<?php

namespace App\Domain\Customers\Import;

use App\Domain\Customers\Data\CustomerData;
use App\Domain\Customers\Data\CustomerInput;
use App\Domain\Customers\Enums\CustomerOrigin;
use App\Domain\Customers\Enums\CustomerStatus;
use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Services\CustomerService;
use App\Support\Spreadsheets\ImportResult;
use App\Support\Spreadsheets\SheetValue;
use Illuminate\Support\Facades\Validator;

/**
 * استيراد العملاء من الشيت: كل الصفوف تُفحص أولًا بنفس قواعد نموذج العميل، ولو في أي خطأ
 * لا يُسجَّل أحد — فإعادة رفع الملف بعد تصحيحه لا تكرر العملاء الذين نجحوا في المرة الأولى.
 */
class CustomerImporter
{
    public function __construct(
        private readonly CustomerSpreadsheet $spreadsheet,
        private readonly CustomerService $customers,
    ) {}

    public function import(string $path): ImportResult
    {
        $rows = $this->spreadsheet->rows($path);
        $inputs = array_map(fn (array $row) => CustomerInput::normalize($this->toInput($row['values'])), $rows);
        $registeredIds = $this->registeredNationalIds($inputs);
        $seenIds = [];
        $errors = [];
        $customers = [];

        foreach ($rows as $index => $row) {
            $input = $inputs[$index];
            $validator = Validator::make(
                $input,
                CustomerInput::rules(checkUniqueNationalId: false),
                $this->messages(),
                CustomerInput::attributes(),
            );

            $rowErrors = $validator->errors()->all();
            $nationalId = $input['national_id'];

            if ($nationalId && isset($registeredIds[$nationalId])) {
                $rowErrors[] = 'الرقم القومي مسجل لعميل موجود بالفعل في النظام.';
            } elseif ($nationalId && isset($seenIds[$nationalId])) {
                $rowErrors[] = "الرقم القومي مكرر — نفس رقم الصف {$seenIds[$nationalId]}.";
            } elseif ($nationalId) {
                $seenIds[$nationalId] = $row['row'];
            }

            if ($rowErrors !== []) {
                $errors[$row['row']] = $rowErrors;
            } else {
                $customers[] = CustomerData::fromArray($validator->validated());
            }
        }

        if ($errors !== []) {
            return new ImportResult(created: 0, rows: count($rows), errors: $errors);
        }

        return new ImportResult(created: $this->customers->createMany($customers), rows: count($rows));
    }

    /** @param  array<string, string>  $values */
    private function toInput(array $values): array
    {
        return [
            'name' => $values['name'] ?? '',
            'phones' => [$values['phone_1'] ?? '', $values['phone_2'] ?? '', $values['phone_3'] ?? ''],
            'primary_phone' => 0,
            'national_id' => $values['national_id'] ?? '',
            'governorate' => $values['governorate'] ?? '',
            'center' => $values['center'] ?? '',
            'village' => $values['village'] ?? '',
            'detailed_address' => $values['detailed_address'] ?? '',
            'status' => $this->enumValue($values['status'] ?? '', CustomerStatus::options(), CustomerStatus::Active->value),
            'origin' => $this->enumValue($values['origin'] ?? '', CustomerOrigin::options(), CustomerOrigin::Internal->value),
            'registered_at' => SheetValue::date($values['registered_at'] ?? ''),
            'notes' => $values['notes'] ?? '',
        ];
    }

    /**
     * يقبل النص العربي الظاهر في القائمة (نشط) أو القيمة المخزّنة (active)، والفارغ يأخذ القيمة الافتراضية.
     * القيمة غير المعروفة تمر كما هي ليرفضها التحقق برسالة واضحة.
     *
     * @param  array<string, string>  $options
     */
    private function enumValue(string $value, array $options, string $default): string
    {
        if ($value === '') {
            return $default;
        }

        return array_search($value, $options, true) ?: $value;
    }

    /**
     * الأرقام القومية المسجلة مسبقًا (حتى للمحذوفين، كقاعدة unique في النموذج) — استعلام واحد للملف كله.
     *
     * @param  list<array<string, mixed>>  $inputs
     * @return array<string, true>
     */
    private function registeredNationalIds(array $inputs): array
    {
        $ids = array_values(array_filter(array_column($inputs, 'national_id')));

        if ($ids === []) {
            return [];
        }

        return Customer::withTrashed()
            ->whereIn('national_id', $ids)
            ->pluck('national_id')
            ->mapWithKeys(fn (string $id) => [$id => true])
            ->all();
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return CustomerInput::messages() + [
            'status.enum' => 'الحالة يجب أن تكون واحدة من: '.implode('، ', CustomerStatus::options()).'.',
            'origin.enum' => 'نوع العميل يجب أن يكون: '.implode(' أو ', CustomerOrigin::options()).'.',
            'registered_at.date' => 'تاريخ التسجيل غير مفهوم — اكتبه بصيغة 2026-10-18.',
        ];
    }
}
