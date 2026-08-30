<?php

namespace App\Http\Controllers\Inventory;

use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Products\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    private const PER_PAGE = 15;

    /** الحد الذي يُعتبر تحته المنتج منخفض المخزون */
    private const LOW_THRESHOLD = 10;

    public function index(Request $request): View
    {
        $warehouseId = $request->integer('warehouse_id') ?: null;

        $products = Product::query()
            ->withSum(
                ['stockBalances as quantity' => fn (Builder $query) => $query->when(
                    $warehouseId,
                    fn (Builder $query) => $query->where('warehouse_id', $warehouseId),
                )],
                'quantity',
            )
            ->search($request->input('q'))
            ->applyFilters($request->only(['category', 'status']))
            ->applySort($request->input('sort'), $request->input('dir'))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.inventory.index', [
            'products' => $products,
            'stats' => $this->stats($warehouseId),
            'warehouses' => Warehouse::options(),
            'categories' => Product::categories(),
            'lowThreshold' => self::LOW_THRESHOLD,
        ]);
    }

    /** ملخص المخزون في استعلام تجميعي واحد فوق أرصدة المنتجات */
    private function stats(?int $warehouseId): array
    {
        $perProduct = Product::query()
            ->leftJoin('stock_balances', function ($join) use ($warehouseId) {
                $join->on('stock_balances.product_id', '=', 'products.id');

                if ($warehouseId) {
                    $join->where('stock_balances.warehouse_id', '=', $warehouseId);
                }
            })
            ->whereNull('products.deleted_at')
            ->groupBy('products.id')
            ->selectRaw('products.id, COALESCE(SUM(stock_balances.quantity), 0) as quantity');

        $summary = DB::query()
            ->fromSub($perProduct, 'balances')
            ->selectRaw('COUNT(*) as products')
            ->selectRaw('COALESCE(SUM(quantity), 0) as pieces')
            ->selectRaw('SUM(CASE WHEN quantity <= 0 THEN 1 ELSE 0 END) as out_of_stock')
            ->selectRaw('SUM(CASE WHEN quantity > 0 AND quantity <= ? THEN 1 ELSE 0 END) as low', [self::LOW_THRESHOLD])
            ->first();

        return [
            'products' => (int) ($summary->products ?? 0),
            'pieces' => (int) ($summary->pieces ?? 0),
            'low' => (int) ($summary->low ?? 0),
            'out_of_stock' => (int) ($summary->out_of_stock ?? 0),
        ];
    }
}
