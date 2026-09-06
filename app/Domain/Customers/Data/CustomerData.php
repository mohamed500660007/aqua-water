<?php

namespace App\Domain\Customers\Data;

use App\Domain\Customers\Enums\CustomerStatus;

/**
 * مدخلات إنشاء/تعديل العميل — العقد بين طبقة الـ HTTP وخدمة المجال.
 */
final readonly class CustomerData
{
    /** @param  list<string>  $phones */
    public function __construct(
        public string $name,
        public CustomerStatus $status,
        public string $registeredAt,
        public array $phones,
        public int $primaryPhoneIndex = 0,
        public ?string $code = null,
        public ?string $nationalId = null,
        public ?string $governorate = null,
        public ?string $center = null,
        public ?string $village = null,
        public ?string $detailedAddress = null,
        public ?string $notes = null,
    ) {}

    /** @param  array<string, mixed>  $validated */
    public static function fromArray(array $validated): self
    {
        return new self(
            name: $validated['name'],
            status: CustomerStatus::from($validated['status']),
            registeredAt: $validated['registered_at'],
            phones: array_values($validated['phones'] ?? []),
            primaryPhoneIndex: (int) ($validated['primary_phone'] ?? 0),
            code: $validated['code'] ?? null,
            nationalId: $validated['national_id'] ?? null,
            governorate: $validated['governorate'] ?? null,
            center: $validated['center'] ?? null,
            village: $validated['village'] ?? null,
            detailedAddress: $validated['detailed_address'] ?? null,
            notes: $validated['notes'] ?? null,
        );
    }
}
