<?php

namespace App\Http\Requests\Sales;

use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Sales\Data\SalesContractData;
use App\Domain\Sales\Data\SalesContractInput;
use App\Domain\Sales\Enums\StockSource;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Sales\Services\ContractPricing;
use App\Domain\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SalesContractRequest extends FormRequest
{
    public function rules(): array
    {
        return SalesContractInput::rules($this->all());
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
        $this->merge(SalesContractInput::normalize($this->all()));
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
        $contract = $this->route('contract');

        // العقد القديم المستورد لا يمس المخزون، فلا معنى لفحص الكمية المتاحة عند تعديله
        if ($validator->errors()->isNotEmpty() || ($contract instanceof SalesContract && ! $contract->stock_tracked)) {
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

    private function validateMoney(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $data = $this->toData();
        $subtotal = app(ContractPricing::class)->forContract($data)['subtotal'];
        $contract = $this->route('contract');

        // بعد بدء التحصيل الحسابات ثابتة: المدفوع يشمل الأقساط المحصّلة، فقواعد المقدم لا تنطبق عليه
        if ($contract instanceof SalesContract && $contract->hasCollections()) {
            if ($this->changesFinancialTerms($contract, $data, $subtotal)) {
                $validator->errors()->add('paid', 'بدأ تحصيل أقساط هذا العقد، فلا يمكن تغيير البنود أو الأسعار أو الخصم أو الفائدة أو المدفوع أو بيانات الأقساط.');
            }

            return;
        }

        $error = SalesContractInput::moneyError($data, $subtotal);

        if ($error) {
            $validator->errors()->add(...$error);
        }
    }

    private function changesFinancialTerms(SalesContract $contract, SalesContractData $data, float $subtotal): bool
    {
        $differs = fn ($stored, float $submitted) => abs((float) $stored - $submitted) > 0.009;

        return $contract->type !== $data->type
            || $differs($contract->subtotal, $subtotal)
            || $differs($contract->discount, $data->discount)
            || $differs($contract->interest, $data->interest)
            || $differs($contract->paid, $data->paid)
            || $contract->installments_count !== $data->installmentsCount
            || $contract->installment_period_months !== $data->installmentPeriodMonths
            || $contract->first_due_date?->toDateString() !== $data->firstDueDate;
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

        return $contract->reservedQuantitiesByProduct();
    }

    public function attributes(): array
    {
        return SalesContractInput::attributes();
    }

    public function messages(): array
    {
        return SalesContractInput::messages();
    }
}
