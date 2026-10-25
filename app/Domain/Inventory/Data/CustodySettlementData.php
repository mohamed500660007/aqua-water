<?php

namespace App\Domain\Inventory\Data;

use App\Domain\Inventory\Enums\CustodySettlementType;

/**
 * مدخلات تسوية عهدة موظف — اتجاه واحد للمستند كله وأكثر من منتج بكميات موجبة.
 */
final readonly class CustodySettlementData
{
    /** @param  list<ProductQuantityData>  $items */
    public function __construct(
        public int $userId,
        public CustodySettlementType $type,
        public string $settlementDate,
        public string $reason,
        public array $items,
        public ?string $note = null,
    ) {}

    /** @param  array<string, mixed>  $validated */
    public static function fromArray(array $validated): self
    {
        return new self(
            userId: (int) $validated['user_id'],
            type: CustodySettlementType::from($validated['type']),
            settlementDate: $validated['settlement_date'],
            reason: $validated['reason'],
            items: array_map(
                fn (array $item) => ProductQuantityData::fromArray($item),
                array_values($validated['items'] ?? []),
            ),
            note: $validated['note'] ?? null,
        );
    }
}
