<?php

namespace App\Domain\Customers\Services;

use App\Domain\Complaints\Models\Complaint;
use App\Domain\Customers\Enums\CustomerOrigin;
use App\Domain\Customers\Enums\CustomerStatus;
use App\Domain\Customers\Models\Customer;
use App\Domain\Reports\Data\ReportPeriod;
use App\Domain\Sales\Models\SalesContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * تقرير العملاء المطبوع. كل الأقسام تُحسب على نفس مجموعة العملاء المفلترة
 * (نفس فلاتر قائمة العملاء)، والفترة تحدد فقط "الجدد" ونشاط العقود والشكاوى.
 */
class CustomerReport
{
    /** حد أقصى لصفوف القائمة المطبوعة حتى لا تُحمَّل قاعدة العملاء كلها في الذاكرة */
    public const MAX_ROWS = 1000;

    /**
     * الأعداد حسب الحالة والنوع + الجدد خلال الفترة — في استعلام تجميعي واحد.
     *
     * matrix: [status => [origin => count]] لجدول توزيع الحالات على نوع العميل.
     *
     * @return array{total: int, new: int, statuses: array<string, int>, origins: array<string, int>, matrix: array<string, array<string, int>>}
     */
    public function summary(Builder $customers, ReportPeriod $period): array
    {
        [$from, $to] = $period->bounds();

        $groups = (clone $customers)
            ->selectRaw('status, origin, COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN registered_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as new_total', [$from, $to])
            ->groupBy('status', 'origin')
            ->toBase()
            ->get();

        $sumWhere = fn (string $column, string $value) => (int) $groups->where($column, $value)->sum('total');

        return [
            'total' => (int) $groups->sum('total'),
            'new' => (int) $groups->sum('new_total'),
            'statuses' => collect(CustomerStatus::cases())
                ->mapWithKeys(fn (CustomerStatus $status) => [$status->value => $sumWhere('status', $status->value)])
                ->all(),
            'origins' => collect(CustomerOrigin::cases())
                ->mapWithKeys(fn (CustomerOrigin $origin) => [$origin->value => $sumWhere('origin', $origin->value)])
                ->all(),
            'matrix' => collect(CustomerStatus::cases())
                ->mapWithKeys(fn (CustomerStatus $status) => [$status->value => collect(CustomerOrigin::cases())
                    ->mapWithKeys(fn (CustomerOrigin $origin) => [$origin->value => (int) $groups
                        ->where('status', $status->value)
                        ->where('origin', $origin->value)
                        ->sum('total')])
                    ->all()])
                ->all(),
        ];
    }

    /**
     * نشاط العملاء خلال الفترة + إجمالي المتبقي عليهم من كل عقودهم.
     *
     * @return array{contracts: int, contracts_total: float, remaining: float, complaints: int}
     */
    public function activity(Builder $customers, ReportPeriod $period): array
    {
        $customerIds = (clone $customers)->select('customers.id');
        $ofCustomers = fn (Builder $contract) => $contract->whereIn('customer_id', $customerIds);

        $contracts = SalesContract::query()
            ->whereIn('customer_id', $customerIds)
            ->toBase()
            ->selectRaw('COALESCE(SUM(remaining), 0) as remaining')
            ->selectRaw('SUM(CASE WHEN contract_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as period_count', $period->bounds())
            ->selectRaw('COALESCE(SUM(CASE WHEN contract_date BETWEEN ? AND ? THEN total ELSE 0 END), 0) as period_total', $period->bounds())
            ->first();

        return [
            'contracts' => (int) ($contracts->period_count ?? 0),
            'contracts_total' => (float) ($contracts->period_total ?? 0),
            'remaining' => (float) ($contracts->remaining ?? 0),
            'complaints' => Complaint::query()
                ->whereBetween('complaint_date', $period->bounds())
                ->whereHas('contract', $ofCustomers)
                ->count(),
        ];
    }

    /** @return Collection<string, int> عدد العملاء لكل محافظة — الأكبر أولًا */
    public function byGovernorate(Builder $customers): Collection
    {
        return (clone $customers)
            ->selectRaw("COALESCE(NULLIF(governorate, ''), 'غير محدد') as governorate, COUNT(*) as total")
            ->groupByRaw("COALESCE(NULLIF(governorate, ''), 'غير محدد')")
            ->orderByDesc('total')
            ->toBase()
            ->pluck('total', 'governorate')
            ->map(fn ($total) => (int) $total);
    }

    /**
     * صفوف القائمة بكل أرقامها محسوبة في نفس الاستعلام (لا استعلام لكل عميل).
     * يُجلب صف زائد عن الحد لمعرفة إن كانت القائمة مقطوعة.
     *
     * @return Collection<int, Customer>
     */
    public function rows(Builder $customers, ReportPeriod $period, ?string $sort = null, ?string $direction = null): Collection
    {
        // الترتيب بالمتبقي يعتمد على عمود withSum المحسوب في نفس الاستعلام، فلا يمر عبر قائمة SORTABLE
        $sorted = $sort === 'remaining'
            ? (clone $customers)->orderBy('remaining_total', $direction === 'asc' ? 'asc' : 'desc')->orderBy('id')
            : (clone $customers)->applySort($sort, $direction);

        return $sorted
            ->select(['id', 'code', 'name', 'governorate', 'center', 'village', 'status', 'origin', 'registered_at'])
            ->with('primaryPhone:id,customer_id,phone')
            ->withCount([
                'contracts',
                'contracts as period_contracts_count' => fn ($query) => $query
                    ->whereBetween('contract_date', $period->bounds()),
                'complaints as period_complaints_count' => fn ($query) => $query
                    ->whereBetween('complaint_date', $period->bounds()),
            ])
            ->withSum('contracts as remaining_total', 'remaining')
            ->limit(self::MAX_ROWS + 1)
            ->get();
    }
}
