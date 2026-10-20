<?php

namespace App\Http\Requests\Maintenance;

use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Maintenance\Data\MaintenanceData;
use App\Domain\Maintenance\Enums\MaintenanceType;
use App\Domain\Products\Models\Product;
use App\Domain\Sales\Models\SalesContractMaintenanceItem;
use App\Domain\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * «إضافة» من سطر شمعة: قطاعي يشمل الشمعة نفسها، وجملة تشمل كل شمعات العقد التي لم تتم بعد.
 */
class MaintenanceRequest extends FormRequest
{
    /** @var list<int>|null */
    private ?array $candleIds = null;

    public function rules(): array
    {
        $rules = [
            'type' => ['required', Rule::enum(MaintenanceType::class)],
            'technician_id' => ['required', Rule::exists('users', 'id')->where('role', User::INSTALLER_ROLE)->whereNull('deleted_at')],
            'done' => ['required', 'boolean'],
            // الصيانة التي تمت لا تكون في المستقبل؛ الموعد المحدد لصيانة لم تتم يمكن أن يكون كذلك
            'date' => ['required', 'date', ...($this->boolean('done') ? ['before_or_equal:today'] : [])],
        ];

        if (! $this->boolean('done')) {
            return $rules;
        }

        foreach ($this->candleIds() as $id) {
            $rules["lines.{$id}.product_id"] = ['required', Rule::exists('products', 'id')->whereNull('deleted_at')];
            $rules["lines.{$id}.under_warranty"] = ['boolean'];
            $rules["lines.{$id}.price"] = ['exclude_if:lines.'.$id.'.under_warranty,true', 'required', 'numeric', 'min:0', 'max:999999'];
            $rules["lines.{$id}.discount"] = ['exclude_if:lines.'.$id.'.under_warranty,true', 'nullable', 'numeric', 'min:0', 'lte:lines.'.$id.'.price'];
        }

        return $rules;
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->validateCustody($validator)];
    }

    public function toData(): MaintenanceData
    {
        return MaintenanceData::fromArray($this->validated(), $this->candleIds());
    }

    /** @return list<int> */
    public function candleIds(): array
    {
        if ($this->candleIds !== null) {
            return $this->candleIds;
        }

        /** @var SalesContractMaintenanceItem $candle */
        $candle = $this->route('candle');

        return $this->candleIds = $this->input('type') === MaintenanceType::Package->value
            ? SalesContractMaintenanceItem::query()
                ->where('sales_contract_id', $candle->sales_contract_id)
                ->pending()
                ->orderBy('replacement_date')
                ->orderBy('id')
                ->pluck('id')
                ->all()
            : [$candle->id];
    }

    protected function prepareForValidation(): void
    {
        $lines = collect((array) $this->input('lines', []))
            ->map(fn ($line) => is_array($line) ? [...$line, 'under_warranty' => (bool) ($line['under_warranty'] ?? false)] : $line)
            ->all();

        $this->merge([
            'lines' => $lines,
            'done' => $this->boolean('done'),
        ]);
    }

    /** كل شمعة تُخصم من عهدة الفني — المجموع لكل منتج لا يتجاوز ما معه */
    private function validateCustody(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty() || ! $this->boolean('done')) {
            return;
        }

        $needed = collect($this->candleIds())
            ->map(fn (int $id) => (int) $this->input("lines.{$id}.product_id"))
            ->countBy();

        $names = Product::query()->whereKey($needed->keys())->pluck('name', 'id');
        $custody = app(EmployeeStockService::class)->availabilityMap((int) $this->input('technician_id'));

        foreach ($needed as $productId => $quantity) {
            $available = $custody[$productId] ?? 0;

            if ($quantity > $available) {
                $validator->errors()->add('lines', "عهدة الفني فيها {$available} بس من «{$names[$productId]}»، والمطلوب {$quantity}.");
            }
        }
    }

    public function attributes(): array
    {
        return [
            'type' => 'نوع الصيانة',
            'technician_id' => 'الفني',
            'date' => 'تاريخ الصيانة',
            'lines.*.product_id' => 'الشمعة المستخدمة',
            'lines.*.price' => 'السعر',
            'lines.*.discount' => 'الخصم',
        ];
    }

    public function messages(): array
    {
        return [
            'technician_id.exists' => 'الفني المختار غير موجود أو لا يحمل دور فني تركيب.',
            'date.before_or_equal' => 'الصيانة اللي تمت تاريخها مايكونش في المستقبل.',
            'lines.*.product_id.required' => 'اختار الشمعة اللي اتركبت من عهدة الفني.',
            'lines.*.price.required' => 'اكتب سعر الشمعة (أو علّم إنها في الضمان).',
            'lines.*.discount.lte' => 'الخصم مايزيدش عن السعر.',
        ];
    }
}
