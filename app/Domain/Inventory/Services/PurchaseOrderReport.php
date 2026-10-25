<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\Models\PurchaseOrder;
use App\Domain\Inventory\Models\PurchaseOrderItem;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Products\Enums\ProductKind;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;

/**
 * تقرير المخزن (طلبيات الوارد) المطبوع. كل الأقسام تُحسب على نفس الطلبيات المفلترة
 * (نفس فلاتر صفحة المخزن + فترة تاريخ الطلبية)، وكلها استعلامات تجميعية.
 */
class PurchaseOrderReport
{
    /** حد أقصى لصفوف الكشف المطبوع حتى لا تُحمَّل كل الطلبيات في الذاكرة */
    public const MAX_ROWS = 1000;

    /** @return array{orders: int, amount: float, pieces: int, products: int, suppliers: int} */
    public function summary(Builder $orders): array
    {
        $totals = (clone $orders)->toBase()
            ->selectRaw('COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as amount, COUNT(DISTINCT supplier) as suppliers')
            ->first();

        $items = $this->items($orders)
            ->selectRaw('COALESCE(SUM(purchase_order_items.quantity), 0) as pieces')
            ->selectRaw('COUNT(DISTINCT purchase_order_items.product_id) as products')
            ->first();

        return [
            'orders' => (int) ($totals->orders ?? 0),
            'amount' => (float) ($totals->amount ?? 0),
            'suppliers' => (int) ($totals->suppliers ?? 0),
            'pieces' => (int) ($items->pieces ?? 0),
            'products' => (int) ($items->products ?? 0),
        ];
    }

    /** @return Collection<int, array{name: string, orders: int, pieces: int, amount: float}> الأعلى مبلغًا أولًا */
    public function byWarehouse(Builder $orders): Collection
    {
        return $this->groupedBy($orders, 'warehouse_id', fn ($ids) => Warehouse::query()
            ->whereIn('id', $ids)->pluck('name', 'id'), 'مخزن محذوف');
    }

    /** @return Collection<int, array{name: string, orders: int, pieces: int, amount: float}> الأعلى مبلغًا أولًا */
    public function bySupplier(Builder $orders): Collection
    {
        return $this->groupedBy($orders, 'supplier', fn ($suppliers) => $suppliers->combine($suppliers), 'مورد غير محدد');
    }

    /** @return Collection<int, object{name: string, sku: string, kind: ?ProductKind, quantity: int, orders: int}> الأكثر كمية أولًا */
    public function products(Builder $orders): Collection
    {
        return $this->items($orders)
            ->join('products', 'products.id', '=', 'purchase_order_items.product_id')
            ->select('products.name', 'products.sku', 'products.kind')
            ->selectRaw('SUM(purchase_order_items.quantity) as quantity')
            ->selectRaw('COUNT(DISTINCT purchase_order_items.purchase_order_id) as orders')
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.kind')
            ->orderByDesc('quantity')
            ->get()
            ->each(function ($row) {
                $row->kind = ProductKind::tryFrom((string) $row->kind);
            });
    }

    /**
     * صفوف الكشف مع المخزن والأصناف. يُجلب صف زائد عن الحد لمعرفة إن كان الكشف مقطوعًا.
     *
     * @return Collection<int, PurchaseOrder>
     */
    public function rows(Builder $orders, ?string $sort = null, ?string $direction = null): Collection
    {
        $direction = $direction === 'asc' ? 'asc' : 'desc';
        $column = ['amount' => 'total_amount', 'pieces' => 'pieces', 'supplier' => 'supplier'][$sort] ?? 'order_date';

        return (clone $orders)
            ->with(['warehouse:id,name', 'items:id,purchase_order_id,product_id,quantity', 'items.product:id,name'])
            ->withSum('items as pieces', 'quantity')
            ->orderBy($column, $direction)
            ->orderBy('id', $direction)
            ->limit(self::MAX_ROWS + 1)
            ->get();
    }

    /**
     * عدد الطلبيات ومبالغها من جدول الطلبيات، والقطع من البنود — كل منهما مجمّع على حدة
     * لأن ربط البنود يكرر مبلغ الطلبية مع كل بند.
     */
    private function groupedBy(Builder $orders, string $column, callable $names, string $fallback): Collection
    {
        $totals = (clone $orders)->toBase()
            ->select($column)
            ->selectRaw('COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as amount')
            ->groupBy($column)
            ->get();

        $pieces = $this->items($orders)
            ->selectRaw("purchase_orders.{$column} as grouped, COALESCE(SUM(purchase_order_items.quantity), 0) as pieces")
            ->groupBy("purchase_orders.{$column}")
            ->pluck('pieces', 'grouped');

        $labels = $names($totals->pluck($column)->filter()->values());

        return $totals
            ->map(fn ($row) => [
                'name' => $labels[$row->{$column}] ?? $fallback,
                'orders' => (int) $row->orders,
                'pieces' => (int) ($pieces[$row->{$column} ?? ''] ?? 0),
                'amount' => (float) $row->amount,
            ])
            ->sortByDesc('amount')
            ->values();
    }

    /** بنود الطلبيات المفلترة مربوطة بطلبياتها — يُكمله المستدعي بأعمدة التجميع */
    private function items(Builder $orders): QueryBuilder
    {
        return PurchaseOrderItem::query()
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->whereIn('purchase_order_items.purchase_order_id', (clone $orders)->select('purchase_orders.id'))
            ->toBase();
    }
}
