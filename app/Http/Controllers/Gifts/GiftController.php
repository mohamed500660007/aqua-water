<?php

namespace App\Http\Controllers\Gifts;

use App\Domain\Customers\Models\Customer;
use App\Domain\Gifts\Models\Gift;
use App\Domain\Gifts\Services\GiftService;
use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Products\Models\Product;
use App\Domain\Sales\Enums\StockSource;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gifts\GiftRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GiftController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly GiftService $gifts,
        private readonly StockService $stock,
        private readonly EmployeeStockService $employeeStock,
    ) {}

    public function index(Request $request): View
    {
        $gifts = Gift::query()
            ->with(['customer:id,name,code', 'technician:id,name', 'product:id,name', 'warehouse:id,name'])
            ->search($request->input('q'))
            ->applyFilters($request->only(['customer_id', 'technician_id', 'product_id']))
            ->applySort($request->input('sort'), $request->input('dir'))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.gifts.index', [
            'gifts' => $gifts,
            'customers' => Customer::query()->orderBy('name')->pluck('name', 'id')->all(),
            'technicians' => $this->technicians(),
            'products' => Product::query()->orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }

    public function create(): View
    {
        return view('pages.gifts.create', [
            'customers' => Customer::query()->orderBy('name')->pluck('name', 'id')->all(),
            'technicians' => $this->technicians(),
            'products' => Product::query()->orderBy('name')->pluck('name', 'id')->all(),
            'warehouses' => Warehouse::options(),
            'stockSources' => [
                StockSource::Warehouse->value => StockSource::Warehouse->label(),
                StockSource::Installer->value => StockSource::Installer->label(),
            ],
            'availability' => $this->stock->availabilityMatrix(),
            'employeeAvailability' => $this->employeeStock->availabilityMatrix(),
        ]);
    }

    public function store(GiftRequest $request): RedirectResponse
    {
        try {
            $this->gifts->create($request->toData());
        } catch (InsufficientStockException $exception) {
            return back()->withInput()->withErrors(['quantity' => $exception->getMessage()]);
        }

        return redirect()
            ->route('gifts.index')
            ->with('success', 'تم تسجيل الهدية وخصم القطعة من مصدرها.');
    }

    public function destroy(Gift $gift): RedirectResponse
    {
        $this->gifts->delete($gift);

        return redirect()
            ->route('gifts.index')
            ->with('success', 'تم حذف الهدية وإعادة القطعة إلى مصدرها.');
    }

    /** @return array<int, string> */
    private function technicians(): array
    {
        return User::query()->where('role', 'فني تركيب')->orderBy('name')->pluck('name', 'id')->all();
    }
}
