<?php

namespace App\Http\Requests\Maintenance;

use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Maintenance\Data\MaintenanceOperationData;
use App\Domain\Maintenance\Enums\MaintenanceOperationType;
use App\Domain\Maintenance\Enums\MaintenancePackageStatus;
use App\Domain\Maintenance\Models\CandleStage;
use App\Domain\Maintenance\Models\MaintenancePackage;
use App\Domain\Sales\Enums\StockSource;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Validator;

class MaintenanceOperationRequest extends FormRequest
{
    public function rules(): array
    {
        $isPackage = $this->isPackage();

        return [
            'type' => ['required', Rule::enum(MaintenanceOperationType::class)],
            'sales_contract_id' => ['required', self::installedDeviceRule()],
            'customer_id' => ['required', 'integer'],
            'maintenance_package_id' => [
                Rule::requiredIf($isPackage),
                'nullable',
                Rule::exists('maintenance_packages', 'id')
                    ->where('sales_contract_id', (int) $this->input('sales_contract_id'))
                    ->where('status', MaintenancePackageStatus::Active->value),
            ],
            'technician_id' => ['required', Rule::exists('users', 'id')->where('role', User::INSTALLER_ROLE)->whereNull('deleted_at')],
            'sales_rep_id' => ['nullable', Rule::exists('users', 'id')->whereIn('role', User::CONTRACT_SALES_ROLES)->whereNull('deleted_at')],
            'stock_source' => ['required', Rule::in([StockSource::Warehouse->value, StockSource::Installer->value])],
            'warehouse_id' => [
                Rule::requiredIf($this->input('stock_source') === StockSource::Warehouse->value),
                'nullable',
                Rule::exists('warehouses', 'id')->where('is_active', true),
            ],
            'serviced_at' => ['required', 'date', 'before_or_equal:today'],
            'positions' => ['required', 'array', 'min:1', 'max:'.count(CandleStage::POSITIONS)],
            'positions.*' => ['integer', 'distinct', Rule::in(CandleStage::query()->pluck('position')->all())],

            // عملية الباكيج بلا أسعار ولا عمولات: المبلغ في عقد الصيانة والعمولة موحّدة على العقد
            'prices' => $isPackage ? ['prohibited'] : ['required', 'array'],
            'prices.*' => ['numeric', 'min:0', 'max:999999'],
            'technician_commission' => $isPackage ? ['prohibited'] : ['nullable', 'numeric', 'min:0', 'max:999999'],
            'sales_rep_commission' => $isPackage ? ['prohibited'] : ['nullable', 'numeric', 'min:0', 'max:999999'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** جهاز مركّب: عقد بيع له تاريخ تركيب */
    public static function installedDeviceRule(): Exists
    {
        return Rule::exists('sales_contracts', 'id')
            ->where('kind', 'sale')
            ->whereNotNull('installation_date')
            ->whereNull('deleted_at');
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validatePrices($validator),
            fn (Validator $validator) => $this->validatePackageBalance($validator),
            fn (Validator $validator) => $this->validateStock($validator),
        ];
    }

    public function toData(): MaintenanceOperationData
    {
        return MaintenanceOperationData::fromArray($this->validated());
    }

    protected function prepareForValidation(): void
    {
        $positions = collect($this->input('positions', []))->filter(fn ($position) => filled($position))->values()->all();
        $salesRepId = $this->boolean('exclude_sales_rep') ? null : ($this->input('sales_rep_id') ?: null);

        if (! $this->isPackage()) {
            // العمولة المتروكة فارغة = عمولة الموظف لكل شمعة × عدد الشمعات (نفس ما يقترحه النموذج)
            $this->merge([
                'technician_commission' => $this->suggestedCommission('technician_commission', $this->input('technician_id'), count($positions)),
                'sales_rep_commission' => $this->suggestedCommission('sales_rep_commission', $salesRepId, count($positions)),
            ]);
        }

        $this->merge([
            'positions' => $positions,
            // العميل يُستنتج من الجهاز نفسه فلا يمكن تسجيل صيانة جهاز على عميل آخر
            'customer_id' => SalesContract::query()->whereKey($this->input('sales_contract_id'))->value('customer_id'),
            'sales_rep_id' => $salesRepId,
            'warehouse_id' => $this->input('warehouse_id') ?: null,
            'maintenance_package_id' => $this->input('maintenance_package_id') ?: null,
            'note' => trim((string) $this->input('note')) ?: null,
        ]);
    }

    private function suggestedCommission(string $field, mixed $userId, int $candles): mixed
    {
        if (filled($this->input($field)) || ! $userId) {
            return $this->input($field);
        }

        return round((float) User::query()->whereKey($userId)->value('maintenance_commission') * $candles, 2);
    }

    private function isPackage(): bool
    {
        return $this->input('type') === MaintenanceOperationType::Package->value;
    }

    private function validatePrices(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty() || $this->isPackage()) {
            return;
        }

        foreach ($this->input('positions') as $position) {
            if (! is_numeric($this->input("prices.{$position}"))) {
                $validator->errors()->add("prices.{$position}", "اكتب سعر شمعة المرحلة {$position}.");
            }
        }
    }

    private function validatePackageBalance(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty() || ! $this->isPackage()) {
            return;
        }

        $package = MaintenancePackage::find($this->input('maintenance_package_id'));
        $count = count($this->input('positions'));

        if ($package && $count > $package->remainingCandles()) {
            $validator->errors()->add('positions', "رصيد عقد الصيانة {$package->remainingCandles()} شمعة فقط، والمختار {$count}.");
        }
    }

    /** الشمعات (مجمّعة لكل منتج) يجب أن تكون متاحة في المصدر المختار */
    private function validateStock(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $stages = CandleStage::configured();
        $needed = collect($this->input('positions'))->countBy(fn ($position) => $stages[(int) $position]->product_id);
        $fromWarehouse = $this->input('stock_source') === StockSource::Warehouse->value;

        foreach ($needed as $productId => $quantity) {
            $available = $fromWarehouse
                ? app(StockService::class)->available($productId, (int) $this->input('warehouse_id'))
                : app(EmployeeStockService::class)->available((int) $this->input('technician_id'), $productId);

            if ($quantity > $available) {
                $name = $stages->firstWhere('product_id', $productId)->product->name;
                $where = $fromWarehouse ? 'المخزن المختار' : 'عهدة الفني';
                $validator->errors()->add('positions', "المتاح من «{$name}» في {$where} {$available} فقط، والمطلوب {$quantity}.");
            }
        }
    }

    public function attributes(): array
    {
        return [
            'type' => 'نوع الصيانة',
            'sales_contract_id' => 'الجهاز',
            'maintenance_package_id' => 'عقد الصيانة',
            'technician_id' => 'الفني',
            'sales_rep_id' => 'الموظفة / المندوبة',
            'stock_source' => 'مصدر الشمعات',
            'warehouse_id' => 'المخزن',
            'serviced_at' => 'تاريخ الصيانة',
            'positions' => 'الشمعات',
            'prices' => 'أسعار الشمعات',
            'prices.*' => 'سعر الشمعة',
            'technician_commission' => 'عمولة الفني',
            'sales_rep_commission' => 'عمولة الموظفة',
            'note' => 'الملاحظة',
        ];
    }

    public function messages(): array
    {
        return [
            'sales_contract_id.exists' => 'الجهاز المختار غير موجود أو لم يُركَّب بعد.',
            'maintenance_package_id.required' => 'اختر عقد الصيانة الذي ستُخصم منه الشمعات.',
            'maintenance_package_id.exists' => 'عقد الصيانة غير ساري لهذا الجهاز.',
            'technician_id.exists' => 'الفني المختار غير موجود أو لا يحمل دور فني تركيب.',
            'positions.required' => 'اختر شمعة واحدة على الأقل.',
            'positions.*.in' => 'إحدى المراحل المختارة غير مضبوطة في إعداد الشمعات.',
            'prices.prohibited' => 'عملية عقد الصيانة لا تُدخل فيها أسعار — المبلغ مسجّل في عقد الصيانة نفسه.',
            'technician_commission.prohibited' => 'عمولة عقد الصيانة موحّدة على العقد ولا تُدخل في الزيارة.',
            'sales_rep_commission.prohibited' => 'عمولة عقد الصيانة موحّدة على العقد ولا تُدخل في الزيارة.',
            'serviced_at.before_or_equal' => 'تاريخ الصيانة لا يمكن أن يكون في المستقبل.',
        ];
    }
}
