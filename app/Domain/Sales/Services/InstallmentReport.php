<?php

namespace App\Domain\Sales\Services;

use App\Domain\Reports\Data\ReportPeriod;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Sales\Models\SalesContractInstallment;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * تقرير الأقساط المطبوع: التحصيل وتوزيعه على المناطق والمحصّلين، والمتأخرات، وأول قسط مع المقدم،
 * والعقود التي انتهت أقساطها. كل قسم عدد ثابت من الاستعلامات التجميعية مهما كثرت الأقساط.
 *
 * الفلاتر: governorate / center / village (منطقة العميل) و collected_by_id (المحصّل — يخص أقسام التحصيل فقط).
 */
class InstallmentReport
{
    /** حد أقصى لصفوف كل كشف مطبوع حتى لا تُحمَّل كل الأقساط في الذاكرة */
    public const MAX_ROWS = 1000;

    public const AREA_LEVELS = ['governorate' => 'المحافظة', 'center' => 'المركز', 'village' => 'القرية'];

    private const UNKNOWN_AREA = 'غير محدد';

    private const CUSTOMER_COLUMNS = 'contract.customer:id,code,name,governorate,center,village';

    /**
     * أرقام الملخص: التحصيل في الفترة، والمستحق فيها ونسبة تحصيله، والمتأخرات حتى نهايتها.
     *
     * @param  array<string, string>  $filters
     * @return array<string, float|int>
     */
    public function summary(ReportPeriod $period, array $filters): array
    {
        $collected = $this->collectedIn($period, $filters)
            ->toBase()
            ->selectRaw('COUNT(*) as installments')
            ->selectRaw('COUNT(DISTINCT sales_contracts.customer_id) as customers')
            ->selectRaw('COALESCE(SUM(sales_contract_installments.paid_amount), 0) as amount')
            ->first();

        // المستحق في الفترة يشمل ما حُصِّل منه وما لم يُحصَّل، فلا يتقيد بالمحصّل
        $due = $this->scoped($filters)
            ->whereBetween('sales_contract_installments.due_date', $period->bounds())
            ->toBase()
            ->selectRaw('COALESCE(SUM(sales_contract_installments.amount), 0) as amount')
            ->selectRaw('COALESCE(SUM(CASE WHEN sales_contract_installments.paid_at IS NOT NULL THEN sales_contract_installments.amount ELSE 0 END), 0) as paid')
            ->first();

        $overdue = $this->overdueQuery($period, $filters)
            ->toBase()
            ->selectRaw('COUNT(*) as installments')
            ->selectRaw('COUNT(DISTINCT sales_contracts.id) as contracts')
            ->selectRaw('COALESCE(SUM(sales_contract_installments.amount), 0) as amount')
            ->first();

        return [
            'collected_installments' => (int) $collected->installments,
            'collected_customers' => (int) $collected->customers,
            'collected_amount' => (float) $collected->amount,
            'due_amount' => (float) $due->amount,
            'due_paid' => (float) $due->paid,
            'overdue_installments' => (int) $overdue->installments,
            'overdue_contracts' => (int) $overdue->contracts,
            'overdue_amount' => (float) $overdue->amount,
        ];
    }

    /**
     * التحصيل مجمّعًا حسب المنطقة ثم المحصّل داخلها: عدد العملاء والأقساط والمبلغ.
     *
     * @param  array<string, string>  $filters
     * @return Collection<int, array{area: string, collectors: Collection<int, array{name: string, customers: int, installments: int, amount: float}>, customers: int, installments: int, amount: float}>
     */
    public function collectionsByArea(ReportPeriod $period, array $filters, string $level): Collection
    {
        $area = $this->areaExpression($level);

        $rows = $this->collectedIn($period, $filters)
            ->toBase()
            ->selectRaw("{$area} as area")
            ->addSelect('sales_contract_installments.collected_by_id')
            ->selectRaw('COUNT(DISTINCT sales_contracts.customer_id) as customers')
            ->selectRaw('COUNT(*) as installments')
            ->selectRaw('COALESCE(SUM(sales_contract_installments.paid_amount), 0) as amount')
            ->groupByRaw($area)
            ->groupBy('sales_contract_installments.collected_by_id')
            ->get();

        // العميل قد يدفع لأكثر من محصّل، فعدد عملاء المنطقة يُحسب مستقلًا لا كمجموع عملاء محصّليها
        $areaCustomers = $this->collectedIn($period, $filters)
            ->toBase()
            ->selectRaw("{$area} as area")
            ->selectRaw('COUNT(DISTINCT sales_contracts.customer_id) as customers')
            ->groupByRaw($area)
            ->pluck('customers', 'area');

        $names = User::query()->whereKey($rows->pluck('collected_by_id')->filter()->unique())->pluck('name', 'id');

        return $rows
            ->groupBy('area')
            ->map(function (Collection $collectors, string $area) use ($names, $areaCustomers) {
                $collectors = $collectors
                    ->map(fn ($row) => [
                        'name' => $names[$row->collected_by_id] ?? 'بدون محصّل',
                        'customers' => (int) $row->customers,
                        'installments' => (int) $row->installments,
                        'amount' => (float) $row->amount,
                    ])
                    ->sortByDesc('amount')
                    ->values();

                return [
                    'area' => $area,
                    'collectors' => $collectors,
                    'customers' => (int) ($areaCustomers[$area] ?? 0),
                    'installments' => $collectors->sum('installments'),
                    'amount' => $collectors->sum('amount'),
                ];
            })
            ->sortByDesc('amount')
            ->values();
    }

    /**
     * كشف التحصيلات مرتّبًا بالمنطقة ثم المحصّل ثم التاريخ. يُجلب صف زائد عن الحد لمعرفة إن كان مقطوعًا.
     *
     * @param  array<string, string>  $filters
     * @return Collection<int, SalesContractInstallment>
     */
    public function collections(ReportPeriod $period, array $filters, string $level): Collection
    {
        return $this->collectedIn($period, $filters)
            ->select('sales_contract_installments.*')
            ->with([self::CUSTOMER_COLUMNS, 'contract:id,code,customer_id', 'collectedBy:id,name', 'splitFrom:id,number'])
            ->orderByRaw($this->areaExpression($level))
            ->orderBy('sales_contract_installments.collected_by_id')
            ->orderBy('sales_contract_installments.paid_at')
            ->orderBy('sales_contract_installments.id')
            ->limit(self::MAX_ROWS + 1)
            ->get();
    }

    /**
     * المتأخرون: عقد لكل صف بأقساطه المتأخرة (رقم كل قسط وقيمته) وإجماليها، الأقدم تأخرًا أولًا.
     * العقود المحوّلة للشؤون القانونية تخرج من المتأخرات العادية.
     *
     * @param  array<string, string>  $filters
     * @return Collection<int, array{contract: SalesContract, installments: Collection<int, SalesContractInstallment>, amount: float, oldest: Carbon}>
     */
    public function overdue(ReportPeriod $period, array $filters): Collection
    {
        $groups = $this->overdueQuery($period, $filters)
            ->toBase()
            ->select('sales_contracts.id as contract_id')
            ->selectRaw('MIN(sales_contract_installments.due_date) as oldest')
            ->groupBy('sales_contracts.id')
            ->orderBy('oldest')
            ->orderBy('sales_contracts.id')
            ->limit(self::MAX_ROWS + 1)
            ->get();

        $contractIds = $groups->pluck('contract_id');

        $installments = $this->overdueQuery($period, $filters)
            ->whereIn('sales_contracts.id', $contractIds)
            ->select('sales_contract_installments.*')
            ->with('splitFrom:id,number')
            ->orderBy('sales_contract_installments.due_date')
            ->orderBy('sales_contract_installments.number')
            ->get()
            ->groupBy('sales_contract_id');

        $contracts = SalesContract::query()
            ->whereKey($contractIds)
            ->with(['customer:id,code,name,governorate,center,village', 'customer.primaryPhone:id,customer_id,phone', 'installer:id,name'])
            ->get(['id', 'code', 'customer_id', 'installer_id', 'installments_count', 'remaining'])
            ->keyBy('id');

        return $groups->map(fn ($group) => [
            'contract' => $contracts[$group->contract_id],
            'installments' => $installments[$group->contract_id],
            'amount' => (float) $installments[$group->contract_id]->sum('amount'),
            'oldest' => Carbon::parse($group->oldest),
        ]);
    }

    /**
     * عملاء بدأوا السداد: القسط الأول (لا جزء متبقٍّ منه) حُصِّل في الفترة، مع المقدم المدفوع وقت التعاقد.
     * المقدم = إجمالي العقد − مجموع أقساطه، لأن الدفع الجزئي يقسم القسط دون أن يغيّر مجموع الأقساط.
     *
     * @param  array<string, string>  $filters
     * @return Collection<int, SalesContractInstallment>
     */
    public function firstPayments(ReportPeriod $period, array $filters): Collection
    {
        return $this->collectedIn($period, $filters)
            ->where('sales_contract_installments.number', 1)
            ->whereNull('sales_contract_installments.split_from_id')
            ->select(['sales_contract_installments.*', 'sales_contracts.total as contract_total'])
            ->addSelect([
                'scheduled_total' => SalesContractInstallment::query()
                    ->from('sales_contract_installments as schedule')
                    ->whereColumn('schedule.sales_contract_id', 'sales_contracts.id')
                    ->selectRaw('COALESCE(SUM(schedule.amount), 0)'),
            ])
            ->with([self::CUSTOMER_COLUMNS, 'contract:id,code,customer_id,contract_date', 'collectedBy:id,name'])
            ->orderBy('sales_contract_installments.paid_at')
            ->orderBy('sales_contract_installments.id')
            ->limit(self::MAX_ROWS + 1)
            ->get()
            ->each(fn (SalesContractInstallment $installment) => $installment->setAttribute(
                'down_payment',
                max(0.0, round((float) $installment->contract_total - (float) $installment->scheduled_total, 2)),
            ));
    }

    /**
     * عقود انتهت أقساطها في الفترة: كل أقساطها محصّلة وآخر تحصيل وقع داخل الفترة.
     *
     * @param  array<string, string>  $filters
     * @return Collection<int, array{contract: SalesContract, payments: int, paid: float, last_paid_at: Carbon}>
     */
    public function finished(ReportPeriod $period, array $filters): Collection
    {
        [$from, $to] = $period->bounds();

        $groups = $this->scoped($filters)
            ->toBase()
            ->select('sales_contracts.id as contract_id')
            ->selectRaw('COUNT(*) as payments')
            ->selectRaw('COALESCE(SUM(sales_contract_installments.paid_amount), 0) as paid')
            ->selectRaw('MAX(sales_contract_installments.paid_at) as last_paid_at')
            ->groupBy('sales_contracts.id')
            ->havingRaw('SUM(CASE WHEN sales_contract_installments.paid_at IS NULL THEN 1 ELSE 0 END) = 0')
            ->havingRaw('MAX(sales_contract_installments.paid_at) BETWEEN ? AND ?', [$from, $to])
            ->orderBy('last_paid_at')
            ->orderBy('sales_contracts.id')
            ->limit(self::MAX_ROWS + 1)
            ->get();

        $contracts = SalesContract::query()
            ->whereKey($groups->pluck('contract_id'))
            ->with('customer:id,code,name,governorate,center,village')
            ->get(['id', 'code', 'customer_id', 'contract_date', 'total', 'installments_count'])
            ->keyBy('id');

        return $groups->map(fn ($group) => [
            'contract' => $contracts[$group->contract_id],
            'payments' => (int) $group->payments,
            'paid' => (float) $group->paid,
            'last_paid_at' => Carbon::parse($group->last_paid_at),
        ]);
    }

    /** آخر ميعاد استحقاق يُعد متأخرًا: أمس، أو نهاية الفترة لو انتهت قبل ذلك */
    public function overdueCutoff(ReportPeriod $period): string
    {
        return min($period->toDate(), today()->subDay()->toDateString());
    }

    /** المتأخر = لم يُحصَّل حتى الآن وميعاده لا يتجاوز overdueCutoff */
    private function overdueQuery(ReportPeriod $period, array $filters): Builder
    {
        return $this->scoped($filters)
            ->whereNull('sales_contract_installments.paid_at')
            ->whereNull('sales_contracts.legal_referred_at')
            ->whereDate('sales_contract_installments.due_date', '<=', $this->overdueCutoff($period));
    }

    private function collectedIn(ReportPeriod $period, array $filters): Builder
    {
        return $this->scoped($filters)
            ->whereBetween('sales_contract_installments.paid_at', $period->bounds())
            ->when(
                $filters['collected_by_id'] ?? null,
                fn (Builder $query, $collectorId) => $query->where('sales_contract_installments.collected_by_id', $collectorId),
            );
    }

    /**
     * أقساط العقود القائمة (غير المحذوفة) مربوطة بالعقد والعميل، مفلترة بمنطقة العميل.
     * بلا أعمدة محددة: الاستعلامات التجميعية تختار أعمدتها، وجلب الموديلات يختار sales_contract_installments.*
     * حتى لا تتداخل أعمدة الجداول المربوطة (id مثلًا).
     *
     * @param  array<string, string>  $filters
     */
    private function scoped(array $filters): Builder
    {
        $query = SalesContractInstallment::query()
            ->join('sales_contracts', 'sales_contracts.id', '=', 'sales_contract_installments.sales_contract_id')
            ->join('customers', 'customers.id', '=', 'sales_contracts.customer_id')
            ->whereNull('sales_contracts.deleted_at');

        foreach (array_keys(self::AREA_LEVELS) as $column) {
            $query->when($filters[$column] ?? null, fn (Builder $query, string $value) => $query->where("customers.{$column}", $value));
        }

        return $query;
    }

    /** $level مقيّد بمفاتيح AREA_LEVELS فلا يدخل الاستعلام نص من المستخدم */
    private function areaExpression(string $level): string
    {
        $column = array_key_exists($level, self::AREA_LEVELS) ? $level : 'center';

        return "COALESCE(NULLIF(customers.{$column}, ''), '".self::UNKNOWN_AREA."')";
    }
}
