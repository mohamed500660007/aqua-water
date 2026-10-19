<?php

namespace App\Domain\Maintenance\Services;

use App\Domain\Maintenance\Models\CandleSchedule;
use App\Domain\Maintenance\Models\CandleStage;
use App\Domain\Maintenance\Models\MaintenanceOperationItem;
use App\Domain\Sales\Enums\ContractKind;
use App\Domain\Sales\Models\SalesContract;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * جدول الصيانة التلقائي: لكل جهاز مركّب ولكل مرحلة مضبوطة، الموعد القادم = (آخر تغيير أو تاريخ التركيب) + دورية المرحلة.
 * «آخر تغيير» يُشتق دائمًا من عمليات الصيانة نفسها، فإلغاء عملية يعيد الموعد السابق تلقائيًا.
 */
class CandleScheduleService
{
    private const CHUNK = 200;

    /** بعد حفظ عقد بيع: ينشئ/يحدّث مواعيد جهازه، أو يحذفها إن لم يعد جهازًا مركّبًا */
    public function syncContract(SalesContract $contract): void
    {
        if (! $contract->isInstalledDevice()) {
            $contract->candleSchedules()->delete();

            return;
        }

        $this->write(collect([$contract]), CandleStage::configured());
    }

    /** بعد عملية صيانة أو إلغائها: تجديد مواعيد هذا الجهاز من آخر تغيير فعلي */
    public function refresh(int $contractId): void
    {
        $contract = SalesContract::query()->find($contractId, ['id', 'kind', 'installation_date']);

        if ($contract) {
            $this->syncContract($contract);
        }
    }

    /** بعد تعديل إعداد المراحل: إعادة حساب كل الأجهزة على دفعات بعدد استعلامات ثابت لكل دفعة */
    public function syncAll(): void
    {
        $stages = CandleStage::configured();

        CandleSchedule::query()->whereNotIn('position', $stages->keys()->all())->delete();

        SalesContract::query()
            ->where('kind', ContractKind::Sale)
            ->whereNotNull('installation_date')
            ->select(['id', 'kind', 'installation_date'])
            ->chunkById(self::CHUNK, fn (Collection $contracts) => $this->write($contracts, $stages));
    }

    /**
     * @param  Collection<int, SalesContract>  $contracts
     * @param  Collection<int, CandleStage>  $stages
     */
    private function write(Collection $contracts, Collection $stages): void
    {
        $contractIds = $contracts->pluck('id')->all();

        CandleSchedule::query()
            ->whereIn('sales_contract_id', $contractIds)
            ->whereNotIn('position', $stages->keys()->all())
            ->delete();

        if ($stages->isEmpty()) {
            return;
        }

        $lastReplaced = $this->lastReplacements($contractIds);
        $now = now();
        $rows = [];

        foreach ($contracts as $contract) {
            foreach ($stages as $position => $stage) {
                $last = $lastReplaced[$contract->id][$position] ?? null;
                $base = CarbonImmutable::parse($last ?? $contract->installation_date);

                $rows[] = [
                    'sales_contract_id' => $contract->id,
                    'position' => $position,
                    'last_replaced_at' => $last,
                    'next_due_at' => $base->addMonthsNoOverflow($stage->interval_months)->toDateString(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        CandleSchedule::upsert($rows, ['sales_contract_id', 'position'], ['last_replaced_at', 'next_due_at', 'updated_at']);
    }

    /**
     * آخر تاريخ تغيير لكل جهاز/مرحلة من العمليات المسجّلة — استعلام تجميعي واحد.
     *
     * @param  list<int>  $contractIds
     * @return array<int, array<int, string>>
     */
    private function lastReplacements(array $contractIds): array
    {
        $rows = MaintenanceOperationItem::query()
            ->join('maintenance_operations', 'maintenance_operations.id', '=', 'maintenance_operation_items.maintenance_operation_id')
            ->whereIn('maintenance_operations.sales_contract_id', $contractIds)
            ->groupBy('maintenance_operations.sales_contract_id', 'maintenance_operation_items.position')
            ->selectRaw('maintenance_operations.sales_contract_id as contract_id, maintenance_operation_items.position as position, MAX(maintenance_operations.serviced_at) as last_at')
            ->toBase()
            ->get();

        $map = [];

        foreach ($rows as $row) {
            $map[$row->contract_id][(int) $row->position] = CarbonImmutable::parse($row->last_at)->toDateString();
        }

        return $map;
    }
}
