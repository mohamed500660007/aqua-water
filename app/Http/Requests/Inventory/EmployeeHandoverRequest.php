<?php

namespace App\Http\Requests\Inventory;

use App\Domain\Inventory\Services\StockService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class EmployeeHandoverRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->whereIn('role', ['مندوب مبيعات', 'فني تركيب'])->whereNull('deleted_at'),
            ],
            'product_id' => ['required', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'moved_at' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $available = app(StockService::class)->available(
                    (int) $this->input('product_id'),
                    (int) $this->input('warehouse_id'),
                );

                if ((int) $this->input('quantity') > $available) {
                    $validator->errors()->add('quantity', "الكمية المتاحة في هذا المخزن {$available} فقط.");
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'moved_at' => $this->input('moved_at') ?: now()->toDateString(),
            'note' => trim((string) $this->input('note')) ?: null,
        ]);
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'الموظف',
            'product_id' => 'المنتج',
            'warehouse_id' => 'المخزن',
            'quantity' => 'الكمية',
            'moved_at' => 'التاريخ',
            'note' => 'الملاحظة',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.exists' => 'الموظف المختار غير موجود أو ليس مندوب مبيعات أو فني تركيب.',
            'moved_at.before_or_equal' => 'التاريخ لا يمكن أن يكون في المستقبل.',
        ];
    }
}
