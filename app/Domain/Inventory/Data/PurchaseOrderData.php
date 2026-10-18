<?php

namespace App\Domain\Inventory\Data;

/**
 * مدخلات تسجيل طلبية وارد: عدة منتجات بكمياتها وإجمالي مبلغ واحد للطلبية كلها.
 */
final readonly class PurchaseOrderData
{
    /** @param  list<ProductQuantityData>  $items */
    public function __construct(
        public int $warehouseId,
        public string $orderDate,
        public float $totalAmount,
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
            totalAmount: (float) $validated['total_amount'],
            items: array_map(
                fn (array $item) => ProductQuantityData::fromArray($item),
                array_values($validated['items'] ?? []),
            ),
            supplier: $validated['supplier'] ?? null,
            note: $validated['note'] ?? null,
        );
    }
}
