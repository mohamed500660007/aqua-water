<?php

namespace App\Http\Requests\Maintenance;

use App\Domain\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** «تعديل» من سطر الشمعة: الفني وتاريخ الصيانة فقط */
class MaintenanceScheduleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'technician_id' => ['required', Rule::exists('users', 'id')->where('role', User::INSTALLER_ROLE)->whereNull('deleted_at')],
            'date' => ['required', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'technician_id' => 'الفني',
            'date' => 'تاريخ الصيانة',
        ];
    }
}
