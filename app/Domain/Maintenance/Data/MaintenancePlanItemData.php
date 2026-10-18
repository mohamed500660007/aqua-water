<?php

namespace App\Domain\Maintenance\Data;

/**
 * بند واحد داخل خطة الصيانة: منتج + دورية الاستبدال بالأشهر + تاريخ الاستبدال.
 */
final readonly class MaintenancePlanItemData
{
    public function __construct(
        public int $productId,
        public string $replacementDate,
    ) {}

    /** @param  array<string, mixed>  $item */
    public static function fromArray(array $item): self
    {
        return new self(
            productId: (int) $item['product_id'],
            replacementDate: $item['replacement_date'],
        );
    }
}
