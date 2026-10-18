<?php

namespace App\Domain\Users\Data;

use App\Domain\Users\Enums\Gender;
use App\Domain\Users\Enums\MaritalStatus;
use App\Domain\Users\Enums\MilitaryStatus;

/**
 * بيانات بطاقة الرقم القومي والبيانات الشخصية للموظف — منفصلة عن بيانات الحساب والوظيفة
 * في UserData لأنها مجموعة كبيرة كلها اختيارية.
 */
final readonly class UserProfileData
{
    public function __construct(
        public ?string $nationalId = null,
        public ?string $nationalIdIssuedAt = null,
        public ?string $nationalIdExpiresAt = null,
        public ?string $nationalIdIssuer = null,
        public ?string $cardAddress = null,
        public ?string $cardOccupation = null,
        public ?string $religion = null,
        public ?MaritalStatus $maritalStatus = null,
        public ?string $birthDate = null,
        public ?Gender $gender = null,
        public ?string $birthPlace = null,
        public ?string $nationality = null,
        public ?string $qualification = null,
        public ?MilitaryStatus $militaryStatus = null,
        public ?string $secondaryPhone = null,
        public ?string $governorate = null,
        public ?string $city = null,
        public ?string $address = null,
        public ?string $emergencyContactName = null,
        public ?string $emergencyContactRelation = null,
        public ?string $emergencyContactPhone = null,
        public ?string $hireDate = null,
        public ?string $notes = null,
    ) {}

    /** @param  array<string, mixed>  $validated */
    public static function fromArray(array $validated): self
    {
        return new self(
            nationalId: $validated['national_id'] ?? null,
            nationalIdIssuedAt: $validated['national_id_issued_at'] ?? null,
            nationalIdExpiresAt: $validated['national_id_expires_at'] ?? null,
            nationalIdIssuer: $validated['national_id_issuer'] ?? null,
            cardAddress: $validated['card_address'] ?? null,
            cardOccupation: $validated['card_occupation'] ?? null,
            religion: $validated['religion'] ?? null,
            maritalStatus: MaritalStatus::tryFrom((string) ($validated['marital_status'] ?? '')),
            birthDate: $validated['birth_date'] ?? null,
            gender: Gender::tryFrom((string) ($validated['gender'] ?? '')),
            birthPlace: $validated['birth_place'] ?? null,
            nationality: $validated['nationality'] ?? null,
            qualification: $validated['qualification'] ?? null,
            militaryStatus: MilitaryStatus::tryFrom((string) ($validated['military_status'] ?? '')),
            secondaryPhone: $validated['secondary_phone'] ?? null,
            governorate: $validated['governorate'] ?? null,
            city: $validated['city'] ?? null,
            address: $validated['address'] ?? null,
            emergencyContactName: $validated['emergency_contact_name'] ?? null,
            emergencyContactRelation: $validated['emergency_contact_relation'] ?? null,
            emergencyContactPhone: $validated['emergency_contact_phone'] ?? null,
            hireDate: $validated['hire_date'] ?? null,
            notes: $validated['notes'] ?? null,
        );
    }

    /** @return array<string, mixed> أعمدة جدول المستخدمين */
    public function toAttributes(): array
    {
        return [
            'national_id' => $this->nationalId,
            'national_id_issued_at' => $this->nationalIdIssuedAt,
            'national_id_expires_at' => $this->nationalIdExpiresAt,
            'national_id_issuer' => $this->nationalIdIssuer,
            'card_address' => $this->cardAddress,
            'card_occupation' => $this->cardOccupation,
            'religion' => $this->religion,
            'marital_status' => $this->maritalStatus,
            'birth_date' => $this->birthDate,
            'gender' => $this->gender,
            'birth_place' => $this->birthPlace,
            'nationality' => $this->nationality,
            'qualification' => $this->qualification,
            // التجنيد يخص الذكور فقط — لا يُحفظ لغيرهم حتى لو أُرسل من النموذج
            'military_status' => $this->gender === Gender::Female ? null : $this->militaryStatus,
            'secondary_phone' => $this->secondaryPhone,
            'governorate' => $this->governorate,
            'city' => $this->city,
            'address' => $this->address,
            'emergency_contact_name' => $this->emergencyContactName,
            'emergency_contact_relation' => $this->emergencyContactRelation,
            'emergency_contact_phone' => $this->emergencyContactPhone,
            'hire_date' => $this->hireDate,
            'notes' => $this->notes,
        ];
    }
}
