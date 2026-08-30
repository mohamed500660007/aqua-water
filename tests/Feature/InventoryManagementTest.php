<?php

namespace Tests\Feature;

use App\Domain\Inventory\Data\StockMovementData;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Products\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private Warehouse $warehouse;

    private StockService $stock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->product = Product::factory()->create(['name' => 'فلتر أكوا برو 7 مراحل']);
        $this->warehouse = Warehouse::factory()->create(['name' => 'المخزن الرئيسي']);
        $this->stock = app(StockService::class);
    }

    private function movementPayload(array $overrides = []): array
    {
        return array_merge([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => StockMovementType::In->value,
            'quantity' => 20,
            'moved_at' => today()->toDateString(),
            'note' => 'رصيد افتتاحي',
        ], $overrides);
    }

    private function receive(int $quantity): void
    {
        $this->stock->record(new StockMovementData(
            productId: $this->product->id,
            warehouseId: $this->warehouse->id,
            type: StockMovementType::In,
            quantity: $quantity,
            movedAt: today()->toDateString(),
        ));
    }

    public function test_incoming_movement_increases_the_balance(): void
    {
        $this->post(route('inventory.movements.store'), $this->movementPayload())
            ->assertRedirect(route('inventory.movements.index'));

        $this->assertSame(20, $this->stock->available($this->product->id, $this->warehouse->id));
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => StockMovementType::In->value,
            'quantity' => 20,
            'balance_after' => 20,
        ]);
    }

    public function test_outgoing_movement_decreases_the_balance(): void
    {
        $this->receive(20);

        $this->post(route('inventory.movements.store'), $this->movementPayload([
            'type' => StockMovementType::Out->value,
            'quantity' => 8,
        ]))->assertSessionHasNoErrors();

        $this->assertSame(12, $this->stock->available($this->product->id, $this->warehouse->id));
        $this->assertDatabaseHas('stock_movements', [
            'type' => StockMovementType::Out->value,
            'quantity' => 8,
            'balance_after' => 12,
        ]);
    }

    public function test_adjustment_sets_the_balance_to_the_counted_quantity(): void
    {
        $this->receive(20);

        $this->post(route('inventory.movements.store'), $this->movementPayload([
            'type' => StockMovementType::Adjustment->value,
            'quantity' => 7,
            'note' => 'جرد فعلي',
        ]))->assertSessionHasNoErrors();

        $this->assertSame(7, $this->stock->available($this->product->id, $this->warehouse->id));
    }

    public function test_it_rejects_a_movement_that_would_make_the_balance_negative(): void
    {
        $this->receive(5);

        $this->post(route('inventory.movements.store'), $this->movementPayload([
            'type' => StockMovementType::Out->value,
            'quantity' => 9,
        ]))->assertSessionHasErrors('quantity');

        $this->assertSame(5, $this->stock->available($this->product->id, $this->warehouse->id));
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_the_service_guards_against_negative_stock_as_a_last_resort(): void
    {
        $this->receive(2);

        $this->expectException(InsufficientStockException::class);

        $this->stock->record(new StockMovementData(
            productId: $this->product->id,
            warehouseId: $this->warehouse->id,
            type: StockMovementType::Out,
            quantity: 3,
            movedAt: today()->toDateString(),
        ));
    }

    public function test_it_validates_quantity_type_and_date(): void
    {
        $this->post(route('inventory.movements.store'), $this->movementPayload([
            'type' => 'unknown',
            'quantity' => -3,
            'moved_at' => now()->addDay()->toDateString(),
        ]))->assertSessionHasErrors(['type', 'quantity', 'moved_at']);

        $this->post(route('inventory.movements.store'), $this->movementPayload(['quantity' => 0]))
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_balances_are_kept_per_warehouse(): void
    {
        $other = Warehouse::factory()->create();

        $this->receive(15);
        $this->stock->record(new StockMovementData(
            productId: $this->product->id,
            warehouseId: $other->id,
            type: StockMovementType::In,
            quantity: 4,
            movedAt: today()->toDateString(),
        ));

        $this->assertSame(15, $this->stock->available($this->product->id, $this->warehouse->id));
        $this->assertSame(4, $this->stock->available($this->product->id, $other->id));

        $page = $this->get(route('inventory.index', ['warehouse_id' => $other->id]))->assertOk();
        $this->assertSame(4, (int) $page->viewData('products')->first()->quantity);
        $this->assertSame(19, (int) $this->get(route('inventory.index'))->viewData('products')->first()->quantity);
    }

    public function test_inventory_pages_render(): void
    {
        $this->receive(3);

        $this->get(route('inventory.index'))->assertOk();
        $this->get(route('inventory.movements.index'))->assertOk();
        $this->get(route('inventory.movements.create'))->assertOk();
    }
}
