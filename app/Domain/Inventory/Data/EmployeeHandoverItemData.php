<?php

namespace App\Domain\Inventory\Data;

/**
 * بند واحد داخل عملية تسليم عهدة لموظف.
 */
final readonly class EmployeeHandoverItemData
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
