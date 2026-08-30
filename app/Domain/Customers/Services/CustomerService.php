<?php

namespace App\Domain\Customers\Services;

use App\Domain\Customers\Data\CustomerData;
use App\Domain\Customers\Models\Customer;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    private const CODE_PREFIX = 'CUS-';

    private const CODE_LENGTH = 5;

    public function create(CustomerData $data): Customer
    {
        return DB::transaction(function () use ($data) {
            $customer = Customer::create($this->attributes($data) + [
                'code' => $data->code ?? $this->nextCode(),
            ]);

            $this->syncPhones($customer, $data);

            return $customer;
        });
    }

    public function update(Customer $customer, CustomerData $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            $customer->update($this->attributes($data) + [
                'code' => $data->code ?? $customer->code,
            ]);

            $this->syncPhones($customer, $data);

            return $customer;
        });
    }

    public function delete(Customer $customer): void
    {
        $customer->delete();
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
            'status' => $data->status,
            'notes' => $data->notes,
            'registered_at' => $data->registeredAt,
        ];
    }

    /** حفظ أرقام الهاتف مع الحفاظ على السجلات الموجودة وتحديد رقم أساسي واحد فقط */
    private function syncPhones(Customer $customer, CustomerData $data): void
    {
        $normalized = collect($data->phones)
            ->map(fn (string $phone) => PhoneNumber::normalize($phone))
            ->filter()
            ->values();

        if ($normalized->isEmpty()) {
            return;
        }

        $primary = $normalized->get($data->primaryPhoneIndex) ?? $normalized->first();
        $numbers = $normalized->unique()->values();

        $customer->phones()->whereNotIn('phone', $numbers->all())->delete();

        foreach ($numbers as $number) {
            $customer->phones()->updateOrCreate(
                ['phone' => $number],
                ['is_primary' => $number === $primary],
            );
        }

        $customer->unsetRelation('phones')->unsetRelation('primaryPhone');
    }

    /** الكود التالي بناءً على آخر كود مولّد تلقائيًا — تشمل المحذوفين حتى لا يتكرر كود قديم */
    private function nextCode(): string
    {
        $lastCode = Customer::withTrashed()
            ->where('code', 'like', self::CODE_PREFIX.'%')
            ->orderByDesc('code')
            ->value('code');

        $lastNumber = $lastCode ? (int) substr($lastCode, strlen(self::CODE_PREFIX)) : 0;

        return self::CODE_PREFIX.str_pad((string) ($lastNumber + 1), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }
}
