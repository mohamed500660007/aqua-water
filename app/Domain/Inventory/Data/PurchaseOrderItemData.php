<?php

namespace App\Domain\Inventory\Data;

/**
 * بند طلبية وارد: منتج × كمية بسعر شراء القطعة وقت الطلبية.
 */
final readonly class PurchaseOrderItemData
{
    public function __construct(
        public int $productId,
        public int $quantity,
        public float $unitPrice,
    ) {}

    /** @param  array<string, mixed>  $item */
    public static function fromArray(array $item): self
    {
        return new self(
            productId: (int) $item['product_id'],
            quantity: (int) $item['quantity'],
            unitPrice: (float) $item['unit_price'],
        );
    }

    public function total(): float
    {
        return round($this->quantity * $this->unitPrice, 2);
    }
}
