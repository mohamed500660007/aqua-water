<?php

namespace App\Domain\Inventory\Data;

/**
 * بند «منتج × كمية» داخل عملية مخزون متعددة البنود (تسليم عهدة أو طلبية وارد).
 */
final readonly class ProductQuantityData
{
    public function __construct(
        public int $productId,
        public int $quantity,
    ) {}

    /** @param  array<string, mixed>  $item */
    public static function fromArray(array $item): self
    {
        return new self(
            productId: (int) $item['product_id'],
            quantity: (int) $item['quantity'],
        );
    }
}
