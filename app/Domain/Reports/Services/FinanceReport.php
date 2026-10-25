<?php

namespace App\Domain\Reports\Services;

use App\Domain\Complaints\Models\Complaint;
use App\Domain\Complaints\Models\ComplaintPart;
use App\Domain\Gifts\Models\Gift;
use App\Domain\Inventory\Models\PurchaseOrder;
use App\Domain\Payroll\Models\Payroll;
use App\Domain\Payroll\Models\PayrollPayment;
use App\Domain\Reports\Data\ReportPeriod;
use App\Domain\Sales\Enums\ContractKind;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Sales\Models\SalesContractInstallment;
use App\Domain\Sales\Models\SalesContractMaintenanceItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * حسابات الشركة لفترة: كل جنيه دخل (مقدمات العقود، تحصيل الأقساط، الصيانة، الشكاوى) وكل جنيه خرج
 * (طلبيات الوارد، المرتبات المصروفة)، والصافي، والمستحقات المعلّقة. الأرقام نقدية: تُحسب بتاريخ
 * الدفع الفعلي لا بتاريخ الاستحقاق. كل بند استعلام تجميعي واحد، والتوزيع الشهري استعلام لكل بند.
 */
class FinanceReport
{
    /** بنود الدخل والمصروفات بترتيب العرض: المفتاح => العنوان */
    public const INCOME = [
        'sale_down_payments' => 'مقدمات ونقدية عقود البيع',
        'maintenance_contracts' => 'عقود الصيانة',
        'installments' => 'تحصيل الأقساط',
        'maintenance_services' => 'خدمات الصيانة (الشمعات)',
        'complaints' => 'الشكاوى (زيارات وقطع غيار)',
    ];

    public const EXPENSES = [
        'purchase_orders' => 'طلبيات الوارد (مشتريات)',
        'payroll' => 'المرتبات المصروفة',
    ];

    /**
     * @return array{income: array<string, float>, expenses: array<string, float>, income_total: float, expenses_total: float, net: float}
     */
    public function totals(ReportPeriod $period): array
    {
        $sums = $this->sources($period)->map(fn (QueryBuilder $query) => (float) $query->sum('amount'));

        $income = collect(self::INCOME)->map(fn (string $label, string $key) => $sums[$key])->all();
        $expenses = collect(self::EXPENSES)->map(fn (string $label, string $key) => $sums[$key])->all();

        return [
            'income' => $income,
            'expenses' => $expenses,
            'income_total' => round(array_sum($income), 2),
            'expenses_total' => round(array_sum($expenses), 2),
            'net' => round(array_sum($income) - array_sum($expenses), 2),
        ];
    }

    /**
     * التدفق شهرًا بشهر داخل الفترة: الدخل والمصروفات والصافي.
     *
     * @return Collection<int, array{month: string, income: float, expenses: float, net: float}>
     */
    public function monthly(ReportPeriod $period): Collection
    {
        $byMonth = $this->sources($period)->map(fn (QueryBuilder $query) => (clone $query)
            ->reorder()
            ->selectRaw('month, SUM(amount) as amount')
            ->groupBy('month')
            ->pluck('amount', 'month'));

        return collect($period->months())->map(function ($month) use ($byMonth) {
            $key = $month->format('Y-m');
            $sum = fn (array $keys) => round(collect($keys)->sum(fn (string $source) => (float) ($byMonth[$source][$key] ?? 0)), 2);
            $income = $sum(array_keys(self::INCOME));
            $expenses = $sum(array_keys(self::EXPENSES));

            return ['month' => $key, 'income' => $income, 'expenses' => $expenses, 'net' => round($income - $expenses, 2)];
        });
    }

    /**
     * مبالغ مستحقة لم تُدفع بعد (لحظية، لا تتقيد بالفترة) + تكلفة الهدايا غير النقدية في الفترة.
     *
     * @return array{receivables: float, legal_receivables: float, payroll_due: float, gifts_cost: float}
     */
    public function outstanding(ReportPeriod $period): array
    {
        $contracts = SalesContract::query()
            ->toBase()
            ->selectRaw('COALESCE(SUM(remaining), 0) as remaining')
            ->selectRaw('COALESCE(SUM(CASE WHEN legal_referred_at IS NOT NULL THEN remaining ELSE 0 END), 0) as legal')
            ->first();

        // المتبقي للموظفين من كشوف شهور الفترة المعتمدة
        $payrollTotal = (float) Payroll::query()->whereBetween('month', $period->bounds())->sum('total');
        $payrollPaid = (float) PayrollPayment::query()
            ->whereHas('payroll', fn (Builder $payroll) => $payroll->whereBetween('month', $period->bounds()))
            ->sum('amount');

        $giftsCost = (float) Gift::query()
            ->join('products', 'products.id', '=', 'gifts.product_id')
            ->whereBetween('gifts.gift_date', $period->bounds())
            ->sum(DB::raw('gifts.quantity * COALESCE(products.cost, 0)'));

        return [
            'receivables' => (float) $contracts->remaining - (float) $contracts->legal,
            'legal_receivables' => (float) $contracts->legal,
            'payroll_due' => round(max(0.0, $payrollTotal - $payrollPaid), 2),
            'gifts_cost' => round($giftsCost, 2),
        ];
    }

    /**
     * مصدر كل بند كاستعلام بعمودين: amount وmonth (YYYY-MM) — نفس الاستعلام يجمع الإجمالي والتوزيع الشهري.
     * SUBSTR على أعمدة التاريخ يعطي «YYYY-MM» في SQLite وMySQL معًا.
     *
     * @return Collection<string, QueryBuilder>
     */
    private function sources(ReportPeriod $period): Collection
    {
        $bounds = $period->bounds();

        // المقدم/النقدي = المدفوع على العقد ناقص ما حُصِّل من أقساطه (في أي وقت)
        $collected = SalesContractInstallment::query()
            ->toBase()
            ->select('sales_contract_id')
            ->selectRaw('SUM(paid_amount) as amount')
            ->whereNotNull('paid_at')
            ->groupBy('sales_contract_id');

        $upfront = fn (ContractKind $kind) => SalesContract::query()
            ->ofKind($kind)
            ->whereBetween('contract_date', $bounds)
            ->leftJoinSub($collected, 'collected', 'collected.sales_contract_id', '=', 'sales_contracts.id')
            ->toBase()
            ->selectRaw('sales_contracts.paid - COALESCE(collected.amount, 0) as amount')
            ->selectRaw('SUBSTR(sales_contracts.contract_date, 1, 7) as month');

        return collect([
            'sale_down_payments' => $upfront(ContractKind::Sale),
            'maintenance_contracts' => $upfront(ContractKind::Maintenance),
            'installments' => SalesContractInstallment::query()
                ->whereHas('contract')
                ->whereBetween('paid_at', $bounds)
                ->toBase()
                ->selectRaw('paid_amount as amount, SUBSTR(paid_at, 1, 7) as month'),
            'maintenance_services' => SalesContractMaintenanceItem::query()
                ->whereBetween('serviced_at', $bounds)
                ->where('under_warranty', false)
                ->toBase()
                ->selectRaw('CASE WHEN price - discount > 0 THEN price - discount ELSE 0 END as amount')
                ->selectRaw('SUBSTR(serviced_at, 1, 7) as month'),
            'complaints' => Complaint::query()
                ->whereBetween('complaint_date', $bounds)
                ->toBase()
                ->selectRaw('visit_fee as amount, SUBSTR(complaint_date, 1, 7) as month')
                ->unionAll(ComplaintPart::query()
                    ->join('complaints', 'complaints.id', '=', 'complaint_parts.complaint_id')
                    ->whereBetween('complaints.complaint_date', $bounds)
                    ->toBase()
                    ->selectRaw('complaint_parts.quantity * complaint_parts.price as amount')
                    ->selectRaw('SUBSTR(complaints.complaint_date, 1, 7) as month')),
            'purchase_orders' => PurchaseOrder::query()
                ->whereBetween('order_date', $bounds)
                ->toBase()
                ->selectRaw('total_amount as amount, SUBSTR(order_date, 1, 7) as month'),
            'payroll' => PayrollPayment::query()
                ->whereBetween('paid_at', $bounds)
                ->toBase()
                ->selectRaw('amount, SUBSTR(paid_at, 1, 7) as month'),
        ])->map(fn (QueryBuilder $query) => DB::query()->fromSub($query, 'source'));
    }
}
