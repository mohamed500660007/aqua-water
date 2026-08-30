<?php

namespace App\Http\Requests\Inventory;

use App\Domain\Inventory\Data\StockMovementData;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Services\StockService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StockMovementRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_id' => ['required', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'type' => ['required', Rule::enum(StockMovementType::class)],
            'quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
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

                $type = StockMovementType::from($this->input('type'));
                $quantity = (int) $this->input('quantity');

                if ($type !== StockMovementType::Adjustment && $quantity < 1) {
                    $validator->errors()->add('quantity', 'الكمية يجب ألا تقل عن 1.');

                    return;
                }

                if ($type !== StockMovementType::Out) {
                    return;
                }

                $available = app(StockService::class)->available(
                    (int) $this->input('product_id'),
                    (int) $this->input('warehouse_id'),
                );

                if ($quantity > $available) {
                    $validator->errors()->add('quantity', "الكمية المتاحة في هذا المخزن {$available} فقط.");
                }
            },
        ];
    }

    public function toData(): StockMovementData
    {
        return StockMovementData::fromArray($this->validated());
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
            'product_id' => 'المنتج',
            'warehouse_id' => 'المخزن',
            'type' => 'نوع الحركة',
            'quantity' => 'الكمية',
            'moved_at' => 'تاريخ الحركة',
            'note' => 'الملاحظة',
        ];
    }

    public function messages(): array
    {
        return [
            'moved_at.before_or_equal' => 'تاريخ الحركة لا يمكن أن يكون في المستقبل.',
            'product_id.exists' => 'المنتج المختار غير موجود.',
            'warehouse_id.exists' => 'المخزن المختار غير موجود أو غير مفعّل.',
        ];
    }
}
