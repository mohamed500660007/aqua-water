<?php

namespace App\Http\Controllers\Sales;

use App\Domain\Customers\Models\Customer;
use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Models\EmployeeStock;
use App\Domain\Inventory\Models\StockBalance;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Maintenance\Models\MaintenancePlan;
use App\Domain\Products\Models\Product;
use App\Domain\Sales\Enums\ContractType;
use App\Domain\Sales\Enums\StockSource;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Sales\Services\SalesContractService;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\SalesContractRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SalesContractController extends Controller
{
    private const PER_PAGE = 12;

    public function __construct(private readonly SalesContractService $contracts) {}

    public function index(Request $request): View
    {
        $contracts = SalesContract::query()
            ->with(['customer:id,name,code'])
            ->withCount('items')
            ->search($request->input('q'))
            ->applyFilters($request->only(['type', 'customer_id', 'from', 'to']))
            ->applySort($request->input('sort'), $request->input('dir'))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.contracts.index', [
            'contracts' => $contracts,
            'stats' => $this->stats(),
            'types' => ContractType::options(),
            'customers' => Customer::query()->orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }

    public function create(): View
    {
        return view('pages.contracts.create', $this->formOptions());
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

    public function destroy(SalesContract $contract): RedirectResponse
    {
        $this->contracts->delete($contract);

        return redirect()
            ->route('contracts.index')
            ->with('success', "تم حذف العقد {$contract->code} وإعادة كمياته إلى المخزن.");
    }

    /** ملخص العقود في استعلام تجميعي واحد */
    private function stats(): array
    {
        $summary = SalesContract::query()
            ->selectRaw('COUNT(*) as contracts')
            ->selectRaw('COALESCE(SUM(total), 0) as total')
            ->selectRaw('COALESCE(SUM(remaining), 0) as remaining')
            ->selectRaw('SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) as installments', [ContractType::Installment->value])
            ->first();

        return [
            'contracts' => (int) ($summary->contracts ?? 0),
            'total' => (float) ($summary->total ?? 0),
            'remaining' => (float) ($summary->remaining ?? 0),
            'installments' => (int) ($summary->installments ?? 0),
        ];
    }

    private function formOptions(): array
    {
        $plans = MaintenancePlan::query()
            ->with(['items:id,maintenance_plan_id,product_id,interval_months,replacement_date', 'items.product:id,name'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'customers' => Customer::query()->orderBy('name')->pluck('name', 'id')->all(),
            'salesReps' => User::query()->where('role', 'مندوب مبيعات')->orderBy('name')->pluck('name', 'id')->all(),
            'installers' => User::query()->where('role', 'فني تركيب')->orderBy('name')->pluck('name', 'id')->all(),
            'warehouses' => Warehouse::options(),
            'types' => ContractType::options(),
            'stockSources' => StockSource::options(),
            'products' => Product::query()->orderBy('name')->get(['id', 'sku', 'name', 'price']),
            'plans' => $plans,
            'availability' => $this->availabilityMatrix(),
            'employeeAvailability' => $this->employeeAvailabilityMatrix(),
        ];
    }

    /**
     * أرصدة كل مخزن/منتج لعرض المتاح فور اختيار المنتج داخل النموذج.
     *
     * @return array<int, array<int, int>>
     */
    private function availabilityMatrix(): array
    {
        return StockBalance::query()
            ->get(['warehouse_id', 'product_id', 'quantity'])
            ->groupBy('warehouse_id')
            ->map(fn ($balances) => $balances->pluck('quantity', 'product_id')->all())
            ->all();
    }

    /**
     * ما يحمله كل موظف من منتجات — لعرض المتاح فورًا عند اختيار مصدر البضاعة من مندوب أو فني.
     *
     * @return array<int, array<int, int>>
     */
    private function employeeAvailabilityMatrix(): array
    {
        return EmployeeStock::query()
            ->get(['user_id', 'product_id', 'quantity'])
            ->groupBy('user_id')
            ->map(fn ($balances) => $balances->pluck('quantity', 'product_id')->all())
            ->all();
    }
}
