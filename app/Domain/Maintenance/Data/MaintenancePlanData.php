<?php

namespace App\Domain\Maintenance\Data;

use App\Domain\Maintenance\Enums\MaintenancePlanStatus;

/**
 * مدخلات إنشاء/تعديل خطة الصيانة مع بنودها.
 */
final readonly class MaintenancePlanData
{
    /** @param  list<MaintenancePlanItemData>  $items */
    public function __construct(
        public string $name,
        public MaintenancePlanStatus $status,
        public array $items,
        public ?string $description = null,
        public ?int $durationMonths = null,
    ) {}

    /** @param  array<string, mixed>  $validated */
    public static function fromArray(array $validated): self
    {
        return new self(
            name: $validated['name'],
            status: MaintenancePlanStatus::from($validated['status']),
            items: array_map(
                fn (array $item) => MaintenancePlanItemData::fromArray($item),
                array_values($validated['items'] ?? []),
            ),
            description: $validated['description'] ?? null,
            durationMonths: isset($validated['duration_months']) ? (int) $validated['duration_months'] : null,
        );
    }
}
