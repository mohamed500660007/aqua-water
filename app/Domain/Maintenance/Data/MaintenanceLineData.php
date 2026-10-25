<?php

namespace App\Domain\Maintenance\Data;

/**
 * شمعة واحدة داخل عملية الصيانة: الشمعة المخصومة من عهدة الفني وسعرها وخصمها وهل هي في الضمان.
 */
final readonly class MaintenanceLineData
{
    public function __construct(
        public int $candleId,
        public ?int $productId = null,
        public float $price = 0.0,
        public float $discount = 0.0,
        public bool $underWarranty = false,
    ) {}

    /** @param  array<string, mixed>  $line */
    public static function fromArray(int $candleId, array $line): self
    {
        $underWarranty = (bool) ($line['under_warranty'] ?? false);

        return new self(
            candleId: $candleId,
            productId: isset($line['product_id']) ? (int) $line['product_id'] : null,
            // الشمعة في الضمان مجانية — لا سعر ولا خصم
            price: $underWarranty ? 0.0 : (float) ($line['price'] ?? 0),
            discount: $underWarranty ? 0.0 : (float) ($line['discount'] ?? 0),
            underWarranty: $underWarranty,
        );
    }
}
