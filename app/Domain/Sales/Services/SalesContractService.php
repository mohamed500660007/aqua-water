<?php

namespace App\Domain\Sales\Services;

use App\Domain\Inventory\Data\StockMovementData;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Maintenance\Models\MaintenancePlan;
use App\Domain\Sales\Data\CollectInstallmentData;
use App\Domain\Sales\Data\SalesContractData;
use App\Domain\Sales\Enums\StockSource;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Sales\Models\SalesContractInstallment;
use App\Domain\Sales\Models\SalesContractItem;
use App\Domain\Sales\Models\SalesContractMaintenanceItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SalesContractService
{
    private const CODE_PREFIX = 'AQW-';

    private const MAINTENANCE_CODE_PREFIX = 'AQM-';

    public function __construct(
        private readonly ContractPricing $pricing,
        private readonly StockService $stock,
        private readonly EmployeeStockService $employeeStock,
    ) {}

    /**
     * @param  bool  $trackStock  false للعقود القديمة المستوردة: أجهزتها سُلِّمت قبل النظام فلا تمس المخزون أبدًا
     */
    public function create(SalesContractData $data, bool $trackStock = true): SalesContract
    {
        return DB::transaction(function () use ($data, $trackStock) {
            $pricing = $this->pricing->forContract($data);

            $contract = SalesContract::create($this->attributes($data, $pricing['subtotal']) + [
                'code' => $this->nextCode(Carbon::parse($data->contractDate), $data->isMaintenance()),
                'stock_tracked' => $trackStock,
            ]);

            $this->syncItems($contract, $pricing['lines'], isNew: true);
            $this->syncInstallments($contract, isNew: true);
            $this->syncMaintenanceSnapshot($contract, $data->maintenancePlanId, isNew: true);
            $this->applyStock($contract, previousQuantities: [], previousSource: null);

            return $contract;
        });
    }

    public function update(SalesContract $contract, SalesContractData $data): SalesContract
    {
        return DB::transaction(function () use ($contract, $data) {
            $previousQuantities = $contract->reservedQuantitiesByProduct();
            $previousSource = $this->sourceContext($contract);
            $previousPlanId = $contract->maintenance_plan_id;
            $previousStart = $this->maintenanceStart($contract);
            $pricing = $this->pricing->forContract($data);

            $contract->update($this->attributes($data, $pricing['subtotal']));

            $this->syncItems($contract, $pricing['lines']);
            $this->syncInstallments($contract);

            // نسخة شمعات الصيانة تُعاد عند تغيير الخطة أو تصحيح تاريخ التركيب — ما لم تتغير أي شمعة فعلًا
            $planChanged = $previousPlanId !== $data->maintenancePlanId || $previousStart !== $this->maintenanceStart($contract);

            if ($planChanged && $contract->maintenanceItems()->done()->doesntExist()) {
                $this->syncMaintenanceSnapshot($contract, $data->maintenancePlanId);
            }

            $this->applyStock($contract, $previousQuantities, $previousSource);

            return $contract;
        });
    }

    /**
     * تسجيل تحصيل قسط، وتحديث المدفوع/المتبقي على العقد بنفس المبلغ.
     * التحصيل الجزئي (مبلغ أقل من قيمة القسط) يُنشئ قسطًا جديدًا بالفرق، مرتبطًا
     * بنفس تاريخ استحقاق القسط الأصلي، حتى يبقى المبلغ المتبقي متابَعًا كقسط مستقل.
     */
    public function collectInstallment(SalesContractInstallment $installment, CollectInstallmentData $data): SalesContractInstallment
    {
        return DB::transaction(function () use ($installment, $data) {
            $shortfall = round((float) $installment->amount - $data->amount, 2);

            $installment->update([
                'amount' => $data->amount,
                'paid_at' => $data->paidAt,
                'paid_amount' => $data->amount,
                'collected_by_id' => $data->collectedById,
                'sales_rep_id' => $data->salesRepId,
                'note' => $data->note,
            ]);

            if ($shortfall > 0.01) {
                $nextNumber = 1 + (int) $installment->contract->installments()->max('number');

                $installment->contract->installments()->create([
                    'number' => $nextNumber,
                    'due_date' => $installment->due_date,
                    'amount' => $shortfall,
                    'split_from_id' => $installment->id,
                ]);
            }

            $contract = $installment->contract;
            $contract->update([
                'paid' => round($contract->paid + $data->amount, 2),
                'remaining' => round($contract->remaining - $data->amount, 2),
            ]);

            return $installment->fresh();
        });
    }

    /**
     * التراجع عن تحصيل قسط بالخطأ، وإرجاع المبلغ إلى متبقي العقد.
     * لو نتج عن هذا القسط قسط متبقٍّ من دفعة جزئية، يُدمَج معه ما لم يكن قد
     * حُصِّل هو نفسه بالفعل — عندها يُرفض التراجع حتى لا يضيع سجل ذلك التحصيل.
     */
    public function uncollectInstallment(SalesContractInstallment $installment): SalesContractInstallment
    {
        return DB::transaction(function () use ($installment) {
            $amount = (float) $installment->paid_amount;
            $splits = $installment->splits;

            if ($splits->contains(fn (SalesContractInstallment $split) => $split->isPaid())) {
                throw new RuntimeException('لا يمكن التراجع لأن جزءًا من المبلغ المتبقي تم تحصيله بالفعل.');
            }

            $restoredAmount = (float) $installment->amount + $splits->sum(fn ($split) => (float) $split->amount);
            $splits->each(fn (SalesContractInstallment $split) => $split->delete());

            $installment->update([
                'amount' => $restoredAmount,
                'paid_at' => null,
                'paid_amount' => null,
                'collected_by_id' => null,
                'sales_rep_id' => null,
                'note' => null,
            ]);

            $contract = $installment->contract;
            $contract->update([
                'paid' => round($contract->paid - $amount, 2),
                'remaining' => round($contract->remaining + $amount, 2),
            ]);

            return $installment->fresh();
        });
    }

    /** تحويل العقد للشؤون القانونية يُخرجه من المتأخرات العادية إلى تقرير القانونية */
    public function referToLegal(SalesContract $contract, string $date): void
    {
        $contract->update(['legal_referred_at' => $date]);
    }

    public function withdrawFromLegal(SalesContract $contract): void
    {
        $contract->update(['legal_referred_at' => null]);
    }

    /** حذف العقد يعيد الكميات المحجوزة إلى مصدرها (مخزن أو موظف) عبر حركات وارد */
    /** @return array<string, mixed> */
    private function attributes(SalesContractData $data, float $subtotal): array
    {
        $discount = min($data->discount, $subtotal);
        $total = round($subtotal - $discount, 2);
        $paid = min($data->paid, $total);
        $remaining = round($total - $paid, 2);

        $installmentsCount = $data->installmentsCount;

        return [
            'kind' => $data->kind,
            'customer_id' => $data->customerId,
            'sales_rep_id' => $data->salesRepId,
            'installer_id' => $data->installerId,
            'warehouse_id' => $data->warehouseId,
            'stock_source' => $data->stockSource,
            'maintenance_plan_id' => $data->maintenancePlanId,
            'contract_date' => $data->contractDate,
            'installation_date' => $data->installationDate,
            'collection_date' => $data->collectionDate,
            'maintenance_date' => $data->maintenanceDate,
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
    private function syncItems(SalesContract $contract, array $lines, bool $isNew = false): void
    {
        $this->clearExisting($contract, 'items', $isNew);
        $this->insertChildren(SalesContractItem::class, $contract, $lines);
        $contract->unsetRelation('items');
    }

    /** العقد المُنشأ للتو لا سطور سابقة له — لا داعي لاستعلام حذف */
    private function clearExisting(SalesContract $contract, string $relation, bool $isNew): void
    {
        if (! $isNew) {
            $contract->{$relation}()->delete();
        }

        $contract->unsetRelation($relation);
    }

    /**
     * إدراج سطور تابعة للعقد (بنود/أقساط) باستعلام واحد بدل استعلام لكل سطر.
     *
     * @param  class-string<Model>  $model
     * @param  list<array<string, mixed>>  $rows
     */
    private function insertChildren(string $model, SalesContract $contract, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $now = now();
        $model::insert(array_map(fn (array $row) => $row + [
            'sales_contract_id' => $contract->id,
            'created_at' => $now,
            'updated_at' => $now,
        ], $rows));
    }

    /** جدول الأقساط يُعاد بناؤه بالكامل لأنه مشتق من المتبقي وعدد الأقساط */
    private function syncInstallments(SalesContract $contract, bool $isNew = false): void
    {
        // لو بدأ التحصيل فعليًا، الجدول يبقى ثابتًا حتى لا يُفقد سجل الأقساط المدفوعة
        if (! $isNew && $contract->installments()->whereNotNull('paid_at')->exists()) {
            return;
        }

        $this->clearExisting($contract, 'installments', $isNew);

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

        $this->insertChildren(SalesContractInstallment::class, $contract, $schedule);
    }

    /**
     * نسخة ثابتة من شمعات الخطة على العقد: موعد كل شمعة = تاريخ التركيب (أو العقد إن لم يُركَّب) + دوريتها.
     * الشمعات تنطبق على كل الفلاتر، فلا منتج مرتبط بها.
     */
    private function syncMaintenanceSnapshot(SalesContract $contract, ?int $planId, bool $isNew = false): void
    {
        $this->clearExisting($contract, 'maintenanceItems', $isNew);

        if (! $planId) {
            return;
        }

        $start = CarbonImmutable::parse($this->maintenanceStart($contract));
        $rows = [];

        foreach (MaintenancePlan::CANDLE_INTERVALS as $position => $months) {
            $rows[] = [
                'product_name' => MaintenancePlan::candleLabel($position),
                'interval_months' => $months,
                'replacement_date' => $start->addMonthsNoOverflow($months)->toDateString(),
            ];
        }

        $this->insertChildren(SalesContractMaintenanceItem::class, $contract, $rows);
    }

    private function maintenanceStart(SalesContract $contract): string
    {
        return ($contract->installation_date ?? $contract->contract_date)->toDateString();
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
        if (! $contract->stock_tracked) {
            return;
        }

        $currentSource = $this->sourceContext($contract);
        $sourceChanged = $previousSource !== null
            && ($previousSource['type'] !== $currentSource['type'] || $previousSource['id'] !== $currentSource['id']);

        if ($sourceChanged) {
            foreach ($previousQuantities as $productId => $quantity) {
                $this->moveStock($contract, $previousSource, $productId, $quantity, StockMovementType::In, 'إرجاع بسبب تغيير مصدر البضاعة');
            }

            $previousQuantities = [];
        }

        $current = $contract->reservedQuantitiesByProduct();
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

    /** كود العقد: AQW-YYMM-0001 للبيع وAQM-YYMM-0001 للصيانة، متسلسل داخل نفس الشهر */
    private function nextCode(Carbon $date, bool $maintenance): string
    {
        $prefix = ($maintenance ? self::MAINTENANCE_CODE_PREFIX : self::CODE_PREFIX).$date->format('ym').'-';

        $lastCode = SalesContract::withTrashed()
            ->where('code', 'like', $prefix.'%')
            ->orderByDesc('code')
            ->value('code');

        $lastNumber = $lastCode ? (int) substr($lastCode, strlen($prefix)) : 0;

        return $prefix.str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }
}
