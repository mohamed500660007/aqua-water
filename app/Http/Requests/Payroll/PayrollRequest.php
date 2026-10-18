<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayrollRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'month' => ['required', 'date_format:Y-m'],
            'base_salary' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'installment_count' => ['required', 'integer', 'min:0'],
            'installment_commission' => ['required', 'numeric', 'min:0'],
            'installed_device_count' => ['required', 'integer', 'min:0'],
            'installation_commission' => ['required', 'numeric', 'min:0'],
            'marketed_device_count' => ['required', 'integer', 'min:0'],
            'marketing_commission' => ['required', 'numeric', 'min:0'],
            'maintenance_count' => ['required', 'integer', 'min:0'],
            'maintenance_commission' => ['required', 'numeric', 'min:0'],
            'regular_deduction' => ['required', 'numeric', 'min:0'],
            'deduction_reason' => [Rule::requiredIf(fn () => (float) $this->input('regular_deduction') > 0), 'nullable', 'string', 'max:500'],
            'attendance_deduction' => ['required', 'numeric', 'min:0'],
            'absence_days' => ['required', 'integer', 'min:0', 'max:31'],
            'bonus' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['absence_days' => $this->input('absence_days') ?: 0]);
    }
}
