<?php

namespace App\Domain\Products\Services;

use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Models\EmployeeStock;
use App\Domain\Inventory\Models\StockBalance;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Products\Enums\ProductKind;
use App\Domain\Products\Enums\ProductStatus;
use App\Domain\Products\Models\Product;
use App\Domain\Reports\Data\ReportPeriod;
use App\Domain\Sales\Models\SalesContractItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;

/**
 * تقرير المنتجات والمخزون المطبوع. كل الأقسام تُحسب على نفس مجموعة المنتجات المفلترة؛
 * الأرصدة لحظية (حتى الآن)، والفترة تحدد فقط حركات المخزن والمبيعات. لو حُدّد مخزن فالأرصدة
 * والحركات تخصه وحده. كل الأقسام استعلامات تجميعية لا تمر على المنتجات واحدًا واحدًا.
 */
class ProductReport
{
    /** حد أقصى لصفوف الكشف المطبوع حتى لا يُحمَّل الكتالوج كله في الذاكرة */
    public const MAX_ROWS = 1000;

    public function __construct(private readonly ?int $warehouseId = null) {}

    public function forWarehouse(?int $warehouseId): self
    {
        return new self($warehouseId);
    }

    /** @return array{products: int, active: int, inactive: int, out_of_stock: int, employee_pieces: int} */
    public function counts(Builder $products): array
    {
        $statuses = (clone $products)->toBase()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'products' => (int) $statuses->sum(),
            'active' => (int) $statuses->get(ProductStatus::Active->value, 0),
            'inactive' => (int) $statuses->get(ProductStatus::Inactive->value, 0),
            'out_of_stock' => (clone $products)
                ->whereDoesntHave('stockBalances', fn (Builder $balances) => $this->inWarehouse($balances->where('quantity', '>', 0)))
                ->count(),
            'employee_pieces' => (int) EmployeeStock::query()
                ->whereIn('product_id', $this->ids($products))
                ->sum('quantity'),
        ];
    }

    /**
     * الأرصدة وقيمتها (بسعر البيع وبالتكلفة) لكل نوع منتج.
     *
     * @return Collection<string, array{kind: ProductKind, products: int, pieces: int, value: float, cost: float}>
     */
    public function stockByKind(Builder $products): Collection
    {
        $counts = (clone $products)->toBase()
            ->select('kind')
            ->selectRaw('COUNT(*) as products')
            ->groupBy('kind')
            ->pluck('products', 'kind');

        $stock = $this->balances($this->ids($products))
            ->selectRaw('products.kind as kind')
            ->groupBy('products.kind')
            ->get()
            ->keyBy('kind');

        return collect(ProductKind::cases())->mapWithKeys(fn (ProductKind $kind) => [$kind->value => [
            'kind' => $kind,
            'products' => (int) $counts->get($kind->value, 0),
            'pieces' => (int) ($stock[$kind->value]->pieces ?? 0),
            'value' => (float) ($stock[$kind->value]->value ?? 0),
            'cost' => (float) ($stock[$kind->value]->cost ?? 0),
        ]]);
    }

    /** @return Collection<int, object{warehouse: string, pieces: int, value: float}> */
    public function stockByWarehouse(Builder $products): Collection
    {
        return $this->balances($this->ids($products))
            ->join('warehouses', 'warehouses.id', '=', 'stock_balances.warehouse_id')
            ->selectRaw('warehouses.name as warehouse')
            ->groupBy('warehouses.id', 'warehouses.name')
            ->orderByDesc('pieces')
            ->get();
    }

    /**
     * حركات المخزن والمبيعات خلال الفترة.
     *
     * @return array{in: int, out: int, adjustments: int, sold_quantity: int, sold_value: float, sold_contracts: int}
     */
    public function activity(Builder $products, ReportPeriod $period): array
    {
        $movements = $this->inWarehouse(StockMovement::query())
            ->whereIn('product_id', $this->ids($products))
            ->whereBetween('moved_at', $period->bounds())
            ->toBase()
            ->selectRaw('type, COALESCE(SUM(quantity), 0) as quantity')
            ->groupBy('type')
            ->pluck('quantity', 'type');

        $sold = SalesContractItem::query()
            ->whereIn('product_id', $this->ids($products))
            ->whereHas('contract', fn (Builder $contract) => $contract->whereBetween('contract_date', $period->bounds()))
            ->toBase()
            ->selectRaw('COALESCE(SUM(quantity), 0) as quantity, COALESCE(SUM(total), 0) as total, COUNT(DISTINCT sales_contract_id) as contracts')
            ->first();

        return [
            'in' => (int) $movements->get(StockMovementType::In->value, 0),
            'out' => (int) $movements->get(StockMovementType::Out->value, 0),
            'adjustments' => (int) $movements->get(StockMovementType::Adjustment->value, 0),
            'sold_quantity' => (int) ($sold->quantity ?? 0),
            'sold_value' => (float) ($sold->total ?? 0),
            'sold_contracts' => (int) ($sold->contracts ?? 0),
        ];
    }

    /**
     * صفوف الكشف بكل أرقامها محسوبة في نفس الاستعلام. يُجلب صف زائد عن الحد لمعرفة إن كان الكشف مقطوعًا.
     *
     * @return Collection<int, Product>
     */
    public function rows(Builder $products, ReportPeriod $period, ?string $sort = null, ?string $direction = null): Collection
    {
        $movementsOf = fn (StockMovementType $type) => fn (Builder $movements) => $this->inWarehouse($movements)
            ->where('type', $type->value)
            ->whereBetween('moved_at', $period->bounds());

        $query = (clone $products)
            ->select(['id', 'sku', 'name', 'category', 'kind', 'status', 'price', 'cost'])
            ->withSum(['stockBalances as stock_quantity' => fn (Builder $balances) => $this->inWarehouse($balances)], 'quantity')
            ->withSum('employeeStocks as employee_quantity', 'quantity')
            ->withSum(['stockMovements as period_in' => $movementsOf(StockMovementType::In)], 'quantity')
            ->withSum(['stockMovements as period_out' => $movementsOf(StockMovementType::Out)], 'quantity')
            ->withSum(['contractItems as period_sold' => fn (Builder $items) => $items
                ->whereHas('contract', fn (Builder $contract) => $contract->whereBetween('contract_date', $period->bounds()))], 'quantity');

        // الترتيب بالأعمدة المحسوبة يعتمد على أسماء withSum في نفس الاستعلام، فلا يمر عبر قائمة SORTABLE
        $direction = $direction === 'desc' ? 'desc' : 'asc';
        match ($sort) {
            'stock' => $query->orderBy('stock_quantity', $direction)->orderBy('id'),
            'sold' => $query->orderBy('period_sold', $direction)->orderBy('id'),
            default => $query->applySort($sort, $direction),
        };

        return $query->limit(self::MAX_ROWS + 1)->get();
    }

    /** مجموع الأرصدة وقيمتها للمنتجات المحددة — يُكمله المستدعي بعمود التجميع */
    private function balances(Builder $productIds): QueryBuilder
    {
        return $this->inWarehouse(StockBalance::query())
            ->join('products', 'products.id', '=', 'stock_balances.product_id')
            ->whereIn('stock_balances.product_id', $productIds)
            ->toBase()
            ->selectRaw('COALESCE(SUM(stock_balances.quantity), 0) as pieces')
            ->selectRaw('COALESCE(SUM(stock_balances.quantity * products.price), 0) as value')
            ->selectRaw('COALESCE(SUM(stock_balances.quantity * COALESCE(products.cost, 0)), 0) as cost');
    }

    private function inWarehouse(Builder $query): Builder
    {
        return $query->when($this->warehouseId, fn (Builder $query) => $query->where(
            $query->getModel()->qualifyColumn('warehouse_id'),
            $this->warehouseId,
        ));
    }

    private function ids(Builder $products): Builder
    {
        return (clone $products)->select('products.id');
    }
}
