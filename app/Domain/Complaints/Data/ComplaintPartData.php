<?php

namespace App\Domain\Complaints\Data;

use App\Domain\Sales\Enums\StockSource;

/**
 * قطعة غيار واحدة صُرفت في شكوى، ومصدر خصمها.
 */
final readonly class ComplaintPartData
{
    public function __construct(
        public int $productId,
        public StockSource $stockSource,
        public int $quantity,
        public float $price,
        public ?int $warehouseId = null,
    ) {}

    /** @param  array<string, mixed>  $validated */
    public static function fromArray(array $validated): self
    {
        return new self(
            productId: (int) $validated['product_id'],
            stockSource: StockSource::from($validated['stock_source']),
            quantity: (int) $validated['quantity'],
            price: (float) $validated['price'],
            warehouseId: isset($validated['warehouse_id']) ? (int) $validated['warehouse_id'] : null,
        );
    }
}
