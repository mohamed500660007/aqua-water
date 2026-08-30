<?php

namespace App\Http\Controllers\Products;

use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Products\Enums\ProductStatus;
use App\Domain\Products\Models\Product;
use App\Domain\Products\Services\ProductService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Products\ProductRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    private const PER_PAGE = 12;

    private const RECENT_MOVEMENTS = 10;

    public function __construct(private readonly ProductService $products) {}

    public function index(Request $request): View
    {
        $products = Product::query()
            ->withSum('stockBalances as quantity', 'quantity')
            ->search($request->input('q'))
            ->applyFilters($request->only(['category', 'status']))
            ->applySort($request->input('sort'), $request->input('dir'))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.products.index', [
            'products' => $products,
            'stats' => $this->stats(),
        ] + $this->formOptions());
    }

    public function create(): View
    {
        return view('pages.products.create', $this->formOptions());
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = $this->products->create($request->toData());

        return redirect()
            ->route('products.show', $product)
            ->with('success', "تمت إضافة المنتج «{$product->name}» بنجاح.");
    }

    public function show(Product $product): View
    {
        return view('pages.products.show', [
            'product' => $product->load('stockBalances.warehouse:id,name'),
            'movements' => StockMovement::query()
                ->with('warehouse:id,name')
                ->where('product_id', $product->id)
                ->orderByDesc('moved_at')
                ->orderByDesc('id')
                ->limit(self::RECENT_MOVEMENTS)
                ->get(),
        ]);
    }

    public function edit(Product $product): View
    {
        return view('pages.products.edit', ['product' => $product] + $this->formOptions());
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->products->update($product, $request->toData());

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'تم حفظ تعديلات المنتج بنجاح.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->products->delete($product);

        return redirect()
            ->route('products.index')
            ->with('success', "تم حذف المنتج «{$product->name}».");
    }

    /** ملخص الكتالوج وقيمة المخزون في استعلامين تجميعيين */
    private function stats(): array
    {
        $counts = Product::query()
            ->selectRaw('COUNT(*) as products')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active', [ProductStatus::Active->value])
            ->first();

        $value = DB::table('stock_balances')
            ->join('products', 'products.id', '=', 'stock_balances.product_id')
            ->whereNull('products.deleted_at')
            ->selectRaw('COALESCE(SUM(stock_balances.quantity * products.price), 0) as value')
            ->selectRaw('COALESCE(SUM(stock_balances.quantity), 0) as pieces')
            ->first();

        return [
            'products' => (int) ($counts->products ?? 0),
            'active' => (int) ($counts->active ?? 0),
            'pieces' => (int) ($value->pieces ?? 0),
            'value' => (float) ($value->value ?? 0),
        ];
    }

    private function formOptions(): array
    {
        return [
            'statuses' => ProductStatus::options(),
            'defaultStatus' => ProductStatus::Active->value,
            'categories' => Product::categories(),
        ];
    }
}
