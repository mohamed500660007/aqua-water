<?php

namespace App\Domain\Maintenance\Services;

use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Maintenance\Data\MaintenanceData;
use App\Domain\Maintenance\Exceptions\MaintenanceException;
use App\Domain\Sales\Models\SalesContractMaintenanceItem;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ملف الصيانة: كل شمعة تُغيَّر مرة واحدة. الصيانة التي تمت تخصم الشمعة من عهدة الفني وتحفظ سعرها وعمولته
 * على سطر الشمعة نفسه فيخرج من الملف؛ والتي لم تتم بعد تحفظ الفني والموعد فقط.
 */
class MaintenanceService
{
    public function __construct(private readonly EmployeeStockService $custody) {}

    public function record(MaintenanceData $data): void
    {
        DB::transaction(function () use ($data) {
            $candles = $this->lockPending($data->candleIds());

            if (! $data->done) {
                $this->schedule($candles, $data->technicianId, $data->date);

                return;
            }

            // عمولة الفني لكل شمعة بسعرها وقت الصيانة، فلا يغيّرها تعديل سعر العمولة لاحقًا
            $commission = (float) User::query()->whereKey($data->technicianId)->value('maintenance_commission');

            foreach ($data->lines as $line) {
                $candle = $candles[$line->candleId];

                $this->custody->decrease(
                    $data->technicianId,
                    $line->productId,
                    1,
                    $data->date,
                    "صيانة {$data->type->label()} — {$candle->product_name} — عقد {$candle->contract->code}",
                    $candle,
                );

                $candle->update([
                    'product_id' => $line->productId,
                    'technician_id' => $data->technicianId,
                    'serviced_at' => $data->date,
                    'service_type' => $data->type,
                    'price' => $line->price,
                    'discount' => $line->discount,
                    'under_warranty' => $line->underWarranty,
                    'technician_commission' => $commission,
                ]);
            }
        });
    }

    /** «تعديل»: تغيير الفني وتاريخ الصيانة لشمعة لم تتم بعد */
    public function reschedule(SalesContractMaintenanceItem $candle, int $technicianId, string $date): void
    {
        DB::transaction(fn () => $this->schedule($this->lockPending([$candle->id]), $technicianId, $date));
    }

    /**
     * @param  Collection<int, SalesContractMaintenanceItem>  $candles
     */
    private function schedule(Collection $candles, int $technicianId, string $date): void
    {
        SalesContractMaintenanceItem::query()
            ->whereKey($candles->modelKeys())
            ->update(['technician_id' => $technicianId, 'replacement_date' => $date]);
    }

    /**
     * قفل الشمعات يمنع تسجيلها مرتين في نفس اللحظة.
     *
     * @param  list<int>  $ids
     * @return Collection<int, SalesContractMaintenanceItem>
     */
    private function lockPending(array $ids): Collection
    {
        $candles = SalesContractMaintenanceItem::query()
            ->with('contract:id,code')
            ->whereKey($ids)
            ->pending()
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($candles->count() !== count($ids)) {
            throw MaintenanceException::alreadyDone();
        }

        return $candles;
    }
}
