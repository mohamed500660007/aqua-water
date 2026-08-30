<?php

namespace App\Http\Requests\Sales;

use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Sales\Data\SalesContractData;
use App\Domain\Sales\Enums\ContractType;
use App\Domain\Sales\Enums\StockSource;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Sales\Services\ContractPricing;
use App\Domain\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SalesContractRequest extends FormRequest
{
    public function rules(): array
    {
        $isInstallment = $this->input('type') === ContractType::Installment->value;

        return [
            'customer_id' => ['required', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'sales_rep_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('role', 'مندوب مبيعات')->whereNull('deleted_at'),
            ],
            'installer_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('role', 'فني تركيب')->whereNull('deleted_at'),
            ],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'stock_source' => ['required', Rule::enum(StockSource::class)],
            'maintenance_plan_id' => ['nullable', Rule::exists('maintenance_plans', 'id')->whereNull('deleted_at')],
            'contract_date' => ['required', 'date'],
            'type' => ['required', Rule::enum(ContractType::class)],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'distinct', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],

            'discount' => ['nullable', 'numeric', 'min:0'],
            'paid' => ['nullable', 'numeric', 'min:0'],

            'installments_count' => [Rule::requiredIf($isInstallment), 'nullable', 'integer', 'min:1', 'max:120'],
            'installment_period_months' => [Rule::requiredIf($isInstallment), 'nullable', 'integer', 'min:1', 'max:12'],
            'first_due_date' => [
                Rule::requiredIf($isInstallment),
                'nullable',
                'date',
                'after_or_equal:contract_date',
            ],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validateStockSource($validator),
            fn (Validator $validator) => $this->validateAvailability($validator),
            fn (Validator $validator) => $this->validateMoney($validator),
        ];
    }

    public function toData(): SalesContractData
    {
        return SalesContractData::fromArray($this->validated());
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->filter(fn ($item) => is_array($item) && filled($item['product_id'] ?? null))
            ->values()
            ->all();

        $this->merge([
            'items' => $items,
            'discount' => $this->input('discount') ?: 0,
            'paid' => $this->input('paid') ?: 0,
            'notes' => trim((string) $this->input('notes')) ?: null,
            'maintenance_plan_id' => $this->input('maintenance_plan_id') ?: null,
            'sales_rep_id' => $this->input('sales_rep_id') ?: null,
            'installer_id' => $this->input('installer_id') ?: null,
            'stock_source' => $this->input('stock_source') ?: StockSource::Warehouse->value,
        ]);
    }

    /** إذا كان مصدر البضاعة موظفًا، يجب أن يكون هذا الموظف مُعيَّنًا فعلًا على العقد */
    private function validateStockSource(Validator $validator): void
    {
        $source = $this->input('stock_source');

        if ($source === StockSource::SalesRep->value && ! $this->filled('sales_rep_id')) {
            $validator->errors()->add('sales_rep_id', 'يجب اختيار مندوب المبيعات لأن مصدر البضاعة منه.');
        }

        if ($source === StockSource::Installer->value && ! $this->filled('installer_id')) {
            $validator->errors()->add('installer_id', 'يجب اختيار فني التركيب لأن مصدر البضاعة منه.');
        }
    }

    /** الكمية المطلوبة يجب أن تكون متاحة فعليًا في المصدر المختار: المخزن أو الموظف */
    private function validateAvailability(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $reserved = $this->reservedByThisContract();

        foreach ($this->input('items', []) as $index => $item) {
            $productId = (int) $item['product_id'];
            $available = $this->availableFor($productId) + ($reserved[$productId] ?? 0);

            if ((int) $item['quantity'] > $available) {
                $validator->errors()->add(
                    "items.{$index}.quantity",
                    "الكمية المتاحة في {$this->sourceLabel()} {$available} فقط.",
                );
            }
        }
    }

    private function availableFor(int $productId): int
    {
        return match ($this->input('stock_source')) {
            StockSource::SalesRep->value => app(EmployeeStockService::class)
                ->available((int) $this->input('sales_rep_id'), $productId),
            StockSource::Installer->value => app(EmployeeStockService::class)
                ->available((int) $this->input('installer_id'), $productId),
            default => app(StockService::class)
                ->available($productId, (int) $this->input('warehouse_id')),
        };
    }

    /** اسم المصدر الفعلي (مخزن أو موظف) — يُستخدم لتوضيح رسائل الكمية المتاحة */
    private function sourceLabel(): string
    {
        return match ($this->input('stock_source')) {
            StockSource::SalesRep->value => User::query()->whereKey($this->input('sales_rep_id'))->value('name') ?? 'مندوب المبيعات',
            StockSource::Installer->value => User::query()->whereKey($this->input('installer_id'))->value('name') ?? 'فني التركيب',
            default => Warehouse::query()->whereKey($this->input('warehouse_id'))->value('name') ?? 'المخزن',
        };
    }

    /** الخصم لا يتجاوز الإجمالي، والمدفوع لا يتجاوز المستحق، والتقسيط يحتاج مبلغًا متبقيًا */
    private function validateMoney(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $pricing = app(ContractPricing::class)->calculate($this->toData()->items);
        $subtotal = $pricing['subtotal'];
        $discount = (float) $this->input('discount', 0);

        if ($discount > $subtotal) {
            $validator->errors()->add('discount', 'الخصم لا يمكن أن يتجاوز إجمالي البنود.');

            return;
        }

        $total = round($subtotal - $discount, 2);
        $paid = (float) $this->input('paid', 0);

        if ($paid > $total) {
            $validator->errors()->add('paid', 'المبلغ المدفوع لا يمكن أن يتجاوز إجمالي العقد.');

            return;
        }

        if ($this->input('type') === ContractType::Installment->value && round($total - $paid, 2) <= 0) {
            $validator->errors()->add('paid', 'لا يمكن إنشاء عقد تقسيط بدون مبلغ متبقٍّ للسداد.');
        }
    }

    /**
     * الكميات المحجوزة أصلًا لهذا العقد تُضاف إلى المتاح عند التعديل — لكن فقط إذا لم يتغيّر
     * مصدر البضاعة نفسه، لأن الكمية القديمة تبقى محجوزة في مصدرها القديم حتى لحظة الحفظ.
     *
     * @return array<int, int>
     */
    private function reservedByThisContract(): array
    {
        $contract = $this->route('contract');

        if (! $contract instanceof SalesContract) {
            return [];
        }

        $previousSourceId = match ($contract->stock_source) {
            StockSource::SalesRep => $contract->sales_rep_id,
            StockSource::Installer => $contract->installer_id,
            default => $contract->warehouse_id,
        };

        $currentSourceId = match ($this->input('stock_source')) {
            StockSource::SalesRep->value => (int) $this->input('sales_rep_id'),
            StockSource::Installer->value => (int) $this->input('installer_id'),
            default => (int) $this->input('warehouse_id'),
        };

        if ($contract->stock_source->value !== $this->input('stock_source') || $previousSourceId !== $currentSourceId) {
            return [];
        }

        return $contract->items()
            ->selectRaw('product_id, SUM(quantity) as quantity')
            ->groupBy('product_id')
            ->pluck('quantity', 'product_id')
            ->map(fn ($quantity) => (int) $quantity)
            ->all();
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'العميل',
            'sales_rep_id' => 'مندوب المبيعات',
            'installer_id' => 'فني التركيب',
            'warehouse_id' => 'المخزن',
            'stock_source' => 'مصدر البضاعة',
            'maintenance_plan_id' => 'خطة الصيانة',
            'contract_date' => 'تاريخ العقد',
            'type' => 'نوع العقد',
            'notes' => 'الملاحظات',
            'items' => 'بنود العقد',
            'items.*.product_id' => 'المنتج',
            'items.*.quantity' => 'الكمية',
            'discount' => 'الخصم',
            'paid' => 'المبلغ المدفوع',
            'installments_count' => 'عدد الأقساط',
            'installment_period_months' => 'دورية السداد',
            'first_due_date' => 'تاريخ أول استحقاق',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'يجب اختيار العميل.',
            'customer_id.exists' => 'العميل المختار غير موجود.',
            'sales_rep_id.exists' => 'المندوب المختار غير موجود أو لا يحمل دور مندوب مبيعات.',
            'installer_id.exists' => 'الفني المختار غير موجود أو لا يحمل دور فني تركيب.',
            'items.required' => 'يجب إضافة بند واحد على الأقل للعقد.',
            'items.min' => 'يجب إضافة بند واحد على الأقل للعقد.',
            'items.*.product_id.distinct' => 'لا يمكن تكرار نفس المنتج داخل العقد.',
            'items.*.quantity.min' => 'الكمية يجب ألا تقل عن 1.',
            'installments_count.required' => 'حدّد عدد الأقساط للعقد بالتقسيط.',
            'installment_period_months.required' => 'حدّد دورية السداد بالأشهر.',
            'first_due_date.required' => 'حدّد تاريخ أول استحقاق.',
            'first_due_date.after_or_equal' => 'تاريخ أول استحقاق يجب ألا يسبق تاريخ العقد.',
        ];
    }
}
