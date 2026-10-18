<?php

namespace App\Domain\Sales\Data;

/**
 * بند واحد داخل عقد البيع — السعر يُقرأ من المنتج وقت الحفظ ولا يأتي من نموذج الواجهة.
 * سعر القطعة الصريح يُستخدم فقط عند استيراد العقود القديمة بسعرها وقت البيع.
 */
final readonly class SalesContractItemData
{
    public function __construct(
        public int $productId,
        public int $quantity,
        public ?float $unitPrice = null,
    ) {}

    /** @param  array<string, mixed>  $item */
    public static function fromArray(array $item): self
    {
        return new self(
            productId: (int) $item['product_id'],
            quantity: (int) $item['quantity'],
            unitPrice: isset($item['unit_price']) ? (float) $item['unit_price'] : null,
        );
    }
}
