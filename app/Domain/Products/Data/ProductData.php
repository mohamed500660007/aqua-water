<?php

namespace App\Domain\Products\Data;

use App\Domain\Products\Enums\ProductStatus;

/**
 * مدخلات إنشاء/تعديل المنتج.
 */
final readonly class ProductData
{
    public function __construct(
        public string $sku,
        public string $name,
        public float $price,
        public ProductStatus $status,
        public ?string $category = null,
        public ?float $cost = null,
        public ?string $warranty = null,
    ) {}

    /** @param  array<string, mixed>  $validated */
    public static function fromArray(array $validated): self
    {
        return new self(
            sku: $validated['sku'],
            name: $validated['name'],
            price: (float) $validated['price'],
            status: ProductStatus::from($validated['status']),
            category: $validated['category'] ?? null,
            cost: isset($validated['cost']) ? (float) $validated['cost'] : null,
            warranty: $validated['warranty'] ?? null,
        );
    }
}
