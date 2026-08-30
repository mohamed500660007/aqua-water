<?php

namespace App\Domain\Sales\Data;

/**
 * بند واحد داخل عقد البيع — السعر يُقرأ من المنتج وقت الحفظ ولا يأتي من الواجهة.
 */
final readonly class SalesContractItemData
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
