<?php

namespace App\Http\Requests\Maintenance;

use App\Domain\Maintenance\Data\MaintenancePackageData;
use App\Domain\Maintenance\Models\MaintenancePackage;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MaintenancePackageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sales_contract_id' => ['required', MaintenanceOperationRequest::installedDeviceRule()],
            'customer_id' => ['required', 'integer'],
            // عقد الصيانة الذي سُجّل فيه المبلغ يجب أن يخص نفس العميل
            'maintenance_contract_id' => [
                'nullable',
                Rule::exists('sales_contracts', 'id')
                    ->where('kind', 'maintenance')
                    ->where('customer_id', (int) $this->input('customer_id'))
                    ->whereNull('deleted_at'),
            ],
            'candles' => ['required', 'integer', 'min:1', 'max:50'],
            'gift_candles' => ['nullable', 'integer', 'min:0', 'max:100'],
            'technician_id' => ['nullable', Rule::exists('users', 'id')->where('role', User::INSTALLER_ROLE)->whereNull('deleted_at')],
            'sales_rep_id' => ['nullable', Rule::exists('users', 'id')->whereIn('role', User::CONTRACT_SALES_ROLES)->whereNull('deleted_at')],
            'technician_commission' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'sales_rep_commission' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'started_at' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:500'],
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

                $active = MaintenancePackage::query()->active()->where('sales_contract_id', $this->input('sales_contract_id'))->first();

                if ($active) {
                    $validator->errors()->add('sales_contract_id', "يوجد عقد صيانة ساري لهذا الجهاز ({$active->number()}) — رصيده {$active->remainingCandles()} شمعة.");
                }
            },
        ];
    }

    public function toData(): MaintenancePackageData
    {
        return MaintenancePackageData::fromArray($this->validated());
    }

    protected function prepareForValidation(): void
    {
        $salesRepId = $this->boolean('exclude_sales_rep') ? null : ($this->input('sales_rep_id') ?: null);
        $candles = (int) $this->input('candles');

        // العمولة المتروكة فارغة = عمولة الموظف لكل شمعة × شمعات الباكيج (نفس ما يقترحه النموذج)
        foreach (['technician_commission' => $this->input('technician_id'), 'sales_rep_commission' => $salesRepId] as $field => $userId) {
            if (blank($this->input($field)) && $userId) {
                $this->merge([$field => round((float) User::query()->whereKey($userId)->value('maintenance_commission') * $candles, 2)]);
            }
        }

        $this->merge([
            'customer_id' => SalesContract::query()->whereKey($this->input('sales_contract_id'))->value('customer_id'),
            'maintenance_contract_id' => $this->input('maintenance_contract_id') ?: null,
            'technician_id' => $this->input('technician_id') ?: null,
            'sales_rep_id' => $salesRepId,
            'gift_candles' => $this->input('gift_candles') ?: 0,
            'note' => trim((string) $this->input('note')) ?: null,
        ]);
    }

    public function attributes(): array
    {
        return [
            'sales_contract_id' => 'الجهاز',
            'maintenance_contract_id' => 'عقد الصيانة المالي',
            'candles' => 'عدد شمعات الباكيج',
            'gift_candles' => 'شمعات الهدايا',
            'technician_id' => 'الفني',
            'sales_rep_id' => 'الموظفة / المندوبة',
            'technician_commission' => 'عمولة الفني',
            'sales_rep_commission' => 'عمولة الموظفة',
            'started_at' => 'تاريخ بدء العقد',
            'note' => 'الملاحظة',
        ];
    }

    public function messages(): array
    {
        return [
            'sales_contract_id.exists' => 'الجهاز المختار غير موجود أو لم يُركَّب بعد.',
            'maintenance_contract_id.exists' => 'عقد الصيانة المالي المختار لا يخص هذا العميل.',
            'started_at.before_or_equal' => 'تاريخ البدء لا يمكن أن يكون في المستقبل.',
        ];
    }
}
