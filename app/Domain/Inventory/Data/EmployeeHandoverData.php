<?php

namespace App\Domain\Inventory\Data;

/**
 * مدخلات تسليم عهدة لموظف — يجوز أن تضم أكثر من منتج في نفس عملية التسليم.
 */
final readonly class EmployeeHandoverData
{
    /** @param  list<ProductQuantityData>  $items */
    public function __construct(
        public int $userId,
        public int $warehouseId,
        public array $items,
        public string $movedAt,
        public ?string $note = null,
    ) {}

    /** @param  array<string, mixed>  $validated */
    public static function fromArray(array $validated): self
    {
        return new self(
            userId: (int) $validated['user_id'],
            warehouseId: (int) $validated['warehouse_id'],
            items: array_map(
                fn (array $item) => ProductQuantityData::fromArray($item),
                array_values($validated['items'] ?? []),
            ),
            movedAt: $validated['moved_at'],
            note: $validated['note'] ?? null,
        );
    }
}
