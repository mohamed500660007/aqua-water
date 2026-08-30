<?php

namespace App\Http\Controllers\Inventory;

use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Models\StockBalance;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Products\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StockMovementRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(private readonly StockService $stock) {}

    public function index(Request $request): View
    {
        $movements = StockMovement::query()
            ->with(['product:id,name,sku', 'warehouse:id,name'])
            ->applyFilters($request->only(['product_id', 'warehouse_id', 'type']))
            ->orderByDesc('moved_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.inventory.movements', [
            'movements' => $movements,
            'products' => Product::query()->orderBy('name')->pluck('name', 'id')->all(),
            'warehouses' => Warehouse::options(),
            'types' => StockMovementType::options(),
        ]);
    }

    public function create(): View
    {
        return view('pages.inventory.create-movement', $this->formOptions());
    }

    public function store(StockMovementRequest $request): RedirectResponse
    {
        try {
            $movement = $this->stock->record($request->toData());
        } catch (InsufficientStockException $exception) {
            return back()->withInput()->withErrors(['quantity' => $exception->getMessage()]);
        }

        return redirect()
            ->route('inventory.movements.index')
            ->with('success', "تم تسجيل حركة {$movement->type->label()} بنجاح — الرصيد الحالي {$movement->balance_after}.");
    }

    private function formOptions(): array
    {
        return [
            'products' => Product::query()->orderBy('name')->get(['id', 'sku', 'name']),
            'warehouses' => Warehouse::options(),
            'types' => StockMovementType::options(),
            'availability' => $this->availabilityMatrix(),
        ];
    }

    /**
     * الأرصدة الحالية لكل مخزن/منتج لعرض المتاح فورًا في النموذج.
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
}
