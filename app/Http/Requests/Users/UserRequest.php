<?php

namespace App\Http\Requests\Users;

use App\Domain\Users\Data\UserData;
use App\Domain\Users\Enums\Gender;
use App\Domain\Users\Enums\MaritalStatus;
use App\Domain\Users\Enums\MilitaryStatus;
use App\Domain\Users\Enums\UserStatus;
use App\Domain\Users\Models\User;
use App\Support\Digits;
use App\Support\Lookups;
use App\Support\NationalId;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UserRequest extends FormRequest
{
    public function rules(): array
    {
        $user = $this->routeUser();

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user)],
            'phone' => ['nullable', 'string', 'regex:'.PhoneNumber::PATTERN, Rule::unique('users', 'phone')->ignore($user)],
            'role' => ['nullable', Rule::in(Lookups::roles())],
            'department' => ['nullable', Rule::in(Lookups::departments())],
            'branch' => ['nullable', Rule::in(Lookups::branches())],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'base_salary' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'installment_commission' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'device_commission' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'installation_commission' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'marketing_commission' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'maintenance_commission' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'hire_date' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],

            // بطاقة الرقم القومي
            'national_id' => ['nullable', 'digits:14', Rule::unique('users', 'national_id')->ignore($user)],
            'national_id_issued_at' => ['nullable', 'date', 'before_or_equal:today'],
            'national_id_expires_at' => ['nullable', 'date', 'after:national_id_issued_at'],
            'national_id_issuer' => ['nullable', 'string', 'max:100'],
            'card_address' => ['nullable', 'string', 'max:500'],
            'card_occupation' => ['nullable', 'string', 'max:100'],
            'religion' => ['nullable', Rule::in(Lookups::religions())],
            'marital_status' => ['nullable', Rule::enum(MaritalStatus::class)],

            // البيانات الشخصية
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'nationality' => ['nullable', 'string', 'max:50'],
            'qualification' => ['nullable', 'string', 'max:150'],
            'military_status' => ['nullable', Rule::enum(MilitaryStatus::class)],
            'secondary_phone' => ['nullable', 'string', 'regex:'.PhoneNumber::PATTERN, 'different:phone'],
            'governorate' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:50'],
            'emergency_contact_phone' => ['nullable', 'string', 'regex:'.PhoneNumber::PATTERN],
        ];
    }

    /** الرقم القومي يحمل تاريخ الميلاد والنوع — يُرفض أي تعارض بينه وبين المُدخل يدويًا */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $nationalId = $this->input('national_id');

                if (! $nationalId || $validator->errors()->has('national_id')) {
                    return;
                }

                $decoded = NationalId::parse($nationalId);

                if ($decoded === null) {
                    $validator->errors()->add('national_id', 'الرقم القومي غير صالح — تاريخ الميلاد أو كود المحافظة بداخله غير صحيح.');

                    return;
                }

                if ($this->input('birth_date') && $this->input('birth_date') !== $decoded['birth_date']->toDateString()) {
                    $validator->errors()->add('birth_date', 'تاريخ الميلاد لا يطابق الرقم القومي ('.$decoded['birth_date']->toDateString().').');
                }

                if ($this->input('gender') && $this->input('gender') !== $decoded['gender']) {
                    $validator->errors()->add('gender', 'النوع لا يطابق الرقم القومي.');
                }
            },
        ];
    }

    public function toData(): UserData
    {
        return UserData::fromArray($this->validated());
    }

    /** توحيد صيغة المدخلات قبل التحقق حتى لا تُرفض قيمة صحيحة بسبب اختلاف الكتابة */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->cleaned('name'),
            'email' => Str::lower((string) $this->cleaned('email')) ?: null,
            'phone' => PhoneNumber::normalize($this->input('phone')),
            'role' => $this->cleaned('role'),
            'department' => $this->cleaned('department'),
            'branch' => $this->cleaned('branch'),
            'national_id' => Digits::normalize($this->input('national_id')) ?: null,
            'secondary_phone' => PhoneNumber::normalize($this->input('secondary_phone')),
            'emergency_contact_phone' => PhoneNumber::normalize($this->input('emergency_contact_phone')),
            'nationality' => $this->cleaned('nationality') ?? 'مصري',
        ] + collect([
            'national_id_issued_at', 'national_id_expires_at', 'national_id_issuer', 'card_address', 'card_occupation',
            'religion', 'marital_status', 'birth_date', 'gender', 'birth_place', 'qualification', 'military_status',
            'governorate', 'city', 'address', 'emergency_contact_name', 'emergency_contact_relation', 'hire_date', 'notes',
        ])->mapWithKeys(fn (string $key) => [$key => $this->cleaned($key)])->all());

        $this->fillFromNationalId();
    }

    /** ما يُترك فارغًا من تاريخ الميلاد والنوع ومحل الميلاد يُستخرج من الرقم القومي */
    private function fillFromNationalId(): void
    {
        $decoded = NationalId::parse($this->input('national_id'));

        if ($decoded === null) {
            return;
        }

        $this->merge(array_filter([
            'birth_date' => $this->input('birth_date') ? null : $decoded['birth_date']->toDateString(),
            'gender' => $this->input('gender') ? null : $decoded['gender'],
            'birth_place' => $this->input('birth_place') ? null : $decoded['governorate'],
        ]));
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم المستخدم',
            'email' => 'البريد الإلكتروني',
            'phone' => 'رقم الهاتف',
            'role' => 'الدور الوظيفي',
            'department' => 'القسم',
            'branch' => 'الفرع',
            'status' => 'الحالة',
            'password' => 'كلمة المرور',
            'base_salary' => 'المرتب الثابت',
            'installment_commission' => 'عمولة القسط',
            'device_commission' => 'عمولة الجهاز',
            'installation_commission' => 'عمولة تركيب الجهاز',
            'marketing_commission' => 'عمولة تسويق الجهاز',
            'maintenance_commission' => 'عمولة عميل الصيانة',
            'hire_date' => 'تاريخ التعيين',
            'notes' => 'الملاحظات',
            'national_id' => 'الرقم القومي',
            'national_id_issued_at' => 'تاريخ إصدار البطاقة',
            'national_id_expires_at' => 'تاريخ انتهاء البطاقة',
            'national_id_issuer' => 'جهة الإصدار',
            'card_address' => 'العنوان المدوّن بالبطاقة',
            'card_occupation' => 'المهنة المدوّنة بالبطاقة',
            'religion' => 'الديانة',
            'marital_status' => 'الحالة الاجتماعية',
            'birth_date' => 'تاريخ الميلاد',
            'gender' => 'النوع',
            'birth_place' => 'محل الميلاد',
            'nationality' => 'الجنسية',
            'qualification' => 'المؤهل الدراسي',
            'military_status' => 'الموقف من التجنيد',
            'secondary_phone' => 'رقم الهاتف الإضافي',
            'governorate' => 'المحافظة',
            'city' => 'المركز / المدينة',
            'address' => 'العنوان التفصيلي',
            'emergency_contact_name' => 'اسم جهة الطوارئ',
            'emergency_contact_relation' => 'صلة القرابة',
            'emergency_contact_phone' => 'هاتف الطوارئ',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'البريد الإلكتروني مستخدم لحساب آخر.',
            'phone.unique' => 'رقم الهاتف مسجل لحساب آخر.',
            'phone.regex' => 'رقم الهاتف غير صحيح — اكتبه بالصيغة المحلية مثل 01012345678.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف.',
            'national_id.digits' => 'الرقم القومي يجب أن يتكون من 14 رقمًا.',
            'national_id.unique' => 'الرقم القومي مسجّل لموظف آخر.',
            'national_id_expires_at.after' => 'تاريخ انتهاء البطاقة يجب أن يكون بعد تاريخ إصدارها.',
            'secondary_phone.regex' => 'رقم الهاتف الإضافي غير صحيح — اكتبه بالصيغة المحلية مثل 01012345678.',
            'secondary_phone.different' => 'رقم الهاتف الإضافي مطابق للرقم الأساسي.',
            'emergency_contact_phone.regex' => 'هاتف الطوارئ غير صحيح — اكتبه بالصيغة المحلية مثل 01012345678.',
        ];
    }

    /** المستخدم الجاري تعديله — يُستثنى من قواعد التفرد */
    private function routeUser(): ?User
    {
        $user = $this->route('user');

        return $user instanceof User ? $user : null;
    }

    private function cleaned(string $key): ?string
    {
        $value = trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }
}
