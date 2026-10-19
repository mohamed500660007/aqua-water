<?php

namespace App\Domain\Customers\Services;

use App\Domain\Customers\Data\CustomerData;
use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Models\CustomerPhone;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    private const CODE_PREFIX = 'CUS-';

    private const CODE_LENGTH = 5;

    private const INSERT_CHUNK = 500;

    public function create(CustomerData $data): Customer
    {
        return DB::transaction(function () use ($data) {
            $customer = Customer::create($this->attributes($data) + [
                'code' => $this->formatCode($this->lastCodeNumber() + 1),
            ]);

            $this->syncPhones($customer, $data);

            return $customer;
        });
    }

    /**
     * إنشاء دفعة عملاء (استيراد الإكسيل) — كلهم أو لا أحد، والكود يُحسب مرة واحدة ثم يتزايد محليًا،
     * فعدد الاستعلامات ثابت تقريبًا مهما كبر الملف.
     *
     * @param  list<CustomerData>  $customers
     */
    public function createMany(array $customers): int
    {
        return DB::transaction(function () use ($customers) {
            $number = $this->lastCodeNumber();
            $now = now();
            $rows = [];
            $phonesByCode = [];

            foreach ($customers as $data) {
                $code = $this->formatCode(++$number);
                $rows[] = [
                    ...$this->attributes($data),
                    'status' => $data->status->value,
                    'origin' => $data->origin->value,
                    'code' => $code,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $phonesByCode[$code] = $this->phoneNumbers($data);
            }

            // إدراج مجمّع ثم استعلام واحد لمعرفة معرّفات العملاء الجدد بأكوادهم الفريدة
            foreach (array_chunk($rows, self::INSERT_CHUNK) as $chunk) {
                Customer::insert($chunk);
            }

            $ids = Customer::query()->whereIn('code', array_keys($phonesByCode))->pluck('id', 'code');
            $phoneRows = [];

            foreach ($phonesByCode as $code => $numbers) {
                foreach ($numbers as $phone => $isPrimary) {
                    $phoneRows[] = [
                        'customer_id' => $ids[$code],
                        'phone' => $phone,
                        'is_primary' => $isPrimary,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            foreach (array_chunk($phoneRows, self::INSERT_CHUNK) as $chunk) {
                CustomerPhone::insert($chunk);
            }

            return count($rows);
        });
    }

    public function update(Customer $customer, CustomerData $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            $customer->update($this->attributes($data));

            $this->syncPhones($customer, $data);

            return $customer;
        });
    }

    /** @return array<string, mixed> */
    private function attributes(CustomerData $data): array
    {
        return [
            'name' => $data->name,
            'national_id' => $data->nationalId,
            'governorate' => $data->governorate,
            'center' => $data->center,
            'village' => $data->village,
            'detailed_address' => $data->detailedAddress,
            'status' => $data->status,
            'origin' => $data->origin,
            'notes' => $data->notes,
            'registered_at' => $data->registeredAt,
        ];
    }

    /** حفظ أرقام الهاتف مع الحفاظ على السجلات الموجودة وتحديد رقم أساسي واحد فقط */
    private function syncPhones(Customer $customer, CustomerData $data): void
    {
        $numbers = $this->phoneNumbers($data);

        if ($numbers === []) {
            return;
        }

        $customer->phones()->whereNotIn('phone', array_keys($numbers))->delete();

        foreach ($numbers as $number => $isPrimary) {
            $customer->phones()->updateOrCreate(['phone' => $number], ['is_primary' => $isPrimary]);
        }

        $customer->unsetRelation('phones')->unsetRelation('primaryPhone');
    }

    /**
     * الأرقام بعد توحيد صيغتها وحذف المكرر: [الرقم => هل هو الأساسي].
     *
     * @return array<string, bool>
     */
    private function phoneNumbers(CustomerData $data): array
    {
        $normalized = collect($data->phones)
            ->map(fn (string $phone) => PhoneNumber::normalize($phone))
            ->filter()
            ->values();

        $primary = $normalized->get($data->primaryPhoneIndex) ?? $normalized->first();

        return $normalized->unique()
            ->mapWithKeys(fn (string $number) => [$number => $number === $primary])
            ->all();
    }

    /** رقم آخر كود — يشمل المحذوفين سابقًا حتى لا يتكرر كود قديم */
    private function lastCodeNumber(): int
    {
        // كود يدوي غير رقمي (مثل CUS-TEST9) يُرتَّب نصيًا بعد الأرقام فيُقرأ صفرًا ويتكرر CUS-00001 —
        // نتخطاه ونأخذ أول كود رقمي، والقراءة تتوقف عنده بلا تحميل كل الأكواد
        $lastCode = Customer::withTrashed()
            ->where('code', 'like', self::CODE_PREFIX.'%')
            ->orderByDesc('code')
            ->select('code')
            ->cursor()
            ->map(fn (Customer $customer) => substr($customer->code, strlen(self::CODE_PREFIX)))
            ->first(fn (string $number) => ctype_digit($number));

        return (int) ($lastCode ?? 0);
    }

    private function formatCode(int $number): string
    {
        return self::CODE_PREFIX.str_pad((string) $number, self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }
}
