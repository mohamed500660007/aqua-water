<?php

namespace App\Domain\Sales\Import;

use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Models\CustomerPhone;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Maintenance\Models\MaintenancePlan;
use App\Domain\Products\Models\Product;
use App\Domain\Users\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Collection;

/**
 * ما يكتبه المستخدم في الشيت بأسماء (منتج، موظف، مخزن، خطة) وكود/هاتف العميل،
 * يُحمَّل كله باستعلامات قليلة ثابتة ثم يُحَل إلى معرّفات — نفس القوائم تملأ القوائم المنسدلة في القالب.
 */
class ContractReferences
{
    /** @var Collection<int, Product> */
    public readonly Collection $productsById;

    /** @var array<string, list<int>> */
    private array $products = [];

    /** @var array<string, array<string, list<int>>> نوع القائمة => الاسم الموحّد => المعرّفات */
    private array $indexes = [];

    /** @var array<string, list<string>> نوع القائمة => الأسماء بكتابتها الأصلية للقوائم المنسدلة */
    private array $names = [];

    /** @var array<string, int> */
    private array $customersByCode = [];

    /** @var array<string, list<int>> */
    private array $customersByPhone = [];

    public function __construct()
    {
        $this->productsById = Product::query()->orderBy('name')->get(['id', 'sku', 'name', 'price'])->keyBy('id');

        foreach ($this->productsById as $product) {
            $this->products[$this->key($product->name)][] = $product->id;
            $this->products[$this->key($product->sku)][] = $product->id;
        }

        $this->register('salesReps', User::query()->whereIn('role', User::CONTRACT_SALES_ROLES)->orderBy('name')->get(['id', 'name']));
        $this->register('installers', User::query()->where('role', User::INSTALLER_ROLE)->orderBy('name')->get(['id', 'name']));
        $this->register('warehouses', Warehouse::query()->active()->orderBy('name')->get(['id', 'name']));
        $this->register('plans', MaintenancePlan::query()->orderBy('name')->get(['id', 'name']));
    }

    /**
     * يحمّل عملاء الملف دفعة واحدة: الأكواد مباشرة، والهواتف عبر جدول الأرقام.
     *
     * @param  list<string>  $values
     */
    public function loadCustomers(array $values): void
    {
        $codes = array_values(array_filter(array_map(fn ($value) => $this->customerCode($value), $values)));
        $phones = array_values(array_filter(array_map(
            fn ($value) => $this->customerCode($value) ? null : PhoneNumber::normalize($value),
            $values,
        )));

        if ($codes !== []) {
            $this->customersByCode = Customer::query()->whereIn('code', $codes)->pluck('id', 'code')->all();
        }

        if ($phones !== []) {
            $this->customersByPhone = CustomerPhone::query()
                ->whereIn('phone', $phones)
                ->whereHas('customer')
                ->get(['phone', 'customer_id'])
                ->groupBy('phone')
                ->map(fn (Collection $rows) => $rows->pluck('customer_id')->unique()->values()->all())
                ->all();
        }
    }

    /** @return int|string معرّف العميل، أو رسالة الخطأ */
    public function customer(string $value): int|string
    {
        if ($code = $this->customerCode($value)) {
            return $this->customersByCode[$code] ?? "لا يوجد عميل بالكود {$code}.";
        }

        $phone = PhoneNumber::normalize($value);
        $ids = $this->customersByPhone[$phone] ?? [];

        return match (count($ids)) {
            1 => $ids[0],
            0 => "لا يوجد عميل مسجل برقم الهاتف {$value} — سجّله أولًا أو اكتب كوده.",
            default => "رقم الهاتف {$value} مسجل لأكثر من عميل — اكتب كود العميل بدلًا منه.",
        };
    }

    public function product(string $value): int|string
    {
        return $this->resolve($this->products, $value, 'منتج', 'بالاسم أو الكود');
    }

    public function salesRep(string $value): int|string
    {
        return $this->resolve($this->indexes['salesReps'], $value, 'مندوب مبيعات');
    }

    public function installer(string $value): int|string
    {
        return $this->resolve($this->indexes['installers'], $value, 'فني تركيب');
    }

    public function warehouse(string $value): int|string
    {
        return $this->resolve($this->indexes['warehouses'], $value, 'مخزن مفعّل');
    }

    public function plan(string $value): int|string
    {
        return $this->resolve($this->indexes['plans'], $value, 'خطة صيانة');
    }

    /** أول مخزن مفعّل أبجديًا — يُسجَّل على العقد القديم إن لم يُحدَّد مخزن */
    public function defaultWarehouseName(): ?string
    {
        return $this->names['warehouses'][0] ?? null;
    }

    /** @return list<string> */
    public function productNames(): array
    {
        return $this->productsById->pluck('name')->unique()->values()->all();
    }

    /** @return list<string> */
    public function salesRepNames(): array
    {
        return $this->names['salesReps'];
    }

    /** @return list<string> */
    public function installerNames(): array
    {
        return $this->names['installers'];
    }

    /** @return list<string> */
    public function warehouseNames(): array
    {
        return $this->names['warehouses'];
    }

    /** @return list<string> */
    public function planNames(): array
    {
        return $this->names['plans'];
    }

    private function customerCode(string $value): ?string
    {
        return preg_match('/^CUS-?(\d+)$/i', trim($value), $match)
            ? 'CUS-'.str_pad($match[1], 5, '0', STR_PAD_LEFT)
            : null;
    }

    /**
     * @param  array<string, list<int>>  $index
     */
    private function resolve(array $index, string $value, string $label, string $by = 'بالاسم'): int|string
    {
        $ids = array_values(array_unique($index[$this->key($value)] ?? []));

        return match (count($ids)) {
            1 => $ids[0],
            0 => "لا يوجد {$label} {$by} «{$value}».",
            default => "يوجد أكثر من {$label} بالاسم «{$value}» — عدّل الاسم في النظام ليكون مميزًا.",
        };
    }

    private function register(string $list, Collection $models): void
    {
        $this->indexes[$list] = [];

        foreach ($models as $model) {
            $this->indexes[$list][$this->key($model->name)][] = $model->id;
        }

        $this->names[$list] = $models->pluck('name')->unique()->values()->all();
    }

    /** المقارنة لا تتأثر بالمسافات الزائدة أو حالة الحروف اللاتينية */
    private function key(?string $value): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim((string) $value)));
    }
}
