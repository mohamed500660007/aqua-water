<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\Data\PurchaseOrderData;
use App\Domain\Inventory\Data\StockMovementData;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

/**
 * الطلبيات: كل بند يدخل المخزن بحركة وارد مرجعها الطلبية، وحذف الطلبية يُخرج نفس الكميات —
 * ويُرفض الحذف لو كانت البضاعة قد صُرفت بالفعل ولم يعد رصيدها كافيًا.
 */
class PurchaseOrderService
{
    public function __construct(private readonly StockService $stock) {}

    public function create(PurchaseOrderData $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $order = PurchaseOrder::create([
                'warehouse_id' => $data->warehouseId,
                'supplier' => $data->supplier,
                'order_date' => $data->orderDate,
                'total_amount' => $data->totalAmount(),
                'note' => $data->note,
            ]);

            $order->items()->createMany(array_map(
                fn ($item) => ['product_id' => $item->productId, 'quantity' => $item->quantity, 'unit_price' => $item->unitPrice],
                $data->items,
            ));

            $this->moveItems($order, StockMovementType::In, $data->orderDate, "طلبية وارد #{$order->id}");

            return $order;
        });
    }

    public function delete(PurchaseOrder $order): void
    {
        DB::transaction(function () use ($order) {
            $this->moveItems($order, StockMovementType::Out, today()->toDateString(), "إلغاء طلبية وارد #{$order->id}");

            $order->delete();
        });
    }

    private function moveItems(PurchaseOrder $order, StockMovementType $type, string $date, string $note): void
    {
        foreach ($order->items as $item) {
            $this->stock->record(new StockMovementData(
                productId: $item->product_id,
                warehouseId: $order->warehouse_id,
                type: $type,
                quantity: $item->quantity,
                movedAt: $date,
                note: $note,
                reference: $order,
            ));
        }
    }
}
