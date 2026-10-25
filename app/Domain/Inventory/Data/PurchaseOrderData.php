<?php

namespace App\Domain\Inventory\Data;

/**
 * مدخلات تسجيل طلبية وارد: عدة منتجات بكمياتها وسعر شراء كل قطعة — إجمالي الطلبية مجموع بنودها.
 */
final readonly class PurchaseOrderData
{
    /** @param  list<PurchaseOrderItemData>  $items */
    public function __construct(
        public int $warehouseId,
        public string $orderDate,
        public array $items,
        public ?string $supplier = null,
        public ?string $note = null,
    ) {}

    /** @param  array<string, mixed>  $validated */
    public static function fromArray(array $validated): self
    {
        return new self(
            warehouseId: (int) $validated['warehouse_id'],
            orderDate: $validated['order_date'],
            items: array_map(
                fn (array $item) => PurchaseOrderItemData::fromArray($item),
                array_values($validated['items'] ?? []),
            ),
            supplier: $validated['supplier'] ?? null,
            note: $validated['note'] ?? null,
        );
    }

    public function totalAmount(): float
    {
        return round(array_sum(array_map(fn (PurchaseOrderItemData $item) => $item->total(), $this->items)), 2);
    }
}
