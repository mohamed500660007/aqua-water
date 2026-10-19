<?php

namespace App\Http\Controllers\Maintenance\Concerns;

use App\Domain\Sales\Models\SalesContract;
use App\Support\Digits;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * خطوة اختيار الجهاز في نماذج الصيانة: بحث بالاسم أو الكود أو رقم الهاتف بدل قائمة بكل العملاء.
 */
trait PicksDevice
{
    private const PICKER_LIMIT = 20;

    /** @return Collection<int, SalesContract> */
    private function searchDevices(?string $term): Collection
    {
        $term = trim((string) $term);

        if ($term === '') {
            return collect();
        }

        $digits = Digits::normalize($term);

        return SalesContract::query()
            ->installedDevices()
            ->where(fn (Builder $query) => $query
                ->search($term)
                ->when(strlen($digits) >= 4, fn (Builder $query) => $query
                    ->orWhereHas('customer.phones', fn (Builder $phones) => $phones->where('phone', 'like', "%{$digits}%"))))
            ->with(['customer:id,name,code', 'customer.primaryPhone', 'items:id,sales_contract_id,product_name'])
            ->latest('installation_date')
            ->limit(self::PICKER_LIMIT)
            ->get();
    }

    private function findDevice(?int $contractId): ?SalesContract
    {
        return $contractId
            ? SalesContract::query()->installedDevices()->with(['customer:id,name,code', 'customer.primaryPhone', 'items:id,sales_contract_id,product_name'])->find($contractId)
            : null;
    }
}
