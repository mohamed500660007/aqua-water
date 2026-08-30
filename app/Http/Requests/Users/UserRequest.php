<?php

namespace App\Http\Requests\Users;

use App\Domain\Users\Data\UserData;
use App\Domain\Users\Enums\UserStatus;
use App\Domain\Users\Models\User;
use App\Support\Lookups;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
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
        ]);
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
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'البريد الإلكتروني مستخدم لحساب آخر.',
            'phone.unique' => 'رقم الهاتف مسجل لحساب آخر.',
            'phone.regex' => 'رقم الهاتف غير صحيح — اكتبه بالصيغة المحلية مثل 01012345678.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف.',
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
