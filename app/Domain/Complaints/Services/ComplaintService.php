<?php

namespace App\Domain\Complaints\Services;

use App\Domain\Complaints\Data\ComplaintData;
use App\Domain\Complaints\Models\Complaint;
use App\Domain\Complaints\Models\ComplaintPart;
use App\Domain\Inventory\Data\StockMovementData;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Sales\Enums\StockSource;
use Illuminate\Support\Facades\DB;

/**
 * تسجيل الشكاوى: قطع الغيار (إن وُجدت) تُخصم فورًا من مصدرها — المخزن أو عهدة الفني
 * أو المندوبة — وحذف الشكوى يُعيدها. رسوم الزيارة لا تمس المخزون.
 */
class ComplaintService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly EmployeeStockService $employeeStock,
    ) {}

    public function create(ComplaintData $data): Complaint
    {
        return DB::transaction(function () use ($data) {
            $complaint = Complaint::create([
                'sales_contract_id' => $data->contractId,
                'technician_id' => $data->technicianId,
                'sales_rep_id' => $data->salesRepId,
                'complaint_date' => $data->complaintDate,
                'visit_fee' => $data->visitFee,
                'resolved' => $data->resolved,
                'note' => $data->note,
            ]);

            $complaint->load('contract:id,code');
            $note = "شكوى — {$complaint->contract->code}";

            foreach ($data->parts as $partData) {
                $part = $complaint->parts()->create([
                    'product_id' => $partData->productId,
                    'stock_source' => $partData->stockSource,
                    'warehouse_id' => $partData->stockSource === StockSource::Warehouse ? $partData->warehouseId : null,
                    'quantity' => $partData->quantity,
                    'price' => $partData->price,
                ]);

                $this->moveStock($part->setRelation('complaint', $complaint), StockMovementType::Out, $data->complaintDate, $note);
            }

            return $complaint;
        });
    }

    public function setResolved(Complaint $complaint, bool $resolved): void
    {
        $complaint->update(['resolved' => $resolved]);
    }

    public function delete(Complaint $complaint): void
    {
        DB::transaction(function () use ($complaint) {
            $complaint->loadMissing('contract:id,code', 'parts');
            $note = "إلغاء شكوى — {$complaint->contract->code}";

            foreach ($complaint->parts as $part) {
                $this->moveStock($part, StockMovementType::In, today()->toDateString(), $note);
            }

            $complaint->delete();
        });
    }

    private function moveStock(ComplaintPart $part, StockMovementType $type, string $date, string $note): void
    {
        if ($part->stock_source === StockSource::Warehouse) {
            $this->stock->record(new StockMovementData(
                productId: $part->product_id,
                warehouseId: $part->warehouse_id,
                type: $type,
                quantity: $part->quantity,
                movedAt: $date,
                note: $note,
                reference: $part,
            ));

            return;
        }

        $employeeId = $part->sourceEmployeeId();

        if ($type === StockMovementType::Out) {
            $this->employeeStock->decrease($employeeId, $part->product_id, $part->quantity, $date, $note, $part);
        } else {
            $this->employeeStock->increase($employeeId, $part->product_id, $part->quantity, $date, $note, $part);
        }
    }
}
