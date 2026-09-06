<?php

namespace App\Http\Controllers\Inventory;

use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Models\EmployeeStock;
use App\Domain\Inventory\Models\StockBalance;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Products\Models\Product;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\EmployeeHandoverRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class EmployeeStockController extends Controller
{
    public function __construct(private readonly EmployeeStockService $employeeStock) {}

    /** ما يحمله كل موظف من منتجات حاليًا */
    public function index(): View
    {
        $balances = EmployeeStock::query()
            ->with(['user:id,name,role', 'product:id,name,sku'])
            ->where('quantity', '>', 0)
            ->orderBy('user_id')
            ->get()
            ->groupBy(fn (EmployeeStock $balance) => $balance->user?->name ?? '—');

        return view('pages.inventory.employee-stocks.index', [
            'balances' => $balances,
        ]);
    }

    public function create(): View
    {
        return view('pages.inventory.employee-stocks.create', [
            'employees' => User::query()
                ->whereIn('role', ['مندوب مبيعات', 'فني تركيب'])
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all(),
            'products' => Product::query()->orderBy('name')->pluck('name', 'id')->all(),
            'warehouses' => Warehouse::options(),
            'availability' => $this->availabilityMatrix(),
        ]);
    }

    /**
     * أرصدة كل مخزن/منتج لعرض المتاح فورًا في نموذج التسليم.
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

    public function store(EmployeeHandoverRequest $request): RedirectResponse
    {
        try {
            $this->employeeStock->handOverMany($request->toData());
        } catch (InsufficientStockException $exception) {
            return back()->withInput()->withErrors(['items' => $exception->getMessage()]);
        }

        return redirect()
            ->route('inventory.employee-stocks.index')
            ->with('success', 'تم تسليم العهدة للموظف بنجاح.');
    }
}
