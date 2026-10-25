<?php

namespace App\Domain\Maintenance\Services;

use App\Domain\Maintenance\Enums\MaintenanceType;
use App\Domain\Reports\Data\ReportPeriod;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Sales\Models\SalesContractMaintenanceItem;
use App\Domain\Users\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * تقرير ملف الصيانة: ما تم في الفترة (جملة/قطاعي، ضمان/خارج الضمان، لكل فني)، والشمعات المتأخرة
 * التي فات ميعادها ولم تُغيَّر، والمستحقة خلال الفترة. كل قسم استعلام تجميعي واحد.
 */
class MaintenanceReport
{
    public const MAX_ROWS = 1000;

    /** الصافي المستحق من العميل لكل شمعة: صفر في الضمان، وإلا السعر بعد الخصم */
    private const NET = 'CASE WHEN under_warranty = 1 THEN 0 WHEN price - discount > 0 THEN price - discount ELSE 0 END';

    /** @return array<string, float|int> */
    public function summary(ReportPeriod $period, ?int $technicianId): array
    {
        $done = $this->serviced($period, $technicianId)
            ->toBase()
            ->selectRaw('COUNT(*) as candles')
            ->selectRaw('COUNT(DISTINCT sales_contract_id) as contracts')
            ->selectRaw('SUM(CASE WHEN service_type = ? THEN 1 ELSE 0 END) as package', [MaintenanceType::Package->value])
            ->selectRaw('SUM(CASE WHEN service_type = ? THEN 1 ELSE 0 END) as per_item', [MaintenanceType::PerItem->value])
            ->selectRaw('SUM(CASE WHEN under_warranty = 1 THEN 1 ELSE 0 END) as warranty')
            ->selectRaw('COALESCE(SUM('.self::NET.'), 0) as revenue')
            ->selectRaw('COALESCE(SUM(technician_commission), 0) as commission')
            ->first();

        $today = CarbonImmutable::today()->toDateString();
        $pending = $this->pending($technicianId)
            ->toBase()
            ->selectRaw('COUNT(*) as candles')
            ->selectRaw('SUM(CASE WHEN replacement_date < ? THEN 1 ELSE 0 END) as late', [$today])
            ->selectRaw('COUNT(DISTINCT CASE WHEN replacement_date < ? THEN sales_contract_id END) as late_contracts', [$today])
            ->selectRaw('SUM(CASE WHEN replacement_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as due', $period->bounds())
            ->selectRaw('COUNT(DISTINCT CASE WHEN replacement_date BETWEEN ? AND ? THEN sales_contract_id END) as due_contracts', $period->bounds())
            ->first();

        return [
            'candles' => (int) $done->candles,
            'contracts' => (int) $done->contracts,
            'package' => (int) $done->package,
            'per_item' => (int) $done->per_item,
            'warranty' => (int) $done->warranty,
            'revenue' => (float) $done->revenue,
            'commission' => (float) $done->commission,
            'pending' => (int) $pending->candles,
            'late' => (int) $pending->late,
            'late_contracts' => (int) $pending->late_contracts,
            'due' => (int) $pending->due,
            'due_contracts' => (int) $pending->due_contracts,
        ];
    }

    /**
     * أداء كل فني في الفترة: الشمعات والعملاء والجملة/القطاعي والإيراد والعمولة.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function byTechnician(ReportPeriod $period, ?int $technicianId): Collection
    {
        $rows = $this->serviced($period, $technicianId)
            ->toBase()
            ->select('technician_id')
            ->selectRaw('COUNT(*) as candles, COUNT(DISTINCT sales_contract_id) as contracts')
            ->selectRaw('SUM(CASE WHEN service_type = ? THEN 1 ELSE 0 END) as package', [MaintenanceType::Package->value])
            ->selectRaw('SUM(CASE WHEN under_warranty = 1 THEN 1 ELSE 0 END) as warranty')
            ->selectRaw('COALESCE(SUM('.self::NET.'), 0) as revenue, COALESCE(SUM(technician_commission), 0) as commission')
            ->groupBy('technician_id')
            ->orderByDesc('candles')
            ->get();

        $names = User::query()->whereKey($rows->pluck('technician_id')->filter())->pluck('name', 'id');

        return $rows->map(fn ($row) => [
            'name' => $names[$row->technician_id] ?? 'بدون فني',
            'candles' => (int) $row->candles,
            'contracts' => (int) $row->contracts,
            'package' => (int) $row->package,
            'per_item' => (int) $row->candles - (int) $row->package,
            'warranty' => (int) $row->warranty,
            'revenue' => (float) $row->revenue,
            'commission' => (float) $row->commission,
        ]);
    }

    /**
     * عدد ما تم من كل شمعة (الأولى…السابعة) جملة وقطاعي.
     *
     * @return Collection<int, object{product_name: string, package: int, per_item: int, total: int}>
     */
    public function byCandle(ReportPeriod $period, ?int $technicianId): Collection
    {
        return $this->serviced($period, $technicianId)
            ->toBase()
            ->select('product_name')
            ->selectRaw('SUM(CASE WHEN service_type = ? THEN 1 ELSE 0 END) as package', [MaintenanceType::Package->value])
            ->selectRaw('SUM(CASE WHEN service_type = ? THEN 1 ELSE 0 END) as per_item', [MaintenanceType::PerItem->value])
            ->selectRaw('COUNT(*) as total')
            ->groupBy('product_name')
            ->orderBy('product_name')
            ->get();
    }

    /**
     * كشف ما تم في الفترة: لكل شمعة العميل والقطعة والفني والسعر والضمان. يُجلب صف زائد لمعرفة القطع.
     *
     * @return Collection<int, SalesContractMaintenanceItem>
     */
    public function done(ReportPeriod $period, ?int $technicianId): Collection
    {
        return $this->serviced($period, $technicianId)
            ->with([
                'contract:id,code,customer_id',
                'contract.customer:id,code,name,governorate,center,village',
                'product:id,name',
                'technician:id,name',
            ])
            ->orderBy('serviced_at')
            ->orderBy('sales_contract_id')
            ->orderBy('id')
            ->limit(self::MAX_ROWS + 1)
            ->get();
    }

    /**
     * عقود بشمعات متأخرة (فات ميعادها ولم تُغيَّر) أو مستحقة خلال الفترة: سطر لكل عقد بعدد شمعاته وأقرب ميعاد.
     *
     * @return Collection<int, array{contract: SalesContract, candles: int, oldest: Carbon, labels: string}>
     */
    public function pendingByContract(ReportPeriod $period, ?int $technicianId, bool $late): Collection
    {
        $today = CarbonImmutable::today()->toDateString();
        $candles = $this->pending($technicianId)
            ->when($late, fn (Builder $query) => $query->where('replacement_date', '<', $today))
            ->when(! $late, fn (Builder $query) => $query->whereBetween('replacement_date', $period->bounds()))
            ->orderBy('replacement_date')
            ->limit(self::MAX_ROWS * 7)
            ->get(['id', 'sales_contract_id', 'product_name', 'replacement_date', 'technician_id'])
            ->groupBy('sales_contract_id');

        $contracts = SalesContract::query()
            ->whereKey($candles->keys()->take(self::MAX_ROWS + 1))
            ->with(['customer:id,code,name,governorate,center,village', 'customer.primaryPhone:id,customer_id,phone', 'installer:id,name'])
            ->get(['id', 'code', 'customer_id', 'installer_id'])
            ->keyBy('id');

        return $contracts
            ->map(fn (SalesContract $contract) => [
                'contract' => $contract,
                'candles' => $candles[$contract->id]->count(),
                'oldest' => $candles[$contract->id]->min('replacement_date'),
                'labels' => $candles[$contract->id]->pluck('product_name')->join('، '),
            ])
            ->sortBy('oldest')
            ->values();
    }

    private function serviced(ReportPeriod $period, ?int $technicianId): Builder
    {
        return SalesContractMaintenanceItem::query()
            ->whereHas('contract')
            ->whereBetween('serviced_at', $period->bounds())
            ->when($technicianId, fn (Builder $query) => $query->where('technician_id', $technicianId));
    }

    private function pending(?int $technicianId): Builder
    {
        return SalesContractMaintenanceItem::query()
            ->whereHas('contract')
            ->pending()
            ->when($technicianId, fn (Builder $query) => $query->where('technician_id', $technicianId));
    }
}
