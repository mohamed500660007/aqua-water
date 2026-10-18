<?php

namespace App\Http\Controllers\Products;

use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Products\Enums\ProductKind;
use App\Domain\Products\Enums\ProductStatus;
use App\Domain\Products\Models\Product;
use App\Domain\Products\Services\ProductReport;
use App\Domain\Products\Services\ProductService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Products\ProductReportRequest;
use App\Http\Requests\Products\ProductRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
            ->applyFilters($request->only(['category', 'kind', 'status']))
            ->applySort($request->input('sort'), $request->input('dir'))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.products.index', ['products' => $products] + $this->formOptions());
    }

    public function report(ProductReportRequest $request, ProductReport $report): View
    {
        $period = $request->period();
        $content = $request->content();
        $report = $report->forWarehouse($request->warehouseId());
        $products = Product::query()
            ->search($request->validated('q'))
            ->applyFilters($request->filters());

        // الأجزاء غير المطلوبة للطباعة لا تُحسب أصلًا
        $withSummary = $content !== 'list';
        $rows = $content !== 'summary'
            ? $report->rows($products, $period, $request->validated('sort'), $request->validated('dir'))
            : collect();

        return view('pages.products.report', [
            'period' => $period,
            'content' => $content,
            'counts' => $report->counts($products),
            'byKind' => $withSummary ? $report->stockByKind($products) : collect(),
            'byWarehouse' => $withSummary ? $report->stockByWarehouse($products) : collect(),
            'activity' => $withSummary ? $report->activity($products, $period) : null,
            'rows' => $rows->take(ProductReport::MAX_ROWS),
            'truncated' => $rows->count() > ProductReport::MAX_ROWS,
            'search' => $request->validated('q'),
            'filters' => $request->filters(),
            'listQuery' => $request->listQuery(),
            'contents' => ProductReportRequest::CONTENTS,
            'sorts' => ProductReportRequest::SORTS,
            'warehouses' => Warehouse::options(),
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

    private function formOptions(): array
    {
        return [
            'statuses' => ProductStatus::options(),
            'kinds' => ProductKind::options(),
            'categories' => Product::categories(),
        ];
    }
}
