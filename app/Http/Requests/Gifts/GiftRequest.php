<?php

namespace App\Http\Requests\Gifts;

use App\Domain\Gifts\Data\GiftData;
use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Sales\Enums\StockSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GiftRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'customer_id' => ['required', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'technician_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('role', 'فني تركيب')->whereNull('deleted_at'),
            ],
            'product_id' => ['required', Rule::exists('products', 'id')->whereNull('deleted_at')],
            // الهدية تُسلَّم من المخزن أو من عهدة الفني فقط
            'stock_source' => ['required', Rule::in([StockSource::Warehouse->value, StockSource::Installer->value])],
            'warehouse_id' => ['nullable', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'gift_date' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validateStockSource($validator),
            fn (Validator $validator) => $this->validateAvailability($validator),
        ];
    }

    public function toData(): GiftData
    {
        return GiftData::fromArray($this->validated());
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'gift_date' => $this->input('gift_date') ?: now()->toDateString(),
            'quantity' => $this->input('quantity') ?: 1,
            'note' => trim((string) $this->input('note')) ?: null,
            'technician_id' => $this->input('technician_id') ?: null,
            'warehouse_id' => $this->input('warehouse_id') ?: null,
        ]);
    }

    private function validateStockSource(Validator $validator): void
    {
        $source = $this->input('stock_source');

        if ($source === StockSource::Warehouse->value && ! $this->filled('warehouse_id')) {
            $validator->errors()->add('warehouse_id', 'يجب اختيار المخزن الذي تُخصم منه القطعة.');
        }

        if ($source === StockSource::Installer->value && ! $this->filled('technician_id')) {
            $validator->errors()->add('technician_id', 'يجب اختيار الفني لأن القطعة تُخصم من عهدته.');
        }
    }

    private function validateAvailability(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $productId = (int) $this->input('product_id');

        $available = $this->input('stock_source') === StockSource::Warehouse->value
            ? app(StockService::class)->available($productId, (int) $this->input('warehouse_id'))
            : app(EmployeeStockService::class)->available((int) $this->input('technician_id'), $productId);

        if ((int) $this->input('quantity') > $available) {
            $validator->errors()->add('quantity', "الكمية المتاحة في هذا المصدر {$available} فقط.");
        }
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'العميل',
            'technician_id' => 'الفني',
            'product_id' => 'قطعة الغيار',
            'stock_source' => 'مصدر القطعة',
            'warehouse_id' => 'المخزن',
            'quantity' => 'الكمية',
            'gift_date' => 'التاريخ',
            'note' => 'الملاحظات',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'يجب اختيار العميل.',
            'product_id.required' => 'اختر قطعة الغيار.',
            'gift_date.before_or_equal' => 'التاريخ لا يمكن أن يكون في المستقبل.',
        ];
    }
}
