<?php

namespace App\Http\Controllers\Maintenance;

use App\Domain\Maintenance\Enums\MaintenancePackageStatus;
use App\Domain\Maintenance\Exceptions\MaintenanceException;
use App\Domain\Maintenance\Models\MaintenancePackage;
use App\Domain\Maintenance\Services\MaintenancePackageService;
use App\Domain\Sales\Enums\ContractKind;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Maintenance\Concerns\PicksDevice;
use App\Http\Requests\Maintenance\MaintenancePackageRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MaintenancePackageController extends Controller
{
    use PicksDevice;

    private const PER_PAGE = 15;

    public function __construct(private readonly MaintenancePackageService $packages) {}

    public function index(Request $request): View
    {
        $status = $request->input('status');

        $packages = MaintenancePackage::query()
            ->search($request->input('q'))
            ->when(in_array($status, array_keys(MaintenancePackageStatus::options()), true), fn ($query) => $query->where('status', $status))
            ->with(['customer:id,name,code', 'device:id,code', 'maintenanceContract:id,code', 'technician:id,name', 'salesRep:id,name'])
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.maintenance.packages.index', [
            'packages' => $packages,
            'statuses' => MaintenancePackageStatus::options(),
        ]);
    }

    public function create(Request $request): View
    {
        $device = $this->findDevice($request->integer('contract') ?: null);

        return view('pages.maintenance.packages.create', [
            'device' => $device,
            'results' => $device ? collect() : $this->searchDevices($request->input('q')),
            // عقود الصيانة المالية لنفس العميل — مكان تسجيل المبلغ (كاش أو أقساط)
            'maintenanceContracts' => $device
                ? SalesContract::query()->ofKind(ContractKind::Maintenance)->where('customer_id', $device->customer_id)
                    ->orderByDesc('contract_date')->get(['id', 'code', 'total', 'type'])
                : collect(),
            'technicians' => User::query()->where('role', User::INSTALLER_ROLE)->orderBy('name')->get(['id', 'name', 'maintenance_commission']),
            'salesReps' => User::query()->whereIn('role', User::CONTRACT_SALES_ROLES)->orderBy('name')->get(['id', 'name', 'maintenance_commission']),
        ]);
    }

    public function store(MaintenancePackageRequest $request): RedirectResponse
    {
        $package = $this->packages->create($request->toData());

        return redirect()
            ->route('maintenance.packages.index')
            ->with('success', "تم تسجيل عقد الصيانة {$package->number()} — رصيد {$package->totalCandles()} شمعة في حساب العميل.");
    }

    public function destroy(MaintenancePackage $package): RedirectResponse
    {
        try {
            $this->packages->delete($package);
        } catch (MaintenanceException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "تم حذف عقد الصيانة {$package->number()}.");
    }
}
