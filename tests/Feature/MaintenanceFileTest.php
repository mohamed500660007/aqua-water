<?php

namespace Tests\Feature;

use App\Domain\Customers\Models\Customer;
use App\Domain\Inventory\Data\StockMovementData;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Maintenance\Enums\MaintenanceType;
use App\Domain\Maintenance\Models\MaintenancePlan;
use App\Domain\Payroll\Services\PayrollService;
use App\Domain\Products\Enums\ProductKind;
use App\Domain\Products\Models\Product;
use App\Domain\Sales\Data\SalesContractData;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Sales\Services\SalesContractService;
use App\Domain\Users\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceFileTest extends TestCase
{
    use RefreshDatabase;

    private User $technician;

    private Product $candle;

    private SalesContract $mohamed;

    private SalesContract $emad;

    protected function setUp(): void
    {
        parent::setUp();

        $warehouse = Warehouse::factory()->create();
        $this->candle = Product::factory()->create(['name' => 'شمعة قطنية', 'kind' => ProductKind::Cartridge]);
        $this->technician = User::factory()->create(['role' => User::INSTALLER_ROLE, 'maintenance_commission' => 5]);

        app(StockService::class)->record(new StockMovementData(
            productId: $this->candle->id, warehouseId: $warehouse->id, type: StockMovementType::In,
            quantity: 20, movedAt: today()->toDateString(),
        ));
        app(EmployeeStockService::class)->handOver($this->technician->id, $this->candle->id, $warehouse->id, 10, today()->toDateString());

        $this->mohamed = $this->contract('محمد', $warehouse);
        $this->emad = $this->contract('عماد', $warehouse);
    }

    private function contract(string $name, Warehouse $warehouse): SalesContract
    {
        $device = Product::factory()->create(['kind' => ProductKind::Device, 'price' => 5000]);

        return app(SalesContractService::class)->create(SalesContractData::fromArray([
            'customer_id' => Customer::factory()->create(['name' => $name])->id,
            'warehouse_id' => $warehouse->id,
            'contract_date' => today()->subYear()->toDateString(),
            'installation_date' => today()->subYear()->toDateString(),
            'maintenance_date' => today()->toDateString(),
            'type' => 'cash',
            'stock_source' => 'warehouse',
            'installer_id' => $this->technician->id,
            'items' => [['product_id' => $device->id, 'quantity' => 1]],
            'paid' => 5000,
            'maintenance_plan_id' => MaintenancePlan::current()->id,
        ]), trackStock: false);
    }

    private function custody(): int
    {
        return app(EmployeeStockService::class)->available($this->technician->id, $this->candle->id);
    }

    private function line(array $overrides = []): array
    {
        return array_merge(['product_id' => $this->candle->id, 'price' => 150, 'discount' => 20], $overrides);
    }

    public function test_the_file_lists_every_candle_that_is_not_done_yet(): void
    {
        $response = $this->get(route('maintenance.file'))
            ->assertOk()
            ->assertSeeInOrder(['كود العميل', 'اسم العميل', 'النوع', 'تاريخ الصيانة', 'الشمعة', 'تم', 'الفني'])
            ->assertSee('محمد')
            ->assertSee('عماد')
            ->assertSee('الشمعة السابعة');

        $this->assertSame(14, $response->viewData('candles')->total());
    }

    public function test_per_item_maintenance_completes_one_candle_from_the_technician_custody(): void
    {
        $second = $this->mohamed->maintenanceItems()->where('product_name', 'الشمعة الثانية')->sole();

        $this->get(route('maintenance.create', $second))->assertOk()->assertSee('محمد')->assertSee('الشمعة الثانية');

        $this->post(route('maintenance.store', $second), [
            'type' => 'per_item',
            'technician_id' => $this->technician->id,
            'date' => today()->toDateString(),
            'done' => 1,
            'lines' => [$second->id => $this->line()],
        ])->assertRedirect(route('maintenance.file'))->assertSessionHasNoErrors();

        $second->refresh();
        $this->assertTrue($second->isDone());
        $this->assertSame(MaintenanceType::PerItem, $second->service_type);
        $this->assertSame($this->candle->id, $second->product_id);
        $this->assertSame(130.0, $second->total());
        $this->assertSame('5.00', $second->technician_commission);
        $this->assertSame(9, $this->custody());

        // خرجت من الملف وحدها، وباقي شمعات محمد مستنية
        $this->assertSame(13, $this->get(route('maintenance.file'))->viewData('candles')->total());
        $this->assertSame(1, $this->get(route('maintenance.file', ['status' => 'done']))->viewData('candles')->total());

        // لا تتسجل مرتين
        $this->get(route('maintenance.create', $second))->assertRedirect(route('maintenance.file'));
    }

    public function test_package_maintenance_completes_all_remaining_candles_and_removes_the_customer(): void
    {
        $first = $this->emad->maintenanceItems()->where('product_name', 'الشمعة الأولى')->sole();
        $ids = $this->emad->maintenanceItems()->pluck('id');

        $this->post(route('maintenance.store', $first), [
            'type' => 'package',
            'technician_id' => $this->technician->id,
            'date' => today()->toDateString(),
            'done' => 1,
            // جملة بدون فلوس: المبلغ في عقد الصيانة
            'lines' => $ids->mapWithKeys(fn ($id) => [$id => $this->line(['price' => 0, 'discount' => 0])])->all(),
        ])->assertRedirect(route('maintenance.file'))->assertSessionHasNoErrors();

        $this->assertSame(7, $this->emad->maintenanceItems()->done()->where('service_type', 'package')->count());
        $this->assertSame(3, $this->custody());
        $this->get(route('maintenance.file'))->assertDontSee('عماد')->assertSee('محمد');
    }

    public function test_warranty_candles_are_free_and_not_done_only_saves_the_technician_and_date(): void
    {
        [$first, $second] = $this->mohamed->maintenanceItems()->take(2)->get()->all();

        $this->post(route('maintenance.store', $first), [
            'type' => 'per_item', 'technician_id' => $this->technician->id, 'date' => today()->toDateString(), 'done' => 1,
            'lines' => [$first->id => ['product_id' => $this->candle->id, 'under_warranty' => 1]],
        ])->assertSessionHasNoErrors();
        $this->assertSame(0.0, $first->fresh()->total());

        $nextWeek = today()->addWeek()->toDateString();
        $this->post(route('maintenance.store', $second), [
            'type' => 'per_item', 'technician_id' => $this->technician->id, 'date' => $nextWeek, 'done' => 0,
        ])->assertSessionHasNoErrors();

        $second->refresh();
        $this->assertFalse($second->isDone());
        $this->assertSame($nextWeek, $second->replacement_date->toDateString());
        $this->assertSame($this->technician->id, $second->technician_id);
        $this->assertSame(9, $this->custody());
    }

    public function test_edit_changes_only_the_technician_and_date(): void
    {
        $other = User::factory()->create(['role' => User::INSTALLER_ROLE]);
        $candle = $this->mohamed->maintenanceItems()->first();

        $this->patch(route('maintenance.update', $candle), ['technician_id' => $other->id, 'date' => '2027-01-15'])
            ->assertSessionHasNoErrors();

        $candle->refresh();
        $this->assertSame($other->id, $candle->technician_id);
        $this->assertSame('2027-01-15', $candle->replacement_date->toDateString());
        $this->assertFalse($candle->isDone());
    }

    public function test_it_validates_custody_prices_and_discount(): void
    {
        $candle = $this->mohamed->maintenanceItems()->first();
        $payload = fn (array $line) => [
            'type' => 'per_item', 'technician_id' => $this->technician->id, 'date' => today()->toDateString(), 'done' => 1,
            'lines' => [$candle->id => $line],
        ];

        $this->post(route('maintenance.store', $candle), $payload(['price' => 100]))->assertSessionHasErrors("lines.{$candle->id}.product_id");
        $this->post(route('maintenance.store', $candle), $payload($this->line(['price' => 50, 'discount' => 80])))
            ->assertSessionHasErrors("lines.{$candle->id}.discount");

        $empty = Product::factory()->create(['kind' => ProductKind::Cartridge]);
        $this->post(route('maintenance.store', $candle), $payload($this->line(['product_id' => $empty->id])))->assertSessionHasErrors('lines');

        $this->assertFalse($candle->fresh()->isDone());
        $this->assertSame(10, $this->custody());
    }

    public function test_payroll_counts_the_candles_each_technician_changed_in_the_month(): void
    {
        $candle = $this->mohamed->maintenanceItems()->first();
        $this->post(route('maintenance.store', $candle), [
            'type' => 'per_item', 'technician_id' => $this->technician->id, 'date' => today()->toDateString(), 'done' => 1,
            'lines' => [$candle->id => $this->line()],
        ]);

        $row = app(PayrollService::class)
            ->monthlyRows(CarbonImmutable::today(), User::query()->whereKey($this->technician->id)->get())
            ->first();

        $this->assertSame(1, $row['values']['maintenance_count']);
        $this->assertSame(5.0, $row['values']['maintenance_commission']);
    }

    public function test_changing_the_installation_date_keeps_the_candles_once_one_is_done(): void
    {
        $candle = $this->mohamed->maintenanceItems()->first();
        $this->post(route('maintenance.store', $candle), [
            'type' => 'per_item', 'technician_id' => $this->technician->id, 'date' => today()->toDateString(), 'done' => 1,
            'lines' => [$candle->id => $this->line()],
        ]);

        $this->mohamed->load('items');
        app(SalesContractService::class)->update($this->mohamed, SalesContractData::fromArray([
            'customer_id' => $this->mohamed->customer_id,
            'warehouse_id' => $this->mohamed->warehouse_id,
            'contract_date' => today()->subYear()->toDateString(),
            'installation_date' => today()->subMonths(6)->toDateString(),
            'maintenance_date' => today()->toDateString(),
            'type' => 'cash',
            'stock_source' => 'warehouse',
            'installer_id' => $this->technician->id,
            'items' => $this->mohamed->items->map(fn ($item) => ['product_id' => $item->product_id, 'quantity' => $item->quantity])->all(),
            'paid' => 5000,
            'maintenance_plan_id' => $this->mohamed->maintenance_plan_id,
        ]));

        $this->assertTrue($candle->fresh()->isDone());
        $this->assertSame(7, $this->mohamed->maintenanceItems()->count());
    }
}
