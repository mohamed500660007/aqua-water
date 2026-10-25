<?php

namespace App\Domain\Reports\Services;

use App\Domain\Complaints\Models\Complaint;
use App\Domain\Customers\Models\Customer;
use App\Domain\Inventory\Models\StockBalance;
use App\Domain\Products\Enums\ProductKind;
use App\Domain\Products\Enums\ProductStatus;
use App\Domain\Products\Models\Product;
use App\Domain\Reports\Data\ReportPeriod;
use App\Domain\Sales\Enums\ContractKind;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Sales\Models\SalesContractInstallment;
use App\Domain\Sales\Models\SalesContractItem;
use App\Domain\Sales\Models\SalesContractMaintenanceItem;
use App\Domain\Users\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * الملخص التنفيذي لإدارة الشركة: مؤشرات الفترة مقارنة بالفترة السابقة بنفس الطول، وترتيب
 * الفريق والمنتجات، ومؤشرات تحتاج انتباهًا الآن. كل مؤشر استعلام تجميعي واحد.
 */
class ExecutiveReport
{
    private const TOP = 5;

    public function __construct(private readonly FinanceReport $finance) {}

    /**
     * الفترة السابقة مباشرة — أساس المقارنة: شهور كاملة تُقارن بنفس عدد الشهور قبلها
     * (أكتوبر بسبتمبر، والسنة بالسنة)، وغيرها بنفس عدد الأيام.
     */
    public function previousPeriod(ReportPeriod $period): ReportPeriod
    {
        $wholeMonths = $period->from->isSameDay($period->from->startOfMonth())
            && $period->to->isSameDay($period->to->endOfMonth());

        if ($wholeMonths) {
            $months = count($period->months());
            $from = $period->from->subMonthsNoOverflow($months);

            return new ReportPeriod($from, $period->from->subDay());
        }

        $days = (int) $period->from->diffInDays($period->to) + 1;
        $to = $period->from->subDay();

        return new ReportPeriod($to->subDays($days - 1), $to);
    }

    /**
     * المؤشرات الرئيسية لفترة واحدة.
     *
     * @return array<string, float|int>
     */
    public function kpis(ReportPeriod $period): array
    {
        $bounds = $period->bounds();

        $sales = SalesContract::query()
            ->ofKind(ContractKind::Sale)
            ->whereBetween('contract_date', $bounds)
            ->toBase()
            ->selectRaw('COUNT(*) as contracts, COALESCE(SUM(total - interest), 0) as value, COALESCE(SUM(interest), 0) as interest')
            ->first();

        $maintenanceContracts = SalesContract::query()
            ->ofKind(ContractKind::Maintenance)
            ->whereBetween('contract_date', $bounds)
            ->toBase()
            ->selectRaw('COUNT(*) as contracts, COALESCE(SUM(total), 0) as value')
            ->first();

        $devices = (int) SalesContractItem::query()
            ->join('sales_contracts', 'sales_contracts.id', '=', 'sales_contract_items.sales_contract_id')
            ->join('products', 'products.id', '=', 'sales_contract_items.product_id')
            ->whereNull('sales_contracts.deleted_at')
            ->whereBetween('sales_contracts.contract_date', $bounds)
            ->where('products.kind', ProductKind::Device->value)
            ->sum('sales_contract_items.quantity');

        $due = SalesContractInstallment::query()
            ->whereHas('contract')
            ->whereBetween('due_date', $bounds)
            ->toBase()
            ->selectRaw('COALESCE(SUM(amount), 0) as amount')
            ->selectRaw('COALESCE(SUM(CASE WHEN paid_at IS NOT NULL THEN amount ELSE 0 END), 0) as paid')
            ->first();

        $candles = SalesContractMaintenanceItem::query()
            ->whereBetween('serviced_at', $bounds)
            ->toBase()
            ->selectRaw('COUNT(*) as candles, COUNT(DISTINCT sales_contract_id) as contracts')
            ->first();

        $complaints = Complaint::query()
            ->whereBetween('complaint_date', $bounds)
            ->toBase()
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(CASE WHEN resolved = 1 THEN 1 ELSE 0 END), 0) as resolved')
            ->first();

        $finance = $this->finance->totals($period);

        return [
            'sales_contracts' => (int) $sales->contracts,
            'sales_value' => (float) $sales->value,
            'interest' => (float) $sales->interest,
            'devices_sold' => $devices,
            'maintenance_contracts' => (int) $maintenanceContracts->contracts,
            'maintenance_contracts_value' => (float) $maintenanceContracts->value,
            'new_customers' => Customer::query()->whereBetween('registered_at', $bounds)->count(),
            'collections' => $finance['income']['installments'],
            'due' => (float) $due->amount,
            'collection_rate' => (float) $due->amount > 0 ? round((float) $due->paid * 100 / (float) $due->amount, 1) : 0.0,
            'candles_serviced' => (int) $candles->candles,
            'maintenance_customers' => (int) $candles->contracts,
            'complaints' => (int) $complaints->total,
            'complaints_resolved' => (int) $complaints->resolved,
            'income' => $finance['income_total'],
            'expenses' => $finance['expenses_total'],
            'net' => $finance['net'],
        ];
    }

    /**
     * أفضل المندوبين والمحصّلين والفنيين والمنتجات في الفترة.
     *
     * @return array{sales_reps: Collection, collectors: Collection, technicians: Collection, products: Collection}
     */
    public function leaders(ReportPeriod $period): array
    {
        $bounds = $period->bounds();

        $salesReps = SalesContract::query()
            ->ofKind(ContractKind::Sale)
            ->whereBetween('contract_date', $bounds)
            ->whereNotNull('sales_rep_id')
            ->toBase()
            ->select('sales_rep_id as user_id')
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total - interest), 0) as amount')
            ->groupBy('sales_rep_id')
            ->orderByDesc('amount')
            ->limit(self::TOP)
            ->get();

        $collectors = SalesContractInstallment::query()
            ->whereHas('contract')
            ->whereBetween('paid_at', $bounds)
            ->whereNotNull('collected_by_id')
            ->toBase()
            ->select('collected_by_id as user_id')
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(paid_amount), 0) as amount')
            ->groupBy('collected_by_id')
            ->orderByDesc('amount')
            ->limit(self::TOP)
            ->get();

        $technicians = SalesContractMaintenanceItem::query()
            ->whereBetween('serviced_at', $bounds)
            ->whereNotNull('technician_id')
            ->toBase()
            ->select('technician_id as user_id')
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(technician_commission), 0) as amount')
            ->groupBy('technician_id')
            ->orderByDesc('count')
            ->limit(self::TOP)
            ->get();

        $names = User::query()
            ->whereKey($salesReps->pluck('user_id')->merge($collectors->pluck('user_id'))->merge($technicians->pluck('user_id'))->unique())
            ->pluck('name', 'id');

        $named = fn (Collection $rows) => $rows->map(fn ($row) => [
            'name' => $names[$row->user_id] ?? '—',
            'count' => (int) $row->count,
            'amount' => (float) $row->amount,
        ]);

        $products = SalesContractItem::query()
            ->join('sales_contracts', 'sales_contracts.id', '=', 'sales_contract_items.sales_contract_id')
            ->whereNull('sales_contracts.deleted_at')
            ->whereBetween('sales_contracts.contract_date', $bounds)
            ->toBase()
            ->select('sales_contract_items.product_name as name')
            ->selectRaw('COALESCE(SUM(sales_contract_items.quantity), 0) as count, COALESCE(SUM(sales_contract_items.total), 0) as amount')
            ->groupBy('sales_contract_items.product_name')
            ->orderByDesc('amount')
            ->limit(self::TOP)
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'count' => (int) $row->count, 'amount' => (float) $row->amount]);

        return [
            'sales_reps' => $named($salesReps),
            'collectors' => $named($collectors),
            'technicians' => $named($technicians),
            'products' => $products,
        ];
    }

    /**
     * مؤشرات تحتاج تدخّل الإدارة الآن (لحظية، لا تتقيد بالفترة).
     *
     * @return array<string, float|int>
     */
    public function alerts(): array
    {
        $today = CarbonImmutable::today()->toDateString();

        $overdue = SalesContractInstallment::query()
            ->whereHas('contract', fn (Builder $contract) => $contract->whereNull('legal_referred_at'))
            ->whereNull('paid_at')
            ->where('due_date', '<', $today)
            ->toBase()
            ->selectRaw('COUNT(DISTINCT sales_contract_id) as contracts, COALESCE(SUM(amount), 0) as amount')
            ->first();

        $legal = SalesContract::query()
            ->whereNotNull('legal_referred_at')
            ->toBase()
            ->selectRaw('COUNT(*) as contracts, COALESCE(SUM(remaining), 0) as amount')
            ->first();

        $inStock = StockBalance::query()->where('quantity', '>', 0)->select('product_id');

        return [
            'overdue_contracts' => (int) $overdue->contracts,
            'overdue_amount' => (float) $overdue->amount,
            'legal_contracts' => (int) $legal->contracts,
            'legal_amount' => (float) $legal->amount,
            'late_candles' => SalesContractMaintenanceItem::query()
                ->whereHas('contract')
                ->pending()
                ->where('replacement_date', '<', $today)
                ->count(),
            'open_complaints' => Complaint::query()->where('resolved', false)->count(),
            'out_of_stock' => Product::query()
                ->where('status', ProductStatus::Active->value)
                ->whereNotIn('id', $inStock)
                ->count(),
        ];
    }

    /** نسبة التغير عن الفترة السابقة — null لو لا أساس للمقارنة */
    public static function change(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous === 0.0) {
            return null;
        }

        return round(($current - $previous) * 100 / abs($previous), 1);
    }
}
