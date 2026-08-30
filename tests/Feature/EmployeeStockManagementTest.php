<?php

namespace Tests\Feature;

use App\Domain\Inventory\Data\StockMovementData;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Products\Models\Product;
use App\Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeStockManagementTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;

    private Product $product;

    private StockService $stock;

    private EmployeeStockService $employeeStock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::factory()->create();
        $this->product = Product::factory()->create(['name' => 'فلتر أكوا برو 7 مراحل']);
        $this->stock = app(StockService::class);
        $this->employeeStock = app(EmployeeStockService::class);

        $this->stock->record(new StockMovementData(
            productId: $this->product->id,
            warehouseId: $this->warehouse->id,
            type: StockMovementType::In,
            quantity: 20,
            movedAt: today()->toDateString(),
        ));
    }

    private function handoverPayload(array $overrides = []): array
    {
        return array_merge([
            'user_id' => User::factory()->create(['role' => 'مندوب مبيعات'])->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 5,
            'moved_at' => today()->toDateString(),
            'note' => 'جولة مبيعات',
        ], $overrides);
    }

    public function test_it_hands_over_stock_from_the_warehouse_to_an_employee(): void
    {
        $employee = User::factory()->create(['role' => 'فني تركيب']);

        $this->post(route('inventory.employee-stocks.store'), $this->handoverPayload([
            'user_id' => $employee->id,
            'quantity' => 6,
        ]))->assertRedirect(route('inventory.employee-stocks.index'));

        $this->assertSame(14, $this->stock->available($this->product->id, $this->warehouse->id));
        $this->assertSame(6, $this->employeeStock->available($employee->id, $this->product->id));

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'out',
            'quantity' => 6,
        ]);
        $this->assertDatabaseHas('employee_stock_movements', [
            'user_id' => $employee->id,
            'product_id' => $this->product->id,
            'type' => 'in',
            'quantity' => 6,
            'balance_after' => 6,
        ]);
    }

    public function test_handing_over_twice_accumulates_the_employee_balance(): void
    {
        $employee = User::factory()->create(['role' => 'مندوب مبيعات']);

        $this->post(route('inventory.employee-stocks.store'), $this->handoverPayload(['user_id' => $employee->id, 'quantity' => 4]));
        $this->post(route('inventory.employee-stocks.store'), $this->handoverPayload(['user_id' => $employee->id, 'quantity' => 3]));

        $this->assertSame(7, $this->employeeStock->available($employee->id, $this->product->id));
        $this->assertSame(13, $this->stock->available($this->product->id, $this->warehouse->id));
    }

    public function test_it_rejects_a_handover_that_exceeds_the_warehouse_balance(): void
    {
        $this->post(route('inventory.employee-stocks.store'), $this->handoverPayload(['quantity' => 25]))
            ->assertSessionHasErrors('quantity');

        $this->assertSame(20, $this->stock->available($this->product->id, $this->warehouse->id));
        $this->assertDatabaseCount('employee_stock_movements', 0);
    }

    public function test_it_rejects_a_user_who_is_not_a_sales_rep_or_installer(): void
    {
        $manager = User::factory()->create(['role' => 'مدير النظام']);

        $this->post(route('inventory.employee-stocks.store'), $this->handoverPayload(['user_id' => $manager->id]))
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseCount('employee_stock_movements', 0);
    }

    public function test_it_validates_quantity_and_date(): void
    {
        $this->post(route('inventory.employee-stocks.store'), $this->handoverPayload([
            'quantity' => 0,
            'moved_at' => now()->addDay()->toDateString(),
        ]))->assertSessionHasErrors(['quantity', 'moved_at']);

        $this->assertDatabaseCount('employee_stock_movements', 0);
    }

    public function test_the_service_guards_against_negative_employee_balance(): void
    {
        $employee = User::factory()->create(['role' => 'فني تركيب']);
        $this->employeeStock->handOver($employee->id, $this->product->id, $this->warehouse->id, 2, today()->toDateString());

        $this->expectException(InsufficientStockException::class);

        $this->employeeStock->decrease($employee->id, $this->product->id, 5, today()->toDateString());
    }

    public function test_index_lists_employees_and_what_they_currently_hold(): void
    {
        $employee = User::factory()->create(['role' => 'مندوب مبيعات', 'name' => 'ريهام عبد الفتاح']);
        $this->employeeStock->handOver($employee->id, $this->product->id, $this->warehouse->id, 4, today()->toDateString());

        $this->get(route('inventory.employee-stocks.index'))
            ->assertOk()
            ->assertSee($employee->name)
            ->assertSee($this->product->name);
    }

    public function test_index_hides_employees_with_a_zero_balance(): void
    {
        $employee = User::factory()->create(['role' => 'مندوب مبيعات', 'name' => 'دينا عادل رمضان']);
        $this->employeeStock->handOver($employee->id, $this->product->id, $this->warehouse->id, 3, today()->toDateString());
        $this->employeeStock->decrease($employee->id, $this->product->id, 3, today()->toDateString());

        $this->get(route('inventory.employee-stocks.index'))
            ->assertOk()
            ->assertDontSee($employee->name);
    }

    public function test_create_page_renders(): void
    {
        User::factory()->create(['role' => 'مندوب مبيعات']);

        $this->get(route('inventory.employee-stocks.create'))
            ->assertOk()
            ->assertSee('تسليم بضاعة لموظف');
    }
}
