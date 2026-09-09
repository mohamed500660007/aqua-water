<?php

namespace App\Domain\Maintenance\Services;

use App\Domain\Inventory\Data\StockMovementData;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Maintenance\Data\MaintenanceVisitData;
use App\Domain\Maintenance\Models\MaintenanceVisit;
use App\Domain\Sales\Enums\StockSource;
use App\Domain\Sales\Models\SalesContract;
use Illuminate\Support\Facades\DB;

/**
 * تسجيل زيارات الصيانة (تغيير شمعات) وخصم القطعة تلقائيًا من مصدرها:
 * المخزن الرئيسي، أو عهدة الفني، أو عهدة المندوبة.
 */
class MaintenanceVisitService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly EmployeeStockService $employeeStock,
    ) {}

    public function create(MaintenanceVisitData $data): MaintenanceVisit
    {
        return DB::transaction(function () use ($data) {
            $visit = MaintenanceVisit::create([
                'sales_contract_id' => $data->contractId,
                'product_id' => $data->productId,
                'stock_source' => $data->stockSource,
                'warehouse_id' => $data->stockSource === StockSource::Warehouse ? $data->warehouseId : null,
                'technician_id' => $data->technicianId,
                'sales_rep_id' => $data->salesRepId,
                'quantity' => $data->quantity,
                'price' => $data->price,
                'warranty_status' => $data->warrantyStatus,
                'performed_at' => $data->performedAt,
                'note' => $data->note,
            ]);

            $contract = SalesContract::findOrFail($data->contractId);
            $note = "صيانة — {$contract->code}".($data->note ? " — {$data->note}" : '');

            if ($data->stockSource === StockSource::Warehouse) {
                $this->stock->record(new StockMovementData(
                    productId: $data->productId,
                    warehouseId: $data->warehouseId,
                    type: StockMovementType::Out,
                    quantity: $data->quantity,
                    movedAt: $data->performedAt,
                    note: $note,
                    reference: $visit,
                ));
            } else {
                $employeeId = $data->stockSource === StockSource::SalesRep ? $data->salesRepId : $data->technicianId;

                $this->employeeStock->decrease(
                    userId: $employeeId,
                    productId: $data->productId,
                    quantity: $data->quantity,
                    date: $data->performedAt,
                    note: $note,
                    reference: $visit,
                );
            }

            return $visit;
        });
    }

    public function delete(MaintenanceVisit $visit): void
    {
        DB::transaction(function () use ($visit) {
            $note = "إلغاء صيانة — {$visit->contract->code}";

            if ($visit->stock_source === StockSource::Warehouse) {
                $this->stock->record(new StockMovementData(
                    productId: $visit->product_id,
                    warehouseId: $visit->warehouse_id,
                    type: StockMovementType::In,
                    quantity: $visit->quantity,
                    movedAt: today()->toDateString(),
                    note: $note,
                    reference: $visit,
                ));
            } else {
                $employeeId = $visit->stockSourceId();

                $this->employeeStock->increase(
                    userId: $employeeId,
                    productId: $visit->product_id,
                    quantity: $visit->quantity,
                    date: today()->toDateString(),
                    note: $note,
                    reference: $visit,
                );
            }

            $visit->delete();
        });
    }
}
