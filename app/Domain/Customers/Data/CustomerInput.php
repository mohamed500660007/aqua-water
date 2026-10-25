<?php

namespace App\Domain\Customers\Data;

use App\Domain\Customers\Enums\CustomerOrigin;
use App\Domain\Customers\Enums\CustomerStatus;
use App\Domain\Customers\Models\Customer;
use App\Support\Digits;
use App\Support\PhoneNumber;
use Illuminate\Validation\Rule;

/**
 * توحيد مدخلات العميل وقواعد التحقق منها — مشتركة بين نموذج الإضافة/التعديل واستيراد الإكسيل
 * حتى يُقبل العميل ويُرفض بنفس المعايير أيًا كان مصدره.
 */
final class CustomerInput
{
    /**
     * @param  bool  $checkUniqueNationalId  الاستيراد يتحقق من التكرار دفعة واحدة بدل استعلام لكل صف
     * @return array<string, mixed>
     */
    public static function rules(?Customer $ignore = null, bool $checkUniqueNationalId = true): array
    {
        $nationalId = ['nullable', 'digits:14'];

        if ($checkUniqueNationalId) {
            $nationalId[] = Rule::unique('customers', 'national_id')->ignore($ignore);
        }

        return [
            'name' => ['required', 'string', 'max:150'],
            'national_id' => $nationalId,
            'governorate' => ['nullable', 'string', 'max:100'],
            'center' => ['nullable', 'string', 'max:100'],
            'village' => ['nullable', 'string', 'max:100'],
            'detailed_address' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(CustomerStatus::class)],
            'origin' => ['required', Rule::enum(CustomerOrigin::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'registered_at' => ['required', 'date', 'before_or_equal:today'],
            'phones' => ['required', 'array', 'min:1', 'max:10'],
            'phones.*' => ['required', 'string', 'distinct', 'regex:'.PhoneNumber::PATTERN],
            'primary_phone' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * توحيد صيغة المدخلات قبل التحقق حتى لا يُرفض رقم صحيح بسبب اختلاف الكتابة.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalize(array $input): array
    {
        $phones = collect($input['phones'] ?? [])
            ->map(fn ($phone) => is_scalar($phone) ? PhoneNumber::normalize((string) $phone) : null)
            ->filter();

        $primaryPhone = $phones->get((int) ($input['primary_phone'] ?? 0));
        $phones = $phones->values();

        return array_merge($input, [
            'name' => self::cleaned($input['name'] ?? null),
            'national_id' => Digits::normalize(isset($input['national_id']) ? (string) $input['national_id'] : null) ?: null,
            'governorate' => self::cleaned($input['governorate'] ?? null),
            'center' => self::cleaned($input['center'] ?? null),
            'village' => self::cleaned($input['village'] ?? null),
            'detailed_address' => self::cleaned($input['detailed_address'] ?? null),
            'notes' => self::cleaned($input['notes'] ?? null),
            'registered_at' => ($input['registered_at'] ?? null) ?: now()->toDateString(),
            'origin' => ($input['origin'] ?? null) ?: CustomerOrigin::Internal->value,
            'phones' => $phones->all(),
            'primary_phone' => max(0, (int) $phones->search($primaryPhone)),
        ]);
    }

    /** @return array<string, string> */
    public static function attributes(): array
    {
        return [
            'name' => 'اسم العميل',
            'national_id' => 'الرقم القومي',
            'governorate' => 'المحافظة',
            'center' => 'المركز',
            'village' => 'القرية',
            'detailed_address' => 'العنوان التفصيلي',
            'status' => 'الحالة',
            'origin' => 'نوع العميل',
            'notes' => 'الملاحظات',
            'registered_at' => 'تاريخ التسجيل',
            'phones' => 'أرقام الهاتف',
            'phones.*' => 'رقم الهاتف',
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
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

    private static function cleaned(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
