<?php

namespace App\Http\Requests\Complaints;

use App\Domain\Complaints\Data\ComplaintData;
use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Sales\Enums\StockSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ComplaintRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sales_contract_id' => ['required', Rule::exists('sales_contracts', 'id')->whereNull('deleted_at')],
            'technician_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('role', 'فني تركيب')->whereNull('deleted_at'),
            ],
            'sales_rep_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('role', 'مندوب مبيعات')->whereNull('deleted_at'),
            ],
            'complaint_date' => ['required', 'date', 'before_or_equal:today'],
            'visit_fee' => ['nullable', 'numeric', 'min:0'],
            'resolved' => ['boolean'],
            'note' => ['nullable', 'string', 'max:500'],

            // قطع الغيار اختيارية: زيارة بلا قطع تُسجَّل برسوم زيارة فقط
            'parts' => ['nullable', 'array', 'max:20'],
            'parts.*.product_id' => ['required', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'parts.*.stock_source' => ['required', Rule::enum(StockSource::class)],
            'parts.*.warehouse_id' => ['nullable', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'parts.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'parts.*.price' => ['required', 'numeric', 'min:0'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validateStockSources($validator),
            fn (Validator $validator) => $this->validateAvailability($validator),
        ];
    }

    public function toData(): ComplaintData
    {
        return ComplaintData::fromArray($this->validated());
    }

    protected function prepareForValidation(): void
    {
        $parts = collect($this->input('parts', []))
            ->filter(fn ($part) => is_array($part) && filled($part['product_id'] ?? null))
            ->values()
            ->all();

        $this->merge([
            'parts' => $parts,
            'visit_fee' => $this->input('visit_fee') ?: 0,
            'resolved' => $this->boolean('resolved'),
            'complaint_date' => $this->input('complaint_date') ?: now()->toDateString(),
            'note' => trim((string) $this->input('note')) ?: null,
            'technician_id' => $this->input('technician_id') ?: null,
            'sales_rep_id' => $this->input('sales_rep_id') ?: null,
        ]);
    }

    /** المخزن مطلوب لكل قطعة مصدرها مخزن، والفني/المندوبة مطلوبان لو خُصمت قطعة من عهدتهما */
    private function validateStockSources(Validator $validator): void
    {
        foreach ($this->input('parts', []) as $index => $part) {
            $source = $part['stock_source'] ?? null;

            if ($source === StockSource::Warehouse->value && ! filled($part['warehouse_id'] ?? null)) {
                $validator->errors()->add("parts.{$index}.warehouse_id", 'يجب اختيار المخزن الذي تُخصم منه القطعة.');
            }

            if ($source === StockSource::Installer->value && ! $this->filled('technician_id')) {
                $validator->errors()->add('technician_id', 'يجب اختيار الفني لأن قطعة تُخصم من عهدته.');
            }

            if ($source === StockSource::SalesRep->value && ! $this->filled('sales_rep_id')) {
                $validator->errors()->add('sales_rep_id', 'يجب اختيار المندوبة لأن قطعة تُخصم من عهدتها.');
            }
        }
    }

    /** الكميات تُجمَّع لكل (منتج، مصدر) قبل مقارنتها بالمتاح، حتى لا يمرّ منتج مكرَّر عبر بندين */
    private function validateAvailability(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $requested = [];

        foreach ($this->input('parts', []) as $index => $part) {
            $key = $part['stock_source'].':'.$this->sourceId($part).':'.$part['product_id'];
            $requested[$key] = ($requested[$key] ?? 0) + (int) $part['quantity'];

            $available = $this->availableFor($part);

            if ($requested[$key] > $available) {
                $validator->errors()->add("parts.{$index}.quantity", "الكمية المتاحة في هذا المصدر {$available} فقط.");
            }
        }
    }

    /** @param  array<string, mixed>  $part */
    private function sourceId(array $part): int
    {
        return match ($part['stock_source']) {
            StockSource::SalesRep->value => (int) $this->input('sales_rep_id'),
            StockSource::Installer->value => (int) $this->input('technician_id'),
            default => (int) ($part['warehouse_id'] ?? 0),
        };
    }

    /** @param  array<string, mixed>  $part */
    private function availableFor(array $part): int
    {
        $productId = (int) $part['product_id'];

        return match ($part['stock_source']) {
            StockSource::Warehouse->value => app(StockService::class)->available($productId, $this->sourceId($part)),
            default => app(EmployeeStockService::class)->available($this->sourceId($part), $productId),
        };
    }

    public function attributes(): array
    {
        return [
            'sales_contract_id' => 'العقد',
            'technician_id' => 'الفني',
            'sales_rep_id' => 'المندوبة',
            'complaint_date' => 'تاريخ الشكوى',
            'visit_fee' => 'رسوم الزيارة',
            'note' => 'الملاحظات',
            'parts.*.product_id' => 'قطعة الغيار',
            'parts.*.stock_source' => 'مصدر القطعة',
            'parts.*.warehouse_id' => 'المخزن',
            'parts.*.quantity' => 'الكمية',
            'parts.*.price' => 'سعر القطعة',
        ];
    }

    public function messages(): array
    {
        return [
            'sales_contract_id.required' => 'يجب اختيار العقد.',
            'complaint_date.before_or_equal' => 'تاريخ الشكوى لا يمكن أن يكون في المستقبل.',
            'parts.*.product_id.required' => 'اختر قطعة الغيار.',
            'parts.*.price.required' => 'أدخل سعر قطعة الغيار.',
        ];
    }
}
