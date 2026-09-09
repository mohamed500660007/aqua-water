<?php

namespace App\Http\Requests\Sales;

use App\Domain\Sales\Data\CollectInstallmentData;
use App\Domain\Sales\Models\SalesContractInstallment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CollectInstallmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'collected_by_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('role', 'فني تركيب')->whereNull('deleted_at'),
            ],
            'sales_rep_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('role', 'مندوب مبيعات')->whereNull('deleted_at'),
            ],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $installment = $this->installment();

                if ($installment->isPaid()) {
                    $validator->errors()->add('amount', 'هذا القسط مُحصَّل بالفعل.');

                    return;
                }

                $remaining = (float) $installment->contract->remaining;

                if ((float) $this->input('amount') > $remaining) {
                    $validator->errors()->add('amount', "المبلغ أكبر من المتبقي على العقد ({$remaining} ج.م).");
                }
            },
        ];
    }

    public function toData(): CollectInstallmentData
    {
        return CollectInstallmentData::fromArray($this->validated());
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => $this->input('amount') ?: $this->installment()->amount,
            'paid_at' => $this->input('paid_at') ?: now()->toDateString(),
            'collected_by_id' => $this->input('collected_by_id') ?: null,
            'sales_rep_id' => $this->input('sales_rep_id') ?: null,
            'note' => trim((string) $this->input('note')) ?: null,
        ]);
    }

    public function attributes(): array
    {
        return [
            'amount' => 'المبلغ المحصَّل',
            'paid_at' => 'تاريخ التحصيل',
            'collected_by_id' => 'الفني المُحصِّل',
            'sales_rep_id' => 'المندوبة المسؤولة',
            'note' => 'ملاحظة',
        ];
    }

    public function messages(): array
    {
        return [
            'collected_by_id.exists' => 'الفني المختار غير موجود أو لا يحمل دور فني تركيب.',
            'sales_rep_id.exists' => 'المندوبة المختارة غير موجودة أو لا تحمل دور مندوب مبيعات.',
        ];
    }

    private function installment(): SalesContractInstallment
    {
        return $this->route('installment');
    }
}
