<?php

namespace App\Domain\Inventory\Data;

/**
 * مدخلات تسوية عهدة موظف بالجرد: لكل منتج الكمية الفعلية التي رجع بها الموظف (quantity)،
 * والفرق عن رصيد عهدته يحدد إشارة التسوية.
 */
final readonly class CustodySettlementData
{
    /** @param  list<ProductQuantityData>  $items  الكمية = المجرودة فعليًا */
    public function __construct(
        public int $userId,
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
            settlementDate: $validated['settlement_date'],
            reason: $validated['reason'],
            items: array_map(
                fn (array $item) => new ProductQuantityData((int) $item['product_id'], (int) $item['counted_quantity']),
                array_values($validated['items'] ?? []),
            ),
            note: $validated['note'] ?? null,
        );
    }
}
