<?php

namespace App\Domain\Users\Services;

use App\Domain\Users\Data\UserData;
use App\Domain\Users\Models\User;

class UserService
{
    public function create(UserData $data): User
    {
        return User::create($this->attributes($data));
    }

    public function update(User $user, UserData $data): User
    {
        $user->update($this->attributes($data));

        return $user;
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    /**
     * كلمة المرور تُحفظ فقط عند إدخالها — تركها فارغة يبقي القديمة كما هي.
     *
     * @return array<string, mixed>
     */
    private function attributes(UserData $data): array
    {
        $attributes = [
            'name' => $data->name,
            'email' => $data->email,
            'phone' => $data->phone,
            'role' => $data->role,
            'department' => $data->department,
            'branch' => $data->branch,
            'status' => $data->status,
            'base_salary' => $data->baseSalary,
            'installment_commission' => $data->installmentCommission,
            'device_commission' => $data->deviceCommission,
            'installation_commission' => $data->installationCommission,
            'marketing_commission' => $data->marketingCommission,
            'maintenance_commission' => $data->maintenanceCommission,
        ] + $data->profile->toAttributes();

        if (filled($data->password)) {
            $attributes['password'] = $data->password;
        }

        return $attributes;
    }
}
