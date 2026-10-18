<?php

namespace App\Domain\Gifts\Services;

use App\Domain\Gifts\Data\GiftData;
use App\Domain\Gifts\Models\Gift;
use App\Domain\Inventory\Data\StockMovementData;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Sales\Enums\StockSource;
use Illuminate\Support\Facades\DB;

/**
 * الهدايا: قطعة الغيار تُخصم فورًا من المخزن أو من عهدة الفني، وحذف الهدية يُعيدها.
 */
class GiftService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly EmployeeStockService $employeeStock,
    ) {}

    public function create(GiftData $data): Gift
    {
        return DB::transaction(function () use ($data) {
            $gift = Gift::create([
                'customer_id' => $data->customerId,
                'technician_id' => $data->technicianId,
                'product_id' => $data->productId,
                'stock_source' => $data->stockSource,
                'warehouse_id' => $data->stockSource === StockSource::Warehouse ? $data->warehouseId : null,
                'quantity' => $data->quantity,
                'gift_date' => $data->giftDate,
                'note' => $data->note,
            ]);

            $gift->load('customer:id,name');
            $this->moveStock($gift, StockMovementType::Out, $data->giftDate, "هدية — {$gift->customer->name}");

            return $gift;
        });
    }

    public function delete(Gift $gift): void
    {
        DB::transaction(function () use ($gift) {
            $gift->loadMissing('customer:id,name');
            $this->moveStock($gift, StockMovementType::In, today()->toDateString(), "إلغاء هدية — {$gift->customer->name}");

            $gift->delete();
        });
    }

    private function moveStock(Gift $gift, StockMovementType $type, string $date, string $note): void
    {
        if ($gift->stock_source === StockSource::Warehouse) {
            $this->stock->record(new StockMovementData(
                productId: $gift->product_id,
                warehouseId: $gift->warehouse_id,
                type: $type,
                quantity: $gift->quantity,
                movedAt: $date,
                note: $note,
                reference: $gift,
            ));

            return;
        }

        if ($type === StockMovementType::Out) {
            $this->employeeStock->decrease($gift->technician_id, $gift->product_id, $gift->quantity, $date, $note, $gift);
        } else {
            $this->employeeStock->increase($gift->technician_id, $gift->product_id, $gift->quantity, $date, $note, $gift);
        }
    }
}
