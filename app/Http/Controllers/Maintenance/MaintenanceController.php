<?php

namespace App\Http\Controllers\Maintenance;

use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Maintenance\Enums\MaintenanceType;
use App\Domain\Maintenance\Exceptions\MaintenanceException;
use App\Domain\Maintenance\Services\MaintenanceService;
use App\Domain\Products\Models\Product;
use App\Domain\Sales\Models\SalesContractMaintenanceItem;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Maintenance\MaintenanceRequest;
use App\Http\Requests\Maintenance\MaintenanceScheduleRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * ملف الصيانة: سطر لكل شمعة على عقود خطة الصيانة. «إضافة» تسجّل الصيانة (قطاعي أو جملة)،
 * و«تعديل» يغيّر الفني وتاريخ الصيانة فقط.
 */
class MaintenanceController extends Controller
{
    private const PER_PAGE = 20;

    public const STATUSES = ['pending' => 'لم يتم', 'done' => 'تم', 'all' => 'الكل'];

    public function __construct(private readonly MaintenanceService $maintenance) {}

    public function index(Request $request): View
    {
        $status = array_key_exists($request->input('status'), self::STATUSES) ? $request->input('status') : 'pending';

        $candles = SalesContractMaintenanceItem::query()
            ->whereHas('contract')
            ->when($status === 'pending', fn ($query) => $query->pending()->orderBy('replacement_date'))
            ->when($status === 'done', fn ($query) => $query->done()->orderByDesc('serviced_at'))
            ->when($status === 'all', fn ($query) => $query->orderBy('replacement_date'))
            ->when($request->filled('technician_id'), fn ($query) => $query->where('technician_id', $request->integer('technician_id')))
            ->search($request->input('q'))
            ->with([
                'contract:id,code,customer_id,installer_id',
                'contract.customer:id,name,code',
                'technician:id,name',
            ])
            ->orderBy('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.maintenance.index', [
            'candles' => $candles,
            'status' => $status,
            'statuses' => self::STATUSES,
            'technicians' => $this->technicians()->pluck('name', 'id')->all(),
        ]);
    }

    public function create(SalesContractMaintenanceItem $candle, EmployeeStockService $custody): View|RedirectResponse
    {
        if ($candle->isDone()) {
            return redirect()->route('maintenance.file')->with('error', MaintenanceException::alreadyDone()->getMessage());
        }

        $candle->load(['contract.customer:id,name,code', 'contract.items:id,sales_contract_id,product_name']);
        $custodyMatrix = $custody->availabilityMatrix();

        return view('pages.maintenance.create', [
            'candle' => $candle,
            'contract' => $candle->contract,
            // شمعات العقد الباقية: قطاعي يعرض المختارة فقط، وجملة تعرضها كلها
            'pending' => $candle->contract->maintenanceItems()->pending()->get(['id', 'product_name', 'replacement_date']),
            'types' => MaintenanceType::options(),
            'technicians' => $this->technicians(),
            'custody' => $custodyMatrix,
            'productNames' => Product::query()
                ->whereKey(collect($custodyMatrix)->flatMap(fn (array $products) => array_keys($products))->unique()->all())
                ->pluck('name', 'id'),
            'defaultTechnician' => $candle->technician_id ?? $candle->contract->installer_id,
        ]);
    }

    public function store(MaintenanceRequest $request, SalesContractMaintenanceItem $candle): RedirectResponse
    {
        $data = $request->toData();

        try {
            $this->maintenance->record($data);
        } catch (InsufficientStockException|MaintenanceException $exception) {
            return back()->withInput()->withErrors(['lines' => $exception->getMessage()]);
        }

        $message = match (true) {
            ! $data->done => 'اتحدد الفني وميعاد الصيانة.',
            $data->type === MaintenanceType::Package => 'اتسجلت صيانة جملة — العميل خرج من ملف الصيانة.',
            default => 'اتسجلت الصيانة واتخصمت الشمعة من عهدة الفني.',
        };

        return redirect()->route('maintenance.file')->with('success', $message);
    }

    public function update(MaintenanceScheduleRequest $request, SalesContractMaintenanceItem $candle): RedirectResponse
    {
        try {
            $this->maintenance->reschedule($candle, $request->integer('technician_id'), $request->validated('date'));
        } catch (MaintenanceException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'اتعدل الفني وتاريخ الصيانة.');
    }

    /** @return Collection<int, User> */
    private function technicians(): Collection
    {
        return User::query()->where('role', User::INSTALLER_ROLE)->orderBy('name')->get(['id', 'name']);
    }
}
