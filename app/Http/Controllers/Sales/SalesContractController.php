<?php

namespace App\Http\Controllers\Sales;

use App\Domain\Customers\Models\Customer;
use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Maintenance\Models\MaintenancePlan;
use App\Domain\Products\Models\Product;
use App\Domain\Sales\Enums\ContractKind;
use App\Domain\Sales\Enums\ContractType;
use App\Domain\Sales\Enums\StockSource;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Sales\Services\SalesContractReport;
use App\Domain\Sales\Services\SalesContractService;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\SalesContractReportRequest;
use App\Http\Requests\Sales\SalesContractRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SalesContractController extends Controller
{
    private const PER_PAGE = 12;

    public function __construct(
        private readonly SalesContractService $contracts,
        private readonly StockService $stock,
        private readonly EmployeeStockService $employeeStock,
    ) {}

    public function index(Request $request): View
    {
        $contracts = SalesContract::query()
            ->with(['customer:id,name,code'])
            ->withCount('items')
            ->search($request->input('q'))
            ->applyFilters($request->only(['kind', 'type', 'customer_id', 'legal', 'from', 'to']))
            ->applySort($request->input('sort'), $request->input('dir'))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.contracts.index', [
            'contracts' => $contracts,
            'contractsCount' => SalesContract::query()->count(),
            'types' => ContractType::options(),
            'kinds' => ContractKind::options(),
            'customers' => Customer::query()->orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }

    public function report(SalesContractReportRequest $request, SalesContractReport $report): View
    {
        $content = $request->content();
        $contracts = SalesContract::query()
            ->search($request->validated('q'))
            ->applyFilters($request->filters());

        // الأجزاء غير المطلوبة للطباعة لا تُحسب أصلًا
        $withSummary = $content !== 'list';
        $rows = $content !== 'summary'
            ? $report->rows((clone $contracts)->applySort($request->validated('sort'), $request->validated('dir')))
            : collect();

        return view('pages.contracts.report', [
            'period' => $request->period(),
            'content' => $content,
            'summary' => $report->summary($contracts),
            'salesRepsReport' => $withSummary ? $report->bySalesRep($contracts) : collect(),
            'products' => $withSummary ? $report->products($contracts) : collect(),
            'rows' => $rows->take(SalesContractReport::MAX_ROWS),
            'truncated' => $rows->count() > SalesContractReport::MAX_ROWS,
            'search' => $request->validated('q'),
            'filters' => $request->filters(),
            'listQuery' => $request->listQuery(),
            'contents' => SalesContractReportRequest::CONTENTS,
            'sorts' => SalesContractReportRequest::SORTS,
            'kinds' => ContractKind::options(),
            'types' => ContractType::options(),
        ] + $this->peopleOptions());
    }

    public function create(Request $request): View
    {
        $kind = ContractKind::tryFrom((string) $request->query('kind')) ?? ContractKind::Sale;

        return view('pages.contracts.create', ['defaultKind' => $kind->value] + $this->formOptions());
    }

    public function store(SalesContractRequest $request): RedirectResponse
    {
        try {
            $contract = $this->contracts->create($request->toData());
        } catch (InsufficientStockException $exception) {
            return back()->withInput()->withErrors(['items' => $exception->getMessage()]);
        }

        return redirect()
            ->route('contracts.show', $contract)
            ->with('success', "تم إنشاء العقد {$contract->code} بنجاح.");
    }

    public function show(SalesContract $contract): View
    {
        return view('pages.contracts.show', [
            'contract' => $contract->load([
                'customer',
                'salesRep:id,name',
                'installer:id,name',
                'warehouse:id,name',
                'maintenancePlan:id,name',
                'items',
                'installments',
                'maintenanceItems',
            ]),
        ]);
    }

    /** لوحة معاينة جانبية للعقد — تُطلب عند الفتح فقط حتى لا تُحمَّل مع كل صف في القائمة */
    public function panel(SalesContract $contract): View
    {
        return view('pages.contracts.panel', ['contract' => $contract->loadDetails()]);
    }

    public function edit(SalesContract $contract): View
    {
        return view('pages.contracts.edit', [
            'contract' => $contract->load('items'),
        ] + $this->formOptions());
    }

    public function update(SalesContractRequest $request, SalesContract $contract): RedirectResponse
    {
        try {
            $this->contracts->update($contract, $request->toData());
        } catch (InsufficientStockException $exception) {
            return back()->withInput()->withErrors(['items' => $exception->getMessage()]);
        }

        return redirect()
            ->route('contracts.show', $contract)
            ->with('success', 'تم حفظ تعديلات العقد بنجاح.');
    }

    public function referToLegal(Request $request, SalesContract $contract): RedirectResponse
    {
        $validated = $request->validate(['referred_at' => ['nullable', 'date', 'before_or_equal:today']]);

        if ((float) $contract->remaining <= 0) {
            return back()->with('error', 'لا يمكن تحويل عقد مسدَّد بالكامل للشؤون القانونية.');
        }

        $this->contracts->referToLegal($contract, $validated['referred_at'] ?? today()->toDateString());

        return back()->with('success', "تم تحويل العقد {$contract->code} للشؤون القانونية.");
    }

    public function withdrawFromLegal(SalesContract $contract): RedirectResponse
    {
        $this->contracts->withdrawFromLegal($contract);

        return back()->with('success', "تم إلغاء تحويل العقد {$contract->code} من الشؤون القانونية.");
    }

    private function formOptions(): array
    {
        $plans = MaintenancePlan::query()
            ->with(['items:id,maintenance_plan_id,product_id,interval_months,replacement_date', 'items.product:id,name'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return $this->peopleOptions() + [
            'warehouses' => Warehouse::options(),
            'types' => ContractType::options(),
            'kinds' => ContractKind::options(),
            'stockSources' => StockSource::options(),
            'products' => Product::query()->orderBy('name')->get(['id', 'sku', 'name', 'price']),
            'plans' => $plans,
            'availability' => $this->stock->availabilityMatrix(),
            'employeeAvailability' => $this->employeeStock->availabilityMatrix(),
        ];
    }

    /** قوائم العملاء والمندوبين والفنيين — مشتركة بين نموذج العقد وفلاتر التقرير */
    private function peopleOptions(): array
    {
        return [
            'customers' => Customer::query()->orderBy('name')->pluck('name', 'id')->all(),
            'salesReps' => User::query()->whereIn('role', ['مندوب مبيعات', 'مندوبة', 'سكرتارية'])->orderBy('name')->pluck('name', 'id')->all(),
            'installers' => User::query()->where('role', 'فني تركيب')->orderBy('name')->pluck('name', 'id')->all(),
        ];
    }
}
