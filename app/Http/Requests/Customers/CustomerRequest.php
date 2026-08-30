<?php

namespace App\Http\Requests\Customers;

use App\Domain\Customers\Data\CustomerData;
use App\Domain\Customers\Enums\CustomerStatus;
use App\Domain\Customers\Models\Customer;
use App\Support\Digits;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function rules(): array
    {
        $customer = $this->customer();

        return [
            'code' => ['nullable', 'string', 'max:32', Rule::unique('customers', 'code')->ignore($customer)],
            'name' => ['required', 'string', 'max:150'],
            'national_id' => ['nullable', 'digits:14', Rule::unique('customers', 'national_id')->ignore($customer)],
            'governorate' => ['nullable', 'string', 'max:100'],
            'center' => ['nullable', 'string', 'max:100'],
            'village' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::enum(CustomerStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'registered_at' => ['required', 'date', 'before_or_equal:today'],
            'phones' => ['required', 'array', 'min:1', 'max:10'],
            'phones.*' => ['required', 'string', 'distinct', 'regex:'.PhoneNumber::PATTERN],
            'primary_phone' => ['required', 'integer', 'min:0'],
        ];
    }

    public function toData(): CustomerData
    {
        return CustomerData::fromArray($this->validated());
    }

    /** توحيد صيغة المدخلات قبل التحقق حتى لا يُرفض رقم صحيح بسبب اختلاف الكتابة */
    protected function prepareForValidation(): void
    {
        $phones = collect($this->input('phones', []))
            ->map(fn ($phone) => is_string($phone) ? PhoneNumber::normalize($phone) : null)
            ->filter();

        $primaryPhone = $phones->get((int) $this->input('primary_phone', 0));
        $phones = $phones->values();

        $this->merge([
            'code' => $this->cleaned('code'),
            'name' => $this->cleaned('name'),
            'national_id' => Digits::normalize($this->input('national_id')) ?: null,
            'governorate' => $this->cleaned('governorate'),
            'center' => $this->cleaned('center'),
            'village' => $this->cleaned('village'),
            'notes' => $this->cleaned('notes'),
            'registered_at' => $this->input('registered_at') ?: now()->toDateString(),
            'phones' => $phones->all(),
            'primary_phone' => max(0, (int) $phones->search($primaryPhone)),
        ]);
    }

    public function attributes(): array
    {
        return [
            'code' => 'كود العميل',
            'name' => 'اسم العميل',
            'national_id' => 'الرقم القومي',
            'governorate' => 'المحافظة',
            'center' => 'المركز',
            'village' => 'القرية',
            'status' => 'الحالة',
            'notes' => 'الملاحظات',
            'registered_at' => 'تاريخ التسجيل',
            'phones' => 'أرقام الهاتف',
            'phones.*' => 'رقم الهاتف',
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'كود العميل مستخدم بالفعل.',
            'national_id.digits' => 'الرقم القومي يجب أن يتكون من 14 رقمًا.',
            'national_id.unique' => 'الرقم القومي مسجل لعميل آخر.',
            'registered_at.before_or_equal' => 'تاريخ التسجيل لا يمكن أن يكون في المستقبل.',
            'phones.required' => 'يجب إدخال رقم هاتف واحد على الأقل.',
            'phones.min' => 'يجب إدخال رقم هاتف واحد على الأقل.',
            'phones.*.required' => 'رقم الهاتف مطلوب.',
            'phones.*.distinct' => 'لا يمكن تكرار نفس رقم الهاتف للعميل.',
            'phones.*.regex' => 'رقم الهاتف غير صحيح — اكتبه بالصيغة المحلية مثل 01012345678.',
        ];
    }

    private function customer(): ?Customer
    {
        $customer = $this->route('customer');

        return $customer instanceof Customer ? $customer : null;
    }

    private function cleaned(string $key): ?string
    {
        $value = trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }
}
