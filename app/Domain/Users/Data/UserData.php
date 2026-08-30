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
        public ?string $password = null,
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
            password: $validated['password'] ?? null,
        );
    }
}
