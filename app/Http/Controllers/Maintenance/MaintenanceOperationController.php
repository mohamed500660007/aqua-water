<?php

namespace App\Http\Controllers\Maintenance;

use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Maintenance\Enums\MaintenanceOperationType;
use App\Domain\Maintenance\Exceptions\MaintenanceException;
use App\Domain\Maintenance\Models\CandleStage;
use App\Domain\Maintenance\Models\MaintenanceOperation;
use App\Domain\Maintenance\Services\MaintenanceOperationService;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Maintenance\Concerns\PicksDevice;
use App\Http\Requests\Maintenance\MaintenanceOperationRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MaintenanceOperationController extends Controller
{
    use PicksDevice;

    private const PER_PAGE = 15;

    public function __construct(private readonly MaintenanceOperationService $operations) {}

    /** سجل كل عمليات الصيانة */
    public function index(Request $request): View
    {
        $operations = MaintenanceOperation::query()
            ->search($request->input('q'))
            ->applyFilters($request->only(['type', 'technician_id', 'from', 'to']))
            ->with([
                'customer:id,name,code',
                'device:id,code',
                'technician:id,name',
                'salesRep:id,name',
                'package:id',
                'items:id,maintenance_operation_id,position,product_name,unit_price',
            ])
            ->orderByDesc('serviced_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.maintenance.operations.index', [
            'operations' => $operations,
            'types' => MaintenanceOperationType::options(),
            'technicians' => $this->technicians(),
        ]);
    }

    /**
     * خطوتان في صفحة واحدة: اختيار الجهاز بالبحث، ثم نموذج العملية بمراحل الجهاز المستحقة محددة مسبقًا.
     */
    public function create(Request $request): View
    {
        $device = $this->findDevice($request->integer('contract') ?: null);
        $device?->load(['candleSchedules', 'activeMaintenancePackage', 'installer:id,name', 'salesRep:id,name']);

        return view('pages.maintenance.operations.create', [
            'device' => $device,
            'results' => $device ? collect() : $this->searchDevices($request->input('q')),
            'stages' => CandleStage::configured(),
            'technicians' => User::query()->where('role', User::INSTALLER_ROLE)->orderBy('name')->get(['id', 'name', 'maintenance_commission']),
            'salesReps' => User::query()->whereIn('role', User::CONTRACT_SALES_ROLES)->orderBy('name')->get(['id', 'name', 'maintenance_commission']),
            'warehouses' => Warehouse::options(),
        ]);
    }

    public function store(MaintenanceOperationRequest $request): RedirectResponse
    {
        try {
            $operation = $this->operations->create($request->toData());
        } catch (InsufficientStockException|MaintenanceException $exception) {
            return back()->withInput()->withErrors(['positions' => $exception->getMessage()]);
        }

        return redirect()
            ->route('maintenance.file')
            ->with('success', "تمت الصيانة ({$operation->number()}) بتاريخ {$operation->serviced_at->format('Y-m-d')} وتجدد موعد الجهاز القادم.");
    }

    public function destroy(MaintenanceOperation $operation): RedirectResponse
    {
        try {
            $this->operations->delete($operation);
        } catch (InsufficientStockException $exception) {
            return back()->with('error', "تعذّر الإلغاء: {$exception->getMessage()}");
        }

        return back()->with('success', "تم إلغاء العملية {$operation->number()} وإرجاع شمعاتها واستعادة موعد الجهاز السابق.");
    }

    /** @return array<int, string> */
    private function technicians(): array
    {
        return User::query()->where('role', User::INSTALLER_ROLE)->orderBy('name')->pluck('name', 'id')->all();
    }
}
