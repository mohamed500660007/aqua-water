<?php

namespace App\Domain\Gifts\Data;

use App\Domain\Sales\Enums\StockSource;

/**
 * مدخلات تسجيل هدية: قطعة غيار تُسلَّم لعميل مجانًا وتُخصم من مصدرها.
 */
final readonly class GiftData
{
    public function __construct(
        public int $customerId,
        public int $productId,
        public StockSource $stockSource,
        public int $quantity,
        public string $giftDate,
        public ?int $technicianId = null,
        public ?int $warehouseId = null,
        public ?string $note = null,
    ) {}

    /** @param  array<string, mixed>  $validated */
    public static function fromArray(array $validated): self
    {
        return new self(
            customerId: (int) $validated['customer_id'],
            productId: (int) $validated['product_id'],
            stockSource: StockSource::from($validated['stock_source']),
            quantity: (int) $validated['quantity'],
            giftDate: $validated['gift_date'],
            technicianId: isset($validated['technician_id']) ? (int) $validated['technician_id'] : null,
            warehouseId: isset($validated['warehouse_id']) ? (int) $validated['warehouse_id'] : null,
            note: $validated['note'] ?? null,
        );
    }
}
