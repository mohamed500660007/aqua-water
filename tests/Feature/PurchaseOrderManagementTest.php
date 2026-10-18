<?php

namespace Tests\Feature;

use App\Domain\Inventory\Models\PurchaseOrder;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Products\Models\Product;
use Database\Seeders\PurchaseOrderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurchaseOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;

    private Product $filter;

    private Product $cartridge;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::factory()->create(['name' => 'المخزن الرئيسي', 'is_active' => true]);
        $this->filter = Product::factory()->create(['name' => 'فلتر سبع مراحل', 'sku' => 'AQ-7', 'kind' => 'device', 'cost' => 3000]);
        $this->cartridge = Product::factory()->create(['name' => 'شمعة كربون', 'sku' => 'AQ-CRB', 'kind' => 'cartridge', 'cost' => 40]);
    }

    private function order(array $overrides = []): void
    {
        $this->post(route('inventory.purchase-orders.store'), $overrides + [
            'warehouse_id' => $this->warehouse->id,
            'order_date' => today()->toDateString(),
            'supplier' => 'مؤسسة النيل للفلاتر',
            'total_amount' => 10000,
            'items' => [
                ['product_id' => $this->filter->id, 'quantity' => 3],
                ['product_id' => $this->cartridge->id, 'quantity' => 20],
            ],
        ])->assertSessionHasNoErrors();
    }

    public function test_page_is_titled_the_warehouse_and_summarises_the_filtered_orders(): void
    {
        $this->order();
        $this->order(['supplier' => 'أكوا تك', 'total_amount' => 500, 'items' => [['product_id' => $this->cartridge->id, 'quantity' => 5]]]);

        $response = $this->get(route('inventory.purchase-orders.index'))
            ->assertOk()
            ->assertSee('المخزن')
            ->assertDontSee('طلبيات الوارد</h1>', false)
            ->assertSee('PO-0001');

        $this->assertSame(['orders' => 2, 'amount' => 10500.0, 'pieces' => 28], $response->viewData('summary'));
    }

    public function test_it_searches_by_order_number_supplier_and_product(): void
    {
        $this->order();
        $this->order(['supplier' => 'أكوا تك', 'items' => [['product_id' => $this->cartridge->id, 'quantity' => 5]]]);

        $this->assertCount(1, $this->get(route('inventory.purchase-orders.index', ['q' => 'PO-0002']))->viewData('orders')->items());
        $this->assertCount(1, $this->get(route('inventory.purchase-orders.index', ['q' => 'النيل']))->viewData('orders')->items());
        $this->assertCount(1, $this->get(route('inventory.purchase-orders.index', ['q' => 'فلتر سبع']))->viewData('orders')->items());
        $this->assertSame(1, $this->get(route('inventory.purchase-orders.index', ['q' => 'أكوا تك']))->viewData('summary')['orders']);
    }

    public function test_preview_panel_shows_the_order_contents(): void
    {
        $this->order(['note' => 'وصلت مع مندوب المورد']);

        $this->get(route('inventory.purchase-orders.panel', PurchaseOrder::sole()))
            ->assertOk()
            ->assertDontSee('<html', false)
            ->assertSee('PO-0001')
            ->assertSee('فلتر سبع مراحل')
            ->assertSee('وصلت مع مندوب المورد');
    }

    public function test_index_query_count_does_not_grow_with_the_orders(): void
    {
        foreach (range(1, 6) as $i) {
            $this->order();
        }

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->get(route('inventory.purchase-orders.index'))->assertOk();

        $this->assertLessThanOrEqual(10, $queries);
    }

    public function test_demo_seeder_creates_orders_through_the_service_once(): void
    {
        $this->seed(PurchaseOrderSeeder::class);

        $count = PurchaseOrder::count();
        $this->assertGreaterThan(10, $count);
        $this->assertGreaterThan(0, app(StockService::class)->available($this->filter->id, $this->warehouse->id));
        $this->assertSame(PurchaseOrder::count(), PurchaseOrder::query()->where('note', 'like', '%'.PurchaseOrderSeeder::MARKER.'%')->count());
        $this->assertTrue(PurchaseOrder::query()->where('order_date', '>', today())->doesntExist());

        $this->seed(PurchaseOrderSeeder::class);
        $this->assertSame($count, PurchaseOrder::count());
    }

    public function test_deleting_an_order_takes_its_quantities_back_out_of_stock(): void
    {
        $this->order();
        $this->assertSame(3, app(StockService::class)->available($this->filter->id, $this->warehouse->id));

        $this->delete(route('inventory.purchase-orders.destroy', PurchaseOrder::sole()))->assertSessionHasNoErrors();

        $this->assertSame(0, app(StockService::class)->available($this->filter->id, $this->warehouse->id));
        $this->assertSame(0, PurchaseOrder::count());
    }

    public function test_warehouse_report_summarises_orders_and_applies_filters(): void
    {
        $branch = Warehouse::factory()->create(['name' => 'مخزن الفرع', 'is_active' => true]);
        $this->order();
        $this->order(['warehouse_id' => $branch->id, 'supplier' => 'أكوا تك', 'total_amount' => 500, 'items' => [['product_id' => $this->cartridge->id, 'quantity' => 5]]]);

        $page = $this->get(route('inventory.purchase-orders.report'))->assertOk()->assertSee('size: A4 portrait', false);
        foreach (['name="q"', 'name="warehouse_id"', 'name="supplier"', 'name="product_id"', 'name="content"', 'name="sort"', 'name="dir"'] as $field) {
            $page->assertSee($field, false);
        }
        $this->assertSame(['orders' => 2, 'amount' => 10500.0, 'suppliers' => 2, 'pieces' => 28, 'products' => 2], $page->viewData('summary'));
        $this->assertSame(['المخزن الرئيسي', 'مخزن الفرع'], $page->viewData('byWarehouse')->pluck('name')->all());
        $this->assertSame(23, $page->viewData('byWarehouse')->first()['pieces']);
        $this->assertSame(25, (int) $page->viewData('products')->firstWhere('name', 'شمعة كربون')->quantity);

        $bySupplier = $this->get(route('inventory.purchase-orders.report', ['supplier' => 'أكوا تك']))->assertOk();
        $this->assertSame(1, $bySupplier->viewData('summary')['orders']);

        $byProduct = $this->get(route('inventory.purchase-orders.report', ['product_id' => $this->filter->id]))->assertOk();
        $this->assertSame(1, $byProduct->viewData('summary')['orders']);

        $sorted = $this->get(route('inventory.purchase-orders.report', ['sort' => 'amount', 'dir' => 'asc']))->viewData('rows');
        $this->assertSame('500.00', $sorted->first()->total_amount);

        $this->get(route('inventory.purchase-orders.report', ['content' => 'summary']))
            ->assertOk()->assertDontSee('rpt-section-title">كشف الطلبيات', false);
        $this->get(route('inventory.purchase-orders.report', ['from' => '2000-01-01', 'to' => '2000-01-31']))
            ->assertOk()->assertSee('لا توجد طلبيات في هذه الفترة');
    }

    public function test_warehouse_page_links_to_its_report_and_the_report_query_count_is_constant(): void
    {
        $this->get(route('inventory.purchase-orders.index'))->assertOk()->assertSee(route('inventory.purchase-orders.report'), false);

        foreach (range(1, 5) as $i) {
            $this->order();
        }

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->get(route('inventory.purchase-orders.report'))->assertOk();

        $this->assertLessThanOrEqual(16, $queries);
    }
}
