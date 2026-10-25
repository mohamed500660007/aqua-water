<?php

namespace App\Http\Controllers\Inventory;

use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Models\StockBalance;
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
    public function __construct(private readonly StockService $stock) {}

    public function create(Request $request): View
    {
        return view('pages.inventory.add-stock', [
            'products' => Product::query()->orderBy('name')->pluck('name', 'id')->all(),
            'warehouses' => Warehouse::options(),
            'types' => StockMovementType::options(),
            'availability' => $this->availabilityMatrix(),
            'selectedProductId' => $request->integer('product_id') ?: null,
        ]);
    }

    public function store(StockMovementRequest $request): RedirectResponse
    {
        try {
            $movement = $this->stock->record($request->toData());
        } catch (InsufficientStockException $exception) {
            return back()->withInput()->withErrors(['quantity' => $exception->getMessage()]);
        }

        return redirect()
            ->route('products.show', $movement->product_id)
            ->with('success', "تم تسجيل حركة {$movement->type->label()} بنجاح — الرصيد الحالي {$movement->balance_after}.");
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
