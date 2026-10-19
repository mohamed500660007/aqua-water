<?php

namespace Tests\Feature;

use App\Domain\Customers\Models\Customer;
use App\Domain\Inventory\Data\StockMovementData;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Maintenance\Enums\MaintenancePackageStatus;
use App\Domain\Maintenance\Models\CandleSchedule;
use App\Domain\Maintenance\Models\MaintenanceOperation;
use App\Domain\Maintenance\Models\MaintenancePackage;
use App\Domain\Maintenance\Services\CandleStageService;
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

    private Warehouse $warehouse;

    private Product $sediment;

    private Product $carbon;

    private User $technician;

    private User $salesRep;

    private SalesContract $device;

    private CarbonImmutable $installedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::factory()->create();
        $this->sediment = Product::factory()->create(['name' => 'شمعة قطنية', 'price' => 150, 'kind' => ProductKind::Cartridge]);
        $this->carbon = Product::factory()->create(['name' => 'شمعة كربون', 'price' => 100, 'kind' => ProductKind::Cartridge]);
        $this->technician = User::factory()->create(['role' => 'فني تركيب', 'maintenance_commission' => 5]);
        $this->salesRep = User::factory()->create(['role' => 'مندوبة', 'maintenance_commission' => 5]);

        foreach ([$this->sediment, $this->carbon] as $candle) {
            app(StockService::class)->record(new StockMovementData(
                productId: $candle->id, warehouseId: $this->warehouse->id, type: StockMovementType::In,
                quantity: 30, movedAt: today()->toDateString(),
            ));
        }

        // المرحلة 1 كل شهر، والمرحلة 2 كل شهرين
        app(CandleStageService::class)->save([
            1 => ['product_id' => $this->sediment->id, 'interval_months' => 1],
            2 => ['product_id' => $this->carbon->id, 'interval_months' => 2],
        ]);

        $this->installedAt = CarbonImmutable::today()->subMonths(3);
        $this->device = $this->installDevice($this->installedAt);
    }

    private function installDevice(CarbonImmutable $installedAt): SalesContract
    {
        $device = Product::factory()->create(['kind' => ProductKind::Device, 'price' => 5000]);

        return app(SalesContractService::class)->create(SalesContractData::fromArray([
            'customer_id' => Customer::factory()->create()->id,
            'warehouse_id' => $this->warehouse->id,
            'contract_date' => $installedAt->toDateString(),
            'installation_date' => $installedAt->toDateString(),
            'maintenance_date' => $installedAt->toDateString(),
            'type' => 'cash',
            'stock_source' => 'warehouse',
            'installer_id' => $this->technician->id,
            'sales_rep_id' => $this->salesRep->id,
            'items' => [['product_id' => $device->id, 'quantity' => 1]],
            'paid' => 5000,
        ]), trackStock: false);
    }

    private function operation(array $overrides = [])
    {
        return $this->from(route('maintenance.operations.create', ['contract' => $this->device->id]))
            ->post(route('maintenance.operations.store'), array_merge([
                'sales_contract_id' => $this->device->id,
                'type' => 'per_item',
                'positions' => [1, 2],
                'prices' => [1 => 150, 2 => 100],
                'technician_id' => $this->technician->id,
                'sales_rep_id' => $this->salesRep->id,
                'stock_source' => 'warehouse',
                'warehouse_id' => $this->warehouse->id,
                'serviced_at' => today()->toDateString(),
            ], $overrides));
    }

    private function dueDate(int $position): string
    {
        return CandleSchedule::where('sales_contract_id', $this->device->id)->where('position', $position)->value('next_due_at')->toDateString();
    }

    public function test_installing_a_device_creates_its_schedule_from_the_installation_date(): void
    {
        $this->assertSame($this->installedAt->addMonth()->toDateString(), $this->dueDate(1));
        $this->assertSame($this->installedAt->addMonths(2)->toDateString(), $this->dueDate(2));

        $this->get(route('maintenance.file'))->assertOk()->assertSee($this->device->code);
    }

    public function test_a_per_item_operation_prices_each_candle_pays_commissions_and_renews_the_schedule(): void
    {
        $this->operation()->assertRedirect(route('maintenance.file'))->assertSessionHas('success');

        $operation = MaintenanceOperation::with('items')->sole();
        $this->assertSame('250.00', $operation->total);
        // العمولة المقترحة: 5 ج.م × شمعتين لكل من الفني والموظفة
        $this->assertSame('10.00', $operation->technician_commission);
        $this->assertSame('10.00', $operation->sales_rep_commission);
        $this->assertSame([150.0, 100.0], $operation->items->pluck('unit_price')->map(fn ($p) => (float) $p)->all());

        $this->assertSame(29, app(StockService::class)->available($this->sediment->id, $this->warehouse->id));
        $this->assertSame(today()->addMonth()->toDateString(), $this->dueDate(1));
        $this->assertSame(today()->addMonths(2)->toDateString(), $this->dueDate(2));

        // الجهاز ينسحب من قائمة المستحقين الحالية
        $this->get(route('maintenance.file'))->assertOk()->assertDontSee($this->device->code);
    }

    public function test_the_sales_rep_can_be_excluded_and_candles_can_come_from_the_technician_custody(): void
    {
        app(EmployeeStockService::class)->handOver($this->technician->id, $this->sediment->id, $this->warehouse->id, 3, today()->toDateString());

        $this->operation([
            'positions' => [1],
            'prices' => [1 => 150],
            'exclude_sales_rep' => 1,
            'stock_source' => 'installer',
        ])->assertSessionHasNoErrors();

        $operation = MaintenanceOperation::sole();
        $this->assertNull($operation->sales_rep_id);
        $this->assertSame('0.00', $operation->sales_rep_commission);
        $this->assertSame(2, app(EmployeeStockService::class)->available($this->technician->id, $this->sediment->id));
    }

    public function test_it_rejects_candles_the_source_does_not_have(): void
    {
        $this->operation(['stock_source' => 'installer'])->assertSessionHasErrors('positions');

        $this->assertDatabaseCount('maintenance_operations', 0);
    }

    public function test_a_package_is_a_candle_balance_consumed_by_visits_and_closed_when_used_up(): void
    {
        $this->post(route('maintenance.packages.store'), [
            'sales_contract_id' => $this->device->id,
            'candles' => 2,
            'gift_candles' => 1,
            'technician_id' => $this->technician->id,
            'sales_rep_id' => $this->salesRep->id,
            'technician_commission' => 70,
            'sales_rep_commission' => 30,
            'started_at' => today()->toDateString(),
        ])->assertSessionHasNoErrors();

        $package = MaintenancePackage::sole();
        $this->assertSame(3, $package->remainingCandles());

        // أسعار الشمع ممنوعة في زيارة العقد — المبلغ مسجّل في عقد الصيانة
        $this->operation(['type' => 'package', 'maintenance_package_id' => $package->id])->assertSessionHasErrors('prices');

        $visit = fn (array $positions) => $this->operation([
            'type' => 'package', 'maintenance_package_id' => $package->id, 'positions' => $positions,
            'prices' => null, 'technician_commission' => null, 'sales_rep_commission' => null,
        ]);

        $visit([1, 2])->assertSessionHasNoErrors();
        $this->assertSame(1, $package->fresh()->remainingCandles());
        $this->assertSame('0.00', MaintenanceOperation::first()->total);

        $visit([1, 2])->assertSessionHasErrors('positions');

        $visit([1])->assertSessionHasNoErrors();
        $this->assertSame(MaintenancePackageStatus::Closed, $package->fresh()->status);
        // الشمعة القطنية (المرحلة 1) صُرفت في زيارتين من المخزن: 30 − 2
        $this->assertSame(28, app(StockService::class)->available($this->sediment->id, $this->warehouse->id));
    }

    public function test_cancelling_an_operation_returns_the_candles_and_restores_the_previous_schedule(): void
    {
        $this->operation();
        $operation = MaintenanceOperation::sole();

        $this->delete(route('maintenance.operations.destroy', $operation))->assertSessionHas('success');

        $this->assertSame(30, app(StockService::class)->available($this->sediment->id, $this->warehouse->id));
        $this->assertSame($this->installedAt->addMonth()->toDateString(), $this->dueDate(1));
        $this->assertDatabaseCount('maintenance_operations', 0);
    }

    public function test_changing_a_stage_interval_recalculates_every_device(): void
    {
        $this->put(route('maintenance.stages.update'), ['stages' => [
            1 => ['product_id' => $this->sediment->id, 'interval_months' => 6],
        ]])->assertSessionHasNoErrors();

        $this->assertSame($this->installedAt->addMonths(6)->toDateString(), $this->dueDate(1));
        $this->assertDatabaseMissing('candle_schedules', ['sales_contract_id' => $this->device->id, 'position' => 2]);
    }

    public function test_payroll_sums_per_item_and_package_commissions_for_the_month(): void
    {
        $this->operation();
        MaintenancePackage::create([
            'customer_id' => $this->device->customer_id, 'sales_contract_id' => $this->device->id,
            'technician_id' => $this->technician->id, 'candles' => 7, 'technician_commission' => 70,
            'status' => MaintenancePackageStatus::Active, 'started_at' => today()->toDateString(),
        ]);

        $row = app(PayrollService::class)->monthlyRows(CarbonImmutable::today(), collect([$this->technician]))->first();

        $this->assertSame(2, $row['automatic']['maintenance_count']);
        $this->assertSame(80.0, $row['automatic']['maintenance_commission']);
        $this->assertSame(80.0, $row['total'] - (float) $this->technician->base_salary);
    }

    public function test_maintenance_pages_render(): void
    {
        foreach ([
            route('maintenance.file'),
            route('maintenance.stages.edit'),
            route('maintenance.operations.index'),
            route('maintenance.operations.create', ['contract' => $this->device->id]),
            route('maintenance.operations.create', ['q' => $this->device->code]),
            route('maintenance.packages.index'),
            route('maintenance.packages.create', ['contract' => $this->device->id]),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
