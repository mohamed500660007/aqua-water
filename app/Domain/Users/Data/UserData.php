<?php

namespace App\Domain\Users\Data;

use App\Domain\Users\Enums\UserStatus;

/**
 * مدخلات إنشاء/تعديل المستخدم — العقد بين طبقة الـ HTTP وخدمة المجال.
 */
final readonly class UserData
{
    public function __construct(
        public string $name,
        public string $email,
        public UserStatus $status,
        public ?string $phone = null,
        public ?string $role = null,
        public ?string $department = null,
        public ?string $branch = null,
        public float $baseSalary = 0,
        public float $installmentCommission = 10,
        public float $deviceCommission = 0,
        public float $installationCommission = 0,
        public float $marketingCommission = 0,
        public float $maintenanceCommission = 0,
        public ?string $password = null,
        public UserProfileData $profile = new UserProfileData,
    ) {}

    /** @param  array<string, mixed>  $validated */
    public static function fromArray(array $validated): self
    {
        return new self(
            name: $validated['name'],
            email: $validated['email'],
            status: UserStatus::from($validated['status']),
            phone: $validated['phone'] ?? null,
            role: $validated['role'] ?? null,
            department: $validated['department'] ?? null,
            branch: $validated['branch'] ?? null,
            baseSalary: (float) ($validated['base_salary'] ?? 0),
            installmentCommission: (float) ($validated['installment_commission'] ?? 10),
            deviceCommission: (float) ($validated['device_commission'] ?? 0),
            installationCommission: (float) ($validated['installation_commission'] ?? $validated['device_commission'] ?? 0),
            marketingCommission: (float) ($validated['marketing_commission'] ?? 0),
            maintenanceCommission: (float) ($validated['maintenance_commission'] ?? 0),
            password: $validated['password'] ?? null,
            profile: UserProfileData::fromArray($validated),
        );
    }
}
