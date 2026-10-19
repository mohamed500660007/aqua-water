<?php

namespace App\Http\Controllers\Complaints;

use App\Domain\Complaints\Models\Complaint;
use App\Domain\Complaints\Services\ComplaintService;
use App\Domain\Customers\Models\Customer;
use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Products\Models\Product;
use App\Domain\Sales\Enums\StockSource;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Complaints\ComplaintRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly ComplaintService $complaints,
        private readonly StockService $stock,
        private readonly EmployeeStockService $employeeStock,
    ) {}

    public function index(Request $request): View
    {
        $complaints = Complaint::query()
            ->with([
                'contract:id,code,customer_id',
                'contract.customer:id,name,code',
                'contract.items:id,sales_contract_id,product_name',
                'technician:id,name',
                'salesRep:id,name',
                'parts:id,complaint_id,product_id,quantity,price',
                'parts.product:id,name',
            ])
            ->search($request->input('q'))
            ->applyFilters($request->only(['customer_id', 'resolved']))
            ->applySort($request->input('sort'), $request->input('dir'))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.complaints.index', [
            'complaints' => $complaints,
            'customers' => Customer::query()->orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }

    public function create(): View
    {
        return view('pages.complaints.create', $this->formOptions());
    }

    public function store(ComplaintRequest $request): RedirectResponse
    {
        try {
            $this->complaints->create($request->toData());
        } catch (InsufficientStockException $exception) {
            return back()->withInput()->withErrors(['parts' => $exception->getMessage()]);
        }

        return redirect()
            ->route('complaints.index')
            ->with('success', 'تم تسجيل الشكوى بنجاح.');
    }

    public function resolve(Request $request, Complaint $complaint): RedirectResponse
    {
        $resolved = $request->boolean('resolved');

        $this->complaints->setResolved($complaint, $resolved);

        return back()->with('success', $resolved ? 'تم تعليم الشكوى كمحلولة.' : 'أُعيدت الشكوى إلى غير محلولة.');
    }

    public function destroy(Complaint $complaint): RedirectResponse
    {
        $this->complaints->delete($complaint);

        return redirect()
            ->route('complaints.index')
            ->with('success', 'تم حذف الشكوى وإعادة قطع الغيار إلى مصدرها.');
    }

    private function formOptions(): array
    {
        $contracts = SalesContract::query()
            ->with(['customer:id,name', 'items:id,sales_contract_id,product_name'])
            ->orderByDesc('contract_date')
            ->get(['id', 'code', 'customer_id', 'installer_id', 'sales_rep_id', 'warehouse_id']);

        return [
            'contractOptions' => $contracts->mapWithKeys(
                fn (SalesContract $contract) => [$contract->id => "{$contract->code} — {$contract->customer?->name}"],
            )->all(),
            'contractsMeta' => $contracts->mapWithKeys(fn (SalesContract $contract) => [
                $contract->id => [
                    'customer' => $contract->customer?->name,
                    'device' => $contract->items->pluck('product_name')->join('، '),
                    'installerId' => (string) ($contract->installer_id ?? ''),
                    'salesRepId' => (string) ($contract->sales_rep_id ?? ''),
                    'warehouseId' => (string) ($contract->warehouse_id ?? ''),
                ],
            ])->all(),
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'price']),
            'warehouses' => Warehouse::options(),
            'technicians' => User::query()->where('role', 'فني تركيب')->orderBy('name')->pluck('name', 'id')->all(),
            'salesReps' => User::query()->where('role', 'مندوب مبيعات')->orderBy('name')->pluck('name', 'id')->all(),
            'stockSources' => StockSource::options(),
            'availability' => $this->stock->availabilityMatrix(),
            'employeeAvailability' => $this->employeeStock->availabilityMatrix(),
        ];
    }
}
