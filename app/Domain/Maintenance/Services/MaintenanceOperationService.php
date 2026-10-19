<?php

namespace App\Domain\Maintenance\Services;

use App\Domain\Inventory\Data\StockMovementData;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Maintenance\Data\MaintenanceOperationData;
use App\Domain\Maintenance\Enums\MaintenancePackageStatus;
use App\Domain\Maintenance\Exceptions\MaintenanceException;
use App\Domain\Maintenance\Models\CandleStage;
use App\Domain\Maintenance\Models\MaintenanceOperation;
use App\Domain\Maintenance\Models\MaintenanceOperationItem;
use App\Domain\Maintenance\Models\MaintenancePackage;
use App\Domain\Sales\Enums\StockSource;
use Illuminate\Support\Facades\DB;

/**
 * تسجيل عملية صيانة كاملة في معاملة واحدة: البنود، خصم الشمعات من المخزن أو عهدة الفني،
 * استهلاك رصيد الباكيج (وتقفيله عند انتهائه)، ثم تجديد مواعيد الجهاز. الإلغاء يعكس كل ذلك.
 */
class MaintenanceOperationService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly EmployeeStockService $employeeStock,
        private readonly CandleScheduleService $schedules,
    ) {}

    public function create(MaintenanceOperationData $data): MaintenanceOperation
    {
        return DB::transaction(function () use ($data) {
            $package = $data->isPackage() ? $this->lockPackage($data->packageId) : null;
            $count = count($data->positions());

            if ($package) {
                $this->assertBalance($package, $count);
            }

            $stages = CandleStage::configured();

            $operation = MaintenanceOperation::create([
                'type' => $data->type,
                'customer_id' => $data->customerId,
                'sales_contract_id' => $data->contractId,
                'maintenance_package_id' => $package?->id,
                'technician_id' => $data->technicianId,
                'sales_rep_id' => $data->salesRepId,
                'stock_source' => $data->stockSource,
                'warehouse_id' => $data->warehouseId,
                'serviced_at' => $data->servicedAt,
                'total' => round(array_sum($data->prices), 2),
                'technician_commission' => $data->technicianCommission,
                'sales_rep_commission' => $data->salesRepCommission,
                'note' => $data->note,
            ]);

            $now = now();
            MaintenanceOperationItem::insert(array_map(fn (int $position) => [
                'maintenance_operation_id' => $operation->id,
                'position' => $position,
                'product_id' => $stages[$position]->product_id,
                'product_name' => $stages[$position]->product->name,
                'unit_price' => $data->prices[$position],
                'created_at' => $now,
                'updated_at' => $now,
            ], $data->positions()));

            $operation->load('items');
            $this->moveStock($operation, StockMovementType::Out, $data->servicedAt, "صيانة {$operation->number()}");

            if ($package) {
                $this->consume($package, $count, $data->servicedAt);
            }

            $this->schedules->refresh($data->contractId);

            return $operation;
        });
    }

    public function delete(MaintenanceOperation $operation): void
    {
        DB::transaction(function () use ($operation) {
            $operation->loadMissing('items');
            $this->moveStock($operation, StockMovementType::In, today()->toDateString(), "إلغاء صيانة {$operation->number()}");

            if ($operation->maintenance_package_id) {
                $this->release($this->lockPackage($operation->maintenance_package_id), $operation->items->count());
            }

            $contractId = $operation->sales_contract_id;
            $operation->delete();

            $this->schedules->refresh($contractId);
        });
    }

    /** الشمعات المتشابهة في أكثر من مرحلة تُجمّع في حركة واحدة لكل منتج */
    private function moveStock(MaintenanceOperation $operation, StockMovementType $type, string $date, string $note): void
    {
        $quantities = $operation->items->countBy('product_id');

        foreach ($quantities as $productId => $quantity) {
            if ($operation->stock_source === StockSource::Warehouse) {
                $this->stock->record(new StockMovementData(
                    productId: $productId,
                    warehouseId: $operation->warehouse_id,
                    type: $type,
                    quantity: $quantity,
                    movedAt: $date,
                    note: $note,
                    reference: $operation,
                ));

                continue;
            }

            $type === StockMovementType::Out
                ? $this->employeeStock->decrease($operation->technician_id, $productId, $quantity, $date, $note, $operation)
                : $this->employeeStock->increase($operation->technician_id, $productId, $quantity, $date, $note, $operation);
        }
    }

    /** قفل صف الباكيج يمنع زيارتين متزامنتين من استهلاك نفس الرصيد */
    private function lockPackage(int $packageId): MaintenancePackage
    {
        return MaintenancePackage::query()->lockForUpdate()->findOrFail($packageId);
    }

    private function assertBalance(MaintenancePackage $package, int $count): void
    {
        if (! $package->isActive()) {
            throw MaintenanceException::packageClosed($package->number());
        }

        if ($count > $package->remainingCandles()) {
            throw MaintenanceException::packageBalance($package->number(), $package->remainingCandles(), $count);
        }
    }

    /** استهلاك الرصيد، وتقفيل العقد تلقائيًا عند آخر شمعة */
    private function consume(MaintenancePackage $package, int $count, string $date): void
    {
        $package->used_candles += $count;

        if ($package->remainingCandles() === 0) {
            $package->status = MaintenancePackageStatus::Closed;
            $package->closed_at = $date;
        }

        $package->save();
    }

    private function release(MaintenancePackage $package, int $count): void
    {
        $package->update([
            'used_candles' => max(0, $package->used_candles - $count),
            'status' => MaintenancePackageStatus::Active,
            'closed_at' => null,
        ]);
    }
}
