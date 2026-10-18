<?php

namespace App\Http\Requests\Inventory;

use App\Domain\Inventory\Data\PurchaseOrderData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'supplier' => ['nullable', 'string', 'max:150'],
            'order_date' => ['required', 'date', 'before_or_equal:today'],
            'total_amount' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'note' => ['nullable', 'string', 'max:500'],

            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'distinct', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    public function toData(): PurchaseOrderData
    {
        return PurchaseOrderData::fromArray($this->validated());
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->filter(fn ($item) => is_array($item) && filled($item['product_id'] ?? null))
            ->values()
            ->all();

        $this->merge([
            'items' => $items,
            'order_date' => $this->input('order_date') ?: now()->toDateString(),
            'supplier' => trim((string) $this->input('supplier')) ?: null,
            'note' => trim((string) $this->input('note')) ?: null,
        ]);
    }

    public function attributes(): array
    {
        return [
            'warehouse_id' => 'المخزن',
            'supplier' => 'المورد',
            'order_date' => 'تاريخ الطلبية',
            'total_amount' => 'إجمالي مبلغ الطلبية',
            'note' => 'الملاحظة',
            'items' => 'بنود الطلبية',
            'items.*.product_id' => 'المنتج',
            'items.*.quantity' => 'الكمية',
        ];
    }

    public function messages(): array
    {
        return [
            'order_date.before_or_equal' => 'تاريخ الطلبية لا يمكن أن يكون في المستقبل.',
            'items.required' => 'يجب إضافة منتج واحد على الأقل للطلبية.',
            'items.min' => 'يجب إضافة منتج واحد على الأقل للطلبية.',
            'items.*.product_id.distinct' => 'لا يمكن تكرار نفس المنتج داخل الطلبية.',
            'items.*.quantity.min' => 'الكمية يجب ألا تقل عن 1.',
        ];
    }
}
