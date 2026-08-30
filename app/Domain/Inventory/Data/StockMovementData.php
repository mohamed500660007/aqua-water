<?php

namespace App\Domain\Inventory\Data;

use App\Domain\Inventory\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Model;

/**
 * مدخلات تسجيل حركة مخزون واحدة.
 */
final readonly class StockMovementData
{
    public function __construct(
        public int $productId,
        public int $warehouseId,
        public StockMovementType $type,
        public int $quantity,
        public string $movedAt,
        public ?string $note = null,
        public ?Model $reference = null,
    ) {}

    /** @param  array<string, mixed>  $validated */
    public static function fromArray(array $validated): self
    {
        return new self(
            productId: (int) $validated['product_id'],
            warehouseId: (int) $validated['warehouse_id'],
            type: StockMovementType::from($validated['type']),
            quantity: (int) $validated['quantity'],
            movedAt: $validated['moved_at'],
            note: $validated['note'] ?? null,
        );
    }

    public function withReference(Model $reference): self
    {
        return new self(
            productId: $this->productId,
            warehouseId: $this->warehouseId,
            type: $this->type,
            quantity: $this->quantity,
            movedAt: $this->movedAt,
            note: $this->note,
            reference: $reference,
        );
    }
}
