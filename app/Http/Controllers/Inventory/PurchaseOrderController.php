<?php

namespace App\Http\Controllers\Inventory;

use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Models\PurchaseOrder;
use App\Domain\Inventory\Models\PurchaseOrderItem;
use App\Domain\Inventory\Models\StockBalance;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Services\PurchaseOrderReport;
use App\Domain\Inventory\Services\PurchaseOrderService;
use App\Domain\Products\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\PurchaseOrderReportRequest;
use App\Http\Requests\Inventory\PurchaseOrderRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(private readonly PurchaseOrderService $orders) {}

    public function index(Request $request): View
    {
        $filtered = PurchaseOrder::query()
            ->search($request->input('q'))
            ->applyFilters($request->only(['warehouse_id', 'from', 'to']));

        // صف لكل منتج في كل طلبية؛ الرصيد الحالي للمنتج في مخزن الطلبية باستعلام فرعي داخل نفس الاستعلام
        $items = PurchaseOrderItem::query()
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->whereIn('purchase_order_items.purchase_order_id', PurchaseOrder::query()
                ->applyFilters($request->only(['warehouse_id', 'from', 'to']))
                ->select('id'))
            ->search($request->input('q'))
            ->select('purchase_order_items.*')
            ->addSelect(['current_balance' => StockBalance::query()
                ->select('quantity')
                ->whereColumn('stock_balances.product_id', 'purchase_order_items.product_id')
                ->whereColumn('stock_balances.warehouse_id', 'purchase_orders.warehouse_id')
                ->limit(1)])
            ->with(['product:id,sku,name', 'purchaseOrder:id,warehouse_id,order_date', 'purchaseOrder.warehouse:id,name'])
            ->orderByDesc('purchase_orders.order_date')
            ->orderByDesc('purchase_orders.id')
            ->orderBy('purchase_order_items.id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.inventory.purchase-orders.index', [
            'items' => $items,
            'summary' => $this->summary($filtered),
            'warehouses' => Warehouse::options(),
        ]);
    }

    public function report(PurchaseOrderReportRequest $request, PurchaseOrderReport $report): View
    {
        $content = $request->content();
        $orders = PurchaseOrder::query()
            ->search($request->validated('q'))
            ->applyFilters($request->filters());

        // الأجزاء غير المطلوبة للطباعة لا تُحسب أصلًا
        $withSummary = $content !== 'list';
        $rows = $content !== 'summary'
            ? $report->rows($orders, $request->validated('sort'), $request->validated('dir'))
            : collect();

        return view('pages.inventory.purchase-orders.report', [
            'period' => $request->period(),
            'content' => $content,
            'summary' => $report->summary($orders),
            'byWarehouse' => $withSummary ? $report->byWarehouse($orders) : collect(),
            'bySupplier' => $withSummary ? $report->bySupplier($orders) : collect(),
            'products' => $withSummary ? $report->products($orders) : collect(),
            'rows' => $rows->take(PurchaseOrderReport::MAX_ROWS),
            'truncated' => $rows->count() > PurchaseOrderReport::MAX_ROWS,
            'search' => $request->validated('q'),
            'filters' => $request->filters(),
            'listQuery' => $request->listQuery(),
            'contents' => PurchaseOrderReportRequest::CONTENTS,
            'sorts' => PurchaseOrderReportRequest::SORTS,
            'warehouses' => Warehouse::options(),
            'suppliers' => PurchaseOrder::suppliers(),
            'productOptions' => Product::query()->orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }

    /** لوحة معاينة جانبية للطلبية — تُطلب عند الفتح فقط */
    public function panel(PurchaseOrder $purchaseOrder): View
    {
        return view('pages.inventory.purchase-orders.panel', [
            'order' => $purchaseOrder->load(['warehouse:id,name', 'items.product:id,sku,name,kind']),
        ]);
    }

    public function create(): View
    {
        return view('pages.inventory.purchase-orders.create', [
            'products' => Product::query()->orderBy('name')->pluck('name', 'id')->all(),
            'warehouses' => Warehouse::options(),
        ]);
    }

    public function store(PurchaseOrderRequest $request): RedirectResponse
    {
        $this->orders->create($request->toData());

        return redirect()
            ->route('inventory.purchase-orders.index')
            ->with('success', 'تم تسجيل الطلبية وإضافة كمياتها إلى المخزن.');
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        try {
            $this->orders->delete($purchaseOrder->load('items'));
        } catch (InsufficientStockException $exception) {
            return back()->with('error', "لا يمكن حذف الطلبية لأن جزءًا من بضاعتها صُرف بالفعل. {$exception->getMessage()}");
        }

        return back()->with('success', 'تم حذف الطلبية وخصم كمياتها من المخزن.');
    }

    /** ملخص كل الطلبيات المطابقة للفلاتر (لا الصفحة الحالية فقط) في استعلامين تجميعيين */
    private function summary(Builder $filtered): array
    {
        $orders = (clone $filtered)->toBase()
            ->selectRaw('COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as amount')
            ->first();

        return [
            'orders' => (int) ($orders->orders ?? 0),
            'amount' => (float) ($orders->amount ?? 0),
            'pieces' => (int) PurchaseOrderItem::query()
                ->whereIn('purchase_order_id', (clone $filtered)->select('id'))
                ->sum('quantity'),
        ];
    }
}
