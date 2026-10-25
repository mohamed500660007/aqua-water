<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class PayrollPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'paid_at' => $this->input('paid_at') ?: now()->toDateString(),
            'note' => trim((string) $this->input('note')) ?: null,
        ]);
    }

    public function attributes(): array
    {
        return [
            'amount' => 'المبلغ المصروف',
            'paid_at' => 'تاريخ الصرف',
            'note' => 'الملاحظة',
        ];
    }
}
