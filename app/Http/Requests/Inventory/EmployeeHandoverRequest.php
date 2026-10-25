<?php

namespace App\Http\Requests\Inventory;

use App\Domain\Inventory\Data\EmployeeHandoverData;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Users\Models\User;
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
                Rule::exists('users', 'id')->whereIn('role', User::CUSTODY_ROLES)->whereNull('deleted_at'),
            ],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'moved_at' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:255'],

            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'distinct', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
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

                $stock = app(StockService::class);
                $warehouseId = (int) $this->input('warehouse_id');

                foreach ($this->input('items', []) as $index => $item) {
                    $available = $stock->available((int) $item['product_id'], $warehouseId);

                    if ((int) $item['quantity'] > $available) {
                        $validator->errors()->add(
                            "items.{$index}.quantity",
                            "الكمية المتاحة في هذا المخزن {$available} فقط.",
                        );
                    }
                }
            },
        ];
    }

    public function toData(): EmployeeHandoverData
    {
        return EmployeeHandoverData::fromArray($this->validated());
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->filter(fn ($item) => is_array($item) && filled($item['product_id'] ?? null))
            ->values()
            ->all();

        $this->merge([
            'items' => $items,
            'moved_at' => $this->input('moved_at') ?: now()->toDateString(),
            'note' => trim((string) $this->input('note')) ?: null,
        ]);
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'الموظف',
            'warehouse_id' => 'المخزن',
            'moved_at' => 'التاريخ',
            'note' => 'الملاحظة',
            'items' => 'بنود العهدة',
            'items.*.product_id' => 'المنتج',
            'items.*.quantity' => 'الكمية',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.exists' => 'الموظف المختار غير موجود أو ليس مندوب مبيعات أو فني تركيب.',
            'moved_at.before_or_equal' => 'التاريخ لا يمكن أن يكون في المستقبل.',
            'items.required' => 'يجب إضافة منتج واحد على الأقل للعهدة.',
            'items.min' => 'يجب إضافة منتج واحد على الأقل للعهدة.',
            'items.*.product_id.distinct' => 'لا يمكن تكرار نفس المنتج داخل عملية التسليم.',
            'items.*.quantity.min' => 'الكمية يجب ألا تقل عن 1.',
        ];
    }
}
