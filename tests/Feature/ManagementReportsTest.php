<?php

namespace Tests\Feature;

use App\Domain\Complaints\Models\Complaint;
use App\Domain\Customers\Models\Customer;
use App\Domain\Gifts\Models\Gift;
use App\Domain\Inventory\Data\CustodySettlementData;
use App\Domain\Inventory\Data\ProductQuantityData;
use App\Domain\Inventory\Data\StockMovementData;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Models\PurchaseOrder;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Services\CustodySettlementService;
use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Maintenance\Enums\MaintenanceType;
use App\Domain\Payroll\Models\Payroll;
use App\Domain\Products\Enums\ProductKind;
use App\Domain\Products\Models\Product;
use App\Domain\Sales\Enums\ContractKind;
use App\Domain\Sales\Enums\ContractType;
use App\Domain\Sales\Enums\StockSource;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Users\Models\User;
use App\Support\ReportCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * ملف التقارير وتقارير الإدارة: كل رقم هنا محسوب يدويًا من سيناريو أكتوبر الثابت.
 */
class ManagementReportsTest extends TestCase
{
    use RefreshDatabase;

    private const OCTOBER = ['from' => '2026-10-01', 'to' => '2026-10-31'];

    private Warehouse $warehouse;

    private Product $device;

    private Product $candle;

    private User $technician;

    private User $salesRep;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-15');

        $this->warehouse = Warehouse::factory()->create();
        $this->device = Product::factory()->create(['name' => 'فلتر سبع مراحل', 'kind' => ProductKind::Device, 'price' => 3000, 'cost' => 2000]);
        $this->candle = Product::factory()->create(['name' => 'شمعة قطنية', 'kind' => ProductKind::Cartridge, 'price' => 100, 'cost' => 40]);
        $this->technician = User::factory()->create(['name' => 'محمود الفني', 'role' => 'فني تركيب']);
        $this->salesRep = User::factory()->create(['name' => 'هشام المندوب', 'role' => 'مندوب مبيعات']);

        foreach ([$this->device, $this->candle] as $product) {
            app(StockService::class)->record(new StockMovementData($product->id, $this->warehouse->id, StockMovementType::In, 100, '2026-01-01'));
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_the_reports_hub_links_every_report_and_is_in_the_navigation(): void
    {
        $page = $this->get(route('reports.index'))->assertOk()->assertSee('ملف التقارير');

        foreach (ReportCatalog::groups() as $group) {
            foreach ($group['reports'] as $report) {
                $page->assertSee($report['title'])->assertSee(route($report['route']), false);
            }
        }

        $this->get(route('home'))->assertSee(route('reports.index'), false);
    }

    public function test_finance_report_adds_up_every_pound_in_and_out(): void
    {
        $this->scenario();

        $page = $this->get(route('reports.finance', self::OCTOBER))->assertOk();
        $totals = $page->viewData('totals');

        // تقسيط: دُفع 1500 منها قسط 500 → مقدم 1000، ونقدي 2000
        $this->assertSame(3000.0, $totals['income']['sale_down_payments']);
        $this->assertSame(300.0, $totals['income']['maintenance_contracts']);
        $this->assertSame(500.0, $totals['income']['installments']);
        // صيانة: 100 − 10 خصم، وشمعة الضمان بلا مقابل
        $this->assertSame(90.0, $totals['income']['maintenance_services']);
        // شكوى: زيارة 50 + قطعتان × 30
        $this->assertSame(110.0, $totals['income']['complaints']);
        $this->assertSame(4000.0, $totals['expenses']['purchase_orders']);
        $this->assertSame(1500.0, $totals['expenses']['payroll']);
        $this->assertSame(4000.0, $totals['income_total']);
        $this->assertSame(5500.0, $totals['expenses_total']);
        $this->assertSame(-1500.0, $totals['net']);

        $october = $page->viewData('monthly')->firstWhere('month', '2026-10');
        $this->assertSame(4000.0, $october['income']);
        $this->assertSame(5500.0, $october['expenses']);

        $outstanding = $page->viewData('outstanding');
        $this->assertSame(1000.0, $outstanding['receivables']);
        $this->assertSame(1000.0, $outstanding['payroll_due']);
        $this->assertSame(40.0, $outstanding['gifts_cost']);
    }

    public function test_executive_report_compares_with_the_previous_period(): void
    {
        $this->scenario();
        $this->contract(['contract_date' => '2026-09-10', 'type' => ContractType::Cash, 'total' => 1000, 'paid' => 1000, 'remaining' => 0]);

        $page = $this->get(route('reports.executive', self::OCTOBER))->assertOk()->assertSee('الملخص التنفيذي');

        $this->assertSame('2026-09-01', $page->viewData('previous')->fromDate());
        $this->assertSame('2026-09-30', $page->viewData('previous')->toDate());

        $current = $page->viewData('current');
        $this->assertSame(2, $current['sales_contracts']);
        $this->assertSame(5000.0, $current['sales_value']);
        $this->assertSame(2, $current['devices_sold']);
        $this->assertSame(1, $current['maintenance_contracts']);
        $this->assertSame(2, $current['candles_serviced']);
        $this->assertSame(1, $current['complaints']);
        $this->assertSame(1, $page->viewData('before')['sales_contracts']);

        $leaders = $page->viewData('leaders');
        $this->assertSame('هشام المندوب', $leaders['sales_reps'][0]['name']);
        $this->assertSame('محمود الفني', $leaders['technicians'][0]['name']);

        $this->assertSame(1, $page->viewData('alerts')['late_candles']);
        $this->assertSame(1, $page->viewData('alerts')['open_complaints']);
    }

    public function test_custody_report_tracks_where_each_employee_custody_went(): void
    {
        $custody = app(EmployeeStockService::class);
        $custody->handOver($this->technician->id, $this->candle->id, $this->warehouse->id, 10, '2026-10-02');
        $contract = $this->contract();
        $custody->decrease($this->technician->id, $this->candle->id, 3, '2026-10-05', 'بيع', $contract);
        $custody->increase($this->technician->id, $this->candle->id, 1, '2026-10-06', 'مرتجع', $contract);
        app(CustodySettlementService::class)->create(new CustodySettlementData(
            $this->technician->id, '2026-10-10', 'جرد', [new ProductQuantityData($this->candle->id, 6)],
        ), post: true);

        $page = $this->get(route('reports.custody', self::OCTOBER))->assertOk()->assertSee('محمود الفني');

        $row = $page->viewData('employees')->firstWhere('user.id', $this->technician->id);
        $this->assertSame(10, $row['received']);
        $this->assertSame(2, $row['sales']);
        $this->assertSame(2, $row['shortage']);
        $this->assertSame(6, $row['balance']);

        $line = $page->viewData('products')->first()['products']->first();
        $this->assertSame('شمعة قطنية', $line['product']->name);
        $this->assertSame(6, $line['balance']);
        $this->assertCount(1, $page->viewData('settlements'));

        $this->get(route('reports.custody', self::OCTOBER + ['user_id' => $this->salesRep->id]))
            ->assertOk()->assertViewHas('employees', fn ($rows) => $rows->isEmpty());
    }

    public function test_maintenance_report_shows_done_late_and_due_work(): void
    {
        $this->scenario();

        $page = $this->get(route('reports.maintenance', self::OCTOBER))->assertOk();

        $summary = $page->viewData('summary');
        $this->assertSame(2, $summary['candles']);
        $this->assertSame(1, $summary['per_item']);
        $this->assertSame(1, $summary['package']);
        $this->assertSame(1, $summary['warranty']);
        $this->assertSame(90.0, $summary['revenue']);
        $this->assertSame(1, $summary['late']);
        $this->assertSame(2, $summary['due']);

        $this->assertSame('محمود الفني', $page->viewData('technicians')->first()['name']);
        // الشمعة الثالثة ميعادها 10/10 ولم تُغيَّر حتى اليوم (15/10)
        $this->assertCount(1, $page->viewData('late'));
        $this->assertSame(1, $page->viewData('late')->first()['candles']);
        $this->assertSame('الشمعة الثالثة', $page->viewData('late')->first()['labels']);
        $page->assertSee('ضمان')->assertSee('الشمعة الأولى');
    }

    public function test_after_sales_report_lists_complaint_parts_and_gifts(): void
    {
        $this->scenario();

        $page = $this->get(route('reports.after-sales', self::OCTOBER))->assertOk();

        $summary = $page->viewData('summary');
        $this->assertSame(1, $summary['complaints']);
        $this->assertSame(1, $summary['open']);
        $this->assertSame(2, $summary['parts_pieces']);
        $this->assertSame(110.0, $summary['revenue']);
        $this->assertSame(1, $summary['gifts']);
        $this->assertSame(40.0, $summary['gift_cost']);

        $this->assertSame('شمعة قطنية', $page->viewData('parts')->first()['name']);
        $page->assertSee('شمعة قطنية × 2')->assertSee('مفتوحة');
    }

    public function test_report_query_counts_do_not_grow_with_the_data(): void
    {
        $this->scenario();
        $routes = ['reports.executive', 'reports.finance', 'reports.custody', 'reports.maintenance', 'reports.after-sales'];
        $few = array_map(fn (string $route) => $this->countQueries(fn () => $this->get(route($route, self::OCTOBER))->assertOk()), $routes);

        for ($round = 0; $round < 6; $round++) {
            $this->scenario();
        }

        $many = array_map(fn (string $route) => $this->countQueries(fn () => $this->get(route($route, self::OCTOBER))->assertOk()), $routes);

        $this->assertSame(array_combine($routes, $few), array_combine($routes, $many));
    }

    /**
     * أكتوبر: عقد تقسيط (3000، مقدم 1000، قسط محصّل 500) + عقد نقدي 2000 + عقد صيانة 300،
     * شمعتان متغيّرتان (قطاعي 100−10، جملة بالضمان) وشمعة متأخرة واثنتان مستحقتان،
     * شكوى (زيارة 50 + قطعتان × 30) وهدية شمعة، طلبية وارد 4000، ومرتب 2500 صُرف منه 1500.
     */
    private function scenario(): void
    {
        $installment = $this->contract([
            'type' => ContractType::Installment, 'total' => 3000, 'paid' => 1500, 'remaining' => 1000,
            'installments_count' => 4, 'installment_amount' => 500,
        ], device: true);
        foreach ([['2026-10-05', '2026-10-06'], ['2026-11-05', null], ['2026-12-05', null], ['2027-01-05', null]] as $index => [$due, $paid]) {
            $installment->installments()->create([
                'number' => $index + 1, 'due_date' => $due, 'amount' => 500,
                'paid_at' => $paid, 'paid_amount' => $paid ? 500 : null, 'collected_by_id' => $paid ? $this->technician->id : null,
            ]);
        }

        $this->contract(['type' => ContractType::Cash, 'total' => 2000, 'paid' => 2000, 'remaining' => 0], device: true);
        $this->contract(['kind' => ContractKind::Maintenance, 'type' => ContractType::Cash, 'total' => 300, 'paid' => 300, 'remaining' => 0]);

        $installment->maintenanceItems()->createMany([
            ['product_name' => 'الشمعة الأولى', 'interval_months' => 1, 'replacement_date' => '2026-10-03', 'product_id' => $this->candle->id,
                'technician_id' => $this->technician->id, 'serviced_at' => '2026-10-04', 'service_type' => MaintenanceType::PerItem,
                'price' => 100, 'discount' => 10, 'under_warranty' => false, 'technician_commission' => 15],
            ['product_name' => 'الشمعة الثانية', 'interval_months' => 4, 'replacement_date' => '2026-10-04', 'product_id' => $this->candle->id,
                'technician_id' => $this->technician->id, 'serviced_at' => '2026-10-04', 'service_type' => MaintenanceType::Package,
                'price' => 100, 'discount' => 0, 'under_warranty' => true, 'technician_commission' => 15],
            ['product_name' => 'الشمعة الثالثة', 'interval_months' => 6, 'replacement_date' => '2026-10-10'],
            ['product_name' => 'الشمعة الرابعة', 'interval_months' => 12, 'replacement_date' => '2026-10-25'],
        ]);

        $complaint = Complaint::query()->create([
            'sales_contract_id' => $installment->id, 'technician_id' => $this->technician->id,
            'complaint_date' => '2026-10-08', 'visit_fee' => 50, 'resolved' => false,
        ]);
        $complaint->parts()->create([
            'product_id' => $this->candle->id, 'stock_source' => StockSource::Warehouse, 'warehouse_id' => $this->warehouse->id, 'quantity' => 2, 'price' => 30,
        ]);
        Gift::query()->create([
            'customer_id' => $installment->customer_id, 'technician_id' => $this->technician->id, 'product_id' => $this->candle->id,
            'stock_source' => StockSource::Warehouse, 'warehouse_id' => $this->warehouse->id, 'quantity' => 1, 'gift_date' => '2026-10-09',
        ]);

        PurchaseOrder::query()->create(['warehouse_id' => $this->warehouse->id, 'supplier' => 'مورد', 'order_date' => '2026-10-02', 'total_amount' => 4000]);

        $payroll = Payroll::query()->create(['user_id' => User::factory()->create()->id, 'month' => '2026-10-01', 'total' => 2500]);
        $payroll->payments()->create(['amount' => 1500, 'paid_at' => '2026-10-28']);
    }

    private function contract(array $attributes = [], bool $device = false): SalesContract
    {
        $contract = SalesContract::query()->create($attributes + [
            'code' => 'AQW-'.fake()->unique()->numerify('#####'),
            'kind' => ContractKind::Sale, 'type' => ContractType::Cash,
            'customer_id' => Customer::factory()->create()->id, 'sales_rep_id' => $this->salesRep->id,
            'installer_id' => $this->technician->id, 'warehouse_id' => $this->warehouse->id,
            'stock_source' => StockSource::Warehouse, 'contract_date' => '2026-10-02',
            'subtotal' => $attributes['total'] ?? 0, 'discount' => 0, 'total' => 0, 'paid' => 0, 'remaining' => 0,
        ]);

        if ($device) {
            $contract->items()->create([
                'product_id' => $this->device->id, 'product_name' => $this->device->name,
                'unit_price' => $contract->total, 'quantity' => 1, 'total' => $contract->total,
            ]);
        }

        return $contract;
    }

    private function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $callback();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    }
}
