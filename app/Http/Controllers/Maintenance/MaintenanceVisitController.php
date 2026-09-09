<?php

namespace App\Http\Controllers\Maintenance;

use App\Domain\Customers\Models\Customer;
use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Models\EmployeeStock;
use App\Domain\Inventory\Models\StockBalance;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Maintenance\Enums\WarrantyStatus;
use App\Domain\Maintenance\Models\MaintenanceVisit;
use App\Domain\Maintenance\Services\MaintenanceVisitService;
use App\Domain\Products\Models\Product;
use App\Domain\Sales\Enums\StockSource;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Maintenance\MaintenanceVisitRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MaintenanceVisitController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(private readonly MaintenanceVisitService $visits) {}

    public function index(Request $request): View
    {
        $visits = MaintenanceVisit::query()
            ->with(['contract:id,code,customer_id', 'contract.customer:id,name,code', 'product:id,name', 'technician:id,name'])
            ->search($request->input('q'))
            ->applyFilters($request->only(['customer_id', 'warranty_status']))
            ->applySort($request->input('sort'), $request->input('dir'))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.maintenance-visits.index', [
            'visits' => $visits,
            'customers' => Customer::query()->orderBy('name')->pluck('name', 'id')->all(),
            'warrantyStatuses' => WarrantyStatus::options(),
        ]);
    }

    public function create(): View
    {
        return view('pages.maintenance-visits.create', $this->formOptions());
    }

    public function store(MaintenanceVisitRequest $request): RedirectResponse
    {
        try {
            $visit = $this->visits->create($request->toData());
        } catch (InsufficientStockException $exception) {
            return back()->withInput()->withErrors(['quantity' => $exception->getMessage()]);
        }

        return redirect()
            ->route('contracts.show', $visit->sales_contract_id)
            ->with('success', 'تم تسجيل زيارة الصيانة وخصم القطعة من المخزون بنجاح.');
    }

    public function destroy(MaintenanceVisit $visit): RedirectResponse
    {
        $this->visits->delete($visit);

        return redirect()
            ->route('maintenance-visits.index')
            ->with('success', 'تم حذف زيارة الصيانة وإعادة القطعة إلى مصدرها.');
    }

    private function formOptions(): array
    {
        $contracts = SalesContract::query()
            ->with(['customer:id,name', 'installer:id,name', 'salesRep:id,name', 'items:id,sales_contract_id,product_name'])
            ->orderByDesc('contract_date')
            ->get(['id', 'code', 'customer_id', 'installer_id', 'sales_rep_id', 'warehouse_id', 'installation_date']);

        $contractOptions = $contracts->mapWithKeys(
            fn (SalesContract $contract) => [$contract->id => "{$contract->code} — {$contract->customer?->name}"],
        )->all();

        $contractsMeta = $contracts->mapWithKeys(fn (SalesContract $contract) => [
            $contract->id => [
                'customer' => $contract->customer?->name,
                'device' => $contract->items->pluck('product_name')->join('، '),
                'installationDate' => $contract->installation_date?->format('Y-m-d'),
                'installerId' => (string) ($contract->installer_id ?? ''),
                'salesRepId' => (string) ($contract->sales_rep_id ?? ''),
                'warehouseId' => (string) ($contract->warehouse_id ?? ''),
            ],
        ])->all();

        return [
            'contractOptions' => $contractOptions,
            'contractsMeta' => $contractsMeta,
            'products' => Product::query()->orderBy('name')->pluck('name', 'id')->all(),
            'warehouses' => Warehouse::options(),
            'technicians' => User::query()->where('role', 'فني تركيب')->orderBy('name')->pluck('name', 'id')->all(),
            'salesReps' => User::query()->where('role', 'مندوب مبيعات')->orderBy('name')->pluck('name', 'id')->all(),
            'stockSources' => StockSource::options(),
            'warrantyStatuses' => WarrantyStatus::options(),
            'availability' => $this->availabilityMatrix(),
            'employeeAvailability' => $this->employeeAvailabilityMatrix(),
        ];
    }

    /** @return array<int, array<int, int>> */
    private function availabilityMatrix(): array
    {
        return StockBalance::query()
            ->get(['warehouse_id', 'product_id', 'quantity'])
            ->groupBy('warehouse_id')
            ->map(fn ($balances) => $balances->pluck('quantity', 'product_id')->all())
            ->all();
    }

    /** @return array<int, array<int, int>> */
    private function employeeAvailabilityMatrix(): array
    {
        return EmployeeStock::query()
            ->get(['user_id', 'product_id', 'quantity'])
            ->groupBy('user_id')
            ->map(fn ($balances) => $balances->pluck('quantity', 'product_id')->all())
            ->all();
    }
}
