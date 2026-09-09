<?php

namespace App\Http\Requests\Maintenance;

use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Maintenance\Data\MaintenanceVisitData;
use App\Domain\Maintenance\Enums\WarrantyStatus;
use App\Domain\Sales\Enums\StockSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MaintenanceVisitRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sales_contract_id' => ['required', Rule::exists('sales_contracts', 'id')->whereNull('deleted_at')],
            'product_id' => ['required', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'stock_source' => ['required', Rule::enum(StockSource::class)],
            'warehouse_id' => ['nullable', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'technician_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('role', 'فني تركيب')->whereNull('deleted_at'),
            ],
            'sales_rep_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('role', 'مندوب مبيعات')->whereNull('deleted_at'),
            ],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'warranty_status' => ['required', Rule::enum(WarrantyStatus::class)],
            'performed_at' => ['required', 'date', 'before_or_equal:today'],
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

    public function toData(): MaintenanceVisitData
    {
        return MaintenanceVisitData::fromArray($this->validated());
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'performed_at' => $this->input('performed_at') ?: now()->toDateString(),
            'note' => trim((string) $this->input('note')) ?: null,
        ]);
    }

    /** المخزن مطلوب لو المصدر مخزن، والموظف المناسب مطلوب لو المصدر عهدة فني أو مندوبة */
    private function validateStockSource(Validator $validator): void
    {
        $source = $this->input('stock_source');

        if ($source === StockSource::Warehouse->value && ! $this->filled('warehouse_id')) {
            $validator->errors()->add('warehouse_id', 'يجب اختيار المخزن لأن مصدر الشمعة منه.');
        }

        if ($source === StockSource::Installer->value && ! $this->filled('technician_id')) {
            $validator->errors()->add('technician_id', 'يجب اختيار الفني لأن مصدر الشمعة من عهدته.');
        }

        if ($source === StockSource::SalesRep->value && ! $this->filled('sales_rep_id')) {
            $validator->errors()->add('sales_rep_id', 'يجب اختيار المندوبة لأن مصدر الشمعة من عهدتها.');
        }
    }

    /** الكمية المطلوبة يجب أن تكون متاحة فعليًا في المصدر المختار */
    private function validateAvailability(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $productId = (int) $this->input('product_id');
        $quantity = (int) $this->input('quantity');
        $source = $this->input('stock_source');

        $available = match ($source) {
            StockSource::SalesRep->value => app(EmployeeStockService::class)
                ->available((int) $this->input('sales_rep_id'), $productId),
            StockSource::Installer->value => app(EmployeeStockService::class)
                ->available((int) $this->input('technician_id'), $productId),
            default => app(StockService::class)
                ->available($productId, (int) $this->input('warehouse_id')),
        };

        if ($quantity > $available) {
            $validator->errors()->add('quantity', "الكمية المتاحة في هذا المصدر {$available} فقط.");
        }
    }

    public function attributes(): array
    {
        return [
            'sales_contract_id' => 'العقد',
            'product_id' => 'المنتج',
            'stock_source' => 'مصدر الشمعة',
            'warehouse_id' => 'المخزن',
            'technician_id' => 'الفني',
            'sales_rep_id' => 'المندوبة',
            'quantity' => 'الكمية',
            'price' => 'السعر',
            'warranty_status' => 'حالة الضمان',
            'performed_at' => 'تاريخ التنفيذ',
        ];
    }

    public function messages(): array
    {
        return [
            'sales_contract_id.required' => 'يجب اختيار العقد.',
            'performed_at.before_or_equal' => 'تاريخ التنفيذ لا يمكن أن يكون في المستقبل.',
        ];
    }
}
