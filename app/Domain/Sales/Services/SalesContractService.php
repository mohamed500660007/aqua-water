<?php

namespace App\Domain\Sales\Services;

use App\Domain\Inventory\Data\StockMovementData;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Maintenance\Models\MaintenancePlan;
use App\Domain\Sales\Data\SalesContractData;
use App\Domain\Sales\Enums\StockSource;
use App\Domain\Sales\Models\SalesContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesContractService
{
    private const CODE_PREFIX = 'AQW-';

    public function __construct(
        private readonly ContractPricing $pricing,
        private readonly StockService $stock,
        private readonly EmployeeStockService $employeeStock,
    ) {}

    public function create(SalesContractData $data): SalesContract
    {
        return DB::transaction(function () use ($data) {
            $pricing = $this->pricing->calculate($data->items);

            $contract = SalesContract::create($this->attributes($data, $pricing['subtotal']) + [
                'code' => $this->nextCode(Carbon::parse($data->contractDate)),
            ]);

            $this->syncItems($contract, $pricing['lines']);
            $this->syncInstallments($contract);
            $this->syncMaintenanceSnapshot($contract, $data->maintenancePlanId);
            $this->applyStock($contract, previousQuantities: [], previousSource: null);

            return $contract;
        });
    }

    public function update(SalesContract $contract, SalesContractData $data): SalesContract
    {
        return DB::transaction(function () use ($contract, $data) {
            $previousQuantities = $this->reservedQuantities($contract);
            $previousSource = $this->sourceContext($contract);
            $previousPlanId = $contract->maintenance_plan_id;
            $pricing = $this->pricing->calculate($data->items);

            $contract->update($this->attributes($data, $pricing['subtotal']));

            $this->syncItems($contract, $pricing['lines']);
            $this->syncInstallments($contract);

            // النسخة الثابتة من بنود الصيانة تُعاد فقط عند تغيير الخطة المرتبطة
            if ($previousPlanId !== $data->maintenancePlanId) {
                $this->syncMaintenanceSnapshot($contract, $data->maintenancePlanId);
            }

            $this->applyStock($contract, $previousQuantities, $previousSource);

            return $contract;
        });
    }

    /** حذف العقد يعيد الكميات المحجوزة إلى مصدرها (مخزن أو موظف) عبر حركات وارد */
    public function delete(SalesContract $contract): void
    {
        DB::transaction(function () use ($contract) {
            $source = $this->sourceContext($contract);

            foreach ($this->reservedQuantities($contract) as $productId => $quantity) {
                $this->moveStock($contract, $source, $productId, $quantity, StockMovementType::In, 'إلغاء عقد');
            }

            $contract->delete();
        });
    }

    /** @return array<string, mixed> */
    private function attributes(SalesContractData $data, float $subtotal): array
    {
        $discount = min($data->discount, $subtotal);
        $total = round($subtotal - $discount, 2);
        $paid = min($data->paid, $total);
        $remaining = round($total - $paid, 2);

        $installmentsCount = $data->installmentsCount;

        return [
            'customer_id' => $data->customerId,
            'sales_rep_id' => $data->salesRepId,
            'installer_id' => $data->installerId,
            'warehouse_id' => $data->warehouseId,
            'stock_source' => $data->stockSource,
            'maintenance_plan_id' => $data->maintenancePlanId,
            'contract_date' => $data->contractDate,
            'type' => $data->type,
            'notes' => $data->notes,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'paid' => $paid,
            'remaining' => $remaining,
            'installments_count' => $installmentsCount,
            'installment_period_months' => $data->installmentPeriodMonths,
            'first_due_date' => $data->firstDueDate,
            'installment_amount' => $installmentsCount
                ? round($remaining / $installmentsCount, 2)
                : null,
        ];
    }

    /** @param  list<array<string, mixed>>  $lines */
    private function syncItems(SalesContract $contract, array $lines): void
    {
        $contract->items()->delete();
        $contract->items()->createMany($lines);
        $contract->unsetRelation('items');
    }

    /** جدول الأقساط يُعاد بناؤه بالكامل لأنه مشتق من المتبقي وعدد الأقساط */
    private function syncInstallments(SalesContract $contract): void
    {
        $contract->installments()->delete();
        $contract->unsetRelation('installments');

        if (! $contract->isInstallment() || ! $contract->installments_count) {
            return;
        }

        $count = $contract->installments_count;
        $period = max(1, (int) $contract->installment_period_months);
        $firstDue = Carbon::parse($contract->first_due_date);
        $amount = (float) $contract->installment_amount;
        $remaining = (float) $contract->remaining;

        $schedule = [];

        for ($number = 1; $number <= $count; $number++) {
            // القسط الأخير يستوعب فروق التقريب حتى يساوي المجموع المتبقي تمامًا
            $value = $number === $count
                ? round($remaining - $amount * ($count - 1), 2)
                : $amount;

            $schedule[] = [
                'number' => $number,
                'due_date' => $firstDue->copy()->addMonths(($number - 1) * $period)->toDateString(),
                'amount' => $value,
            ];
        }

        $contract->installments()->createMany($schedule);
    }

    private function syncMaintenanceSnapshot(SalesContract $contract, ?int $planId): void
    {
        $contract->maintenanceItems()->delete();
        $contract->unsetRelation('maintenanceItems');

        if (! $planId) {
            return;
        }

        $plan = MaintenancePlan::query()->with('items.product:id,name')->find($planId);

        if (! $plan) {
            return;
        }

        $contract->maintenanceItems()->createMany(
            $plan->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? 'منتج محذوف',
                'interval_months' => $item->interval_months,
                'replacement_date' => $item->replacement_date->toDateString(),
            ])->all(),
        );
    }

    /**
     * تحديث المخزون بفروق الكميات فقط: الزيادة تُصرف والنقصان يُرد.
     * إذا تغيّر مصدر البضاعة نفسه بين التعديلين، تُرد كل الكميات القديمة إلى مصدرها القديم
     * بالكامل ثم تُصرف الكميات الجديدة من المصدر الجديد، بدل حساب فرق بين مصدرين مختلفين.
     *
     * @param  array<int, int>  $previousQuantities
     * @param  array{type: StockSource, id: int|null}|null  $previousSource
     */
    private function applyStock(SalesContract $contract, array $previousQuantities, ?array $previousSource): void
    {
        $currentSource = $this->sourceContext($contract);
        $sourceChanged = $previousSource !== null
            && ($previousSource['type'] !== $currentSource['type'] || $previousSource['id'] !== $currentSource['id']);

        if ($sourceChanged) {
            foreach ($previousQuantities as $productId => $quantity) {
                $this->moveStock($contract, $previousSource, $productId, $quantity, StockMovementType::In, 'إرجاع بسبب تغيير مصدر البضاعة');
            }

            $previousQuantities = [];
        }

        $current = $this->reservedQuantities($contract);
        $productIds = array_unique([...array_keys($previousQuantities), ...array_keys($current)]);

        foreach ($productIds as $productId) {
            $delta = ($current[$productId] ?? 0) - ($previousQuantities[$productId] ?? 0);

            if ($delta === 0) {
                continue;
            }

            $this->moveStock(
                $contract,
                $currentSource,
                $productId,
                abs($delta),
                $delta > 0 ? StockMovementType::Out : StockMovementType::In,
                $delta > 0 ? 'صرف بضاعة عقد بيع' : 'تعديل كميات عقد بيع',
            );
        }
    }

    /** @param  array{type: StockSource, id: int|null}  $source */
    private function moveStock(
        SalesContract $contract,
        array $source,
        int $productId,
        int $quantity,
        StockMovementType $type,
        string $note,
    ): void {
        $note = "{$note} {$contract->code}";
        $date = $contract->contract_date->toDateString();

        if ($source['type'] === StockSource::Warehouse) {
            $this->stock->record(new StockMovementData(
                productId: $productId,
                warehouseId: $source['id'],
                type: $type,
                quantity: $quantity,
                movedAt: $date,
                note: $note,
                reference: $contract,
            ));

            return;
        }

        $type === StockMovementType::Out
            ? $this->employeeStock->decrease($source['id'], $productId, $quantity, $date, $note, $contract)
            : $this->employeeStock->increase($source['id'], $productId, $quantity, $date, $note, $contract);
    }

    /** @return array{type: StockSource, id: int|null} */
    private function sourceContext(SalesContract $contract): array
    {
        return ['type' => $contract->stock_source, 'id' => $contract->stockSourceId()];
    }

    /** @return array<int, int> */
    private function reservedQuantities(SalesContract $contract): array
    {
        return $contract->items()
            ->selectRaw('product_id, SUM(quantity) as quantity')
            ->groupBy('product_id')
            ->pluck('quantity', 'product_id')
            ->map(fn ($quantity) => (int) $quantity)
            ->all();
    }

    /** كود العقد: AQW-YYMM-0001 متسلسل داخل نفس الشهر */
    private function nextCode(Carbon $date): string
    {
        $prefix = self::CODE_PREFIX.$date->format('ym').'-';

        $lastCode = SalesContract::withTrashed()
            ->where('code', 'like', $prefix.'%')
            ->orderByDesc('code')
            ->value('code');

        $lastNumber = $lastCode ? (int) substr($lastCode, strlen($prefix)) : 0;

        return $prefix.str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }
}
