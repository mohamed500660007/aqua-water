<?php

namespace Tests\Feature;

use App\Domain\Inventory\Data\CustodySettlementData;
use App\Domain\Inventory\Data\StockMovementData;
use App\Domain\Inventory\Enums\CustodySettlementStatus;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Exceptions\SettlementAlreadyPostedException;
use App\Domain\Inventory\Models\CustodySettlement;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Services\CustodySettlementService;
use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Products\Models\Product;
use App\Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustodySettlementManagementTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;

    private Product $candle;

    private User $technician;

    private StockService $stock;

    private EmployeeStockService $employeeStock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::factory()->create();
        $this->candle = Product::factory()->create(['name' => 'شمعة فلتر']);
        $this->technician = User::factory()->create(['role' => 'فني تركيب', 'name' => 'محمد الفني']);
        $this->stock = app(StockService::class);
        $this->employeeStock = app(EmployeeStockService::class);

        $this->stock->record(new StockMovementData(
            productId: $this->candle->id,
            warehouseId: $this->warehouse->id,
            type: StockMovementType::In,
            quantity: 20,
            movedAt: today()->toDateString(),
        ));
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'user_id' => $this->technician->id,
            'type' => 'negative',
            'settlement_date' => today()->toDateString(),
            'reason' => 'عجز في الجرد',
            'post' => 1,
            'items' => [['product_id' => $this->candle->id, 'quantity' => 5]],
        ], $overrides);
    }

    private function giveTechnician(int $quantity): void
    {
        $this->employeeStock->handOver($this->technician->id, $this->candle->id, $this->warehouse->id, $quantity, today()->toDateString());
    }

    private function warehouseBalance(): int
    {
        return $this->stock->available($this->candle->id, $this->warehouse->id);
    }

    private function custodyBalance(): int
    {
        return $this->employeeStock->available($this->technician->id, $this->candle->id);
    }

    public function test_negative_settlement_zeroes_the_custody_without_touching_the_warehouse(): void
    {
        $this->giveTechnician(5);

        $this->post(route('inventory.custody-settlements.store'), $this->payload())
            ->assertRedirect(route('inventory.employee-stocks.show', $this->technician));

        $this->assertSame(0, $this->custodyBalance());
        $this->assertSame(15, $this->warehouseBalance());
        $this->assertDatabaseCount('stock_movements', 2);

        $settlement = CustodySettlement::firstOrFail();
        $this->assertSame(CustodySettlementStatus::Posted, $settlement->status);
        $this->assertNotNull($settlement->posted_at);
        $this->assertDatabaseHas('custody_settlement_items', ['balance_before' => 5, 'balance_after' => 0]);
        $this->assertDatabaseHas('employee_stock_movements', [
            'user_id' => $this->technician->id,
            'type' => 'out',
            'quantity' => 5,
            'balance_after' => 0,
            'reference_type' => $settlement->getMorphClass(),
            'reference_id' => $settlement->id,
        ]);
    }

    public function test_positive_settlement_adds_to_the_custody_without_touching_the_warehouse(): void
    {
        $this->post(route('inventory.custody-settlements.store'), $this->payload(['type' => 'positive', 'reason' => 'زيادة جرد']));

        $this->assertSame(5, $this->custodyBalance());
        $this->assertSame(20, $this->warehouseBalance());
        $this->assertDatabaseHas('custody_settlement_items', ['balance_before' => 0, 'balance_after' => 5]);
    }

    public function test_a_negative_settlement_cannot_exceed_the_custody_balance(): void
    {
        $this->giveTechnician(5);

        $this->post(route('inventory.custody-settlements.store'), $this->payload([
            'items' => [['product_id' => $this->candle->id, 'quantity' => 6]],
        ]))->assertSessionHasErrors('items.0.quantity');

        $this->assertSame(5, $this->custodyBalance());
        $this->assertDatabaseCount('custody_settlements', 0);
    }

    public function test_posting_twice_does_not_apply_the_settlement_twice(): void
    {
        $this->giveTechnician(5);
        $settlement = app(CustodySettlementService::class)->create(
            $this->requestData(['type' => 'positive', 'items' => [['product_id' => $this->candle->id, 'quantity' => 3]]]),
        );

        $this->post(route('inventory.custody-settlements.post', $settlement))->assertSessionHas('success');
        $this->post(route('inventory.custody-settlements.post', $settlement))->assertSessionHas('error');

        $this->assertSame(8, $this->custodyBalance());
    }

    public function test_saving_a_draft_does_not_change_any_balance(): void
    {
        $this->giveTechnician(5);

        $this->post(route('inventory.custody-settlements.store'), $this->payload(['post' => 0]));

        $this->assertSame(5, $this->custodyBalance());
        $this->assertSame(CustodySettlementStatus::Draft, CustodySettlement::firstOrFail()->status);
        $this->assertDatabaseHas('custody_settlement_items', ['balance_before' => null, 'balance_after' => null]);
    }

    public function test_a_multi_item_settlement_is_posted_atomically(): void
    {
        $filter = Product::factory()->create();
        $this->giveTechnician(5);
        $settlement = app(CustodySettlementService::class)->create($this->requestData([
            'items' => [
                ['product_id' => $this->candle->id, 'quantity' => 2],
                ['product_id' => $filter->id, 'quantity' => 1],
            ],
        ]));

        // الفلتر غير موجود في العهدة فيفشل الترحيل كله ولا يُخصم الشمع وحده
        $this->post(route('inventory.custody-settlements.post', $settlement))->assertSessionHas('error');

        $this->assertSame(5, $this->custodyBalance());
        $this->assertTrue($settlement->fresh()->isDraft());
        $this->assertDatabaseCount('employee_stock_movements', 1);
    }

    public function test_a_posted_settlement_cannot_be_edited_or_deleted(): void
    {
        $this->post(route('inventory.custody-settlements.store'), $this->payload(['type' => 'positive']));
        $settlement = CustodySettlement::firstOrFail();

        $this->delete(route('inventory.custody-settlements.destroy', $settlement))->assertSessionHas('error');
        $this->put(route('inventory.custody-settlements.update', $settlement), $this->payload(['type' => 'positive']))
            ->assertRedirect(route('inventory.custody-settlements.index'));

        $this->assertDatabaseCount('custody_settlements', 1);
        $this->assertSame(5, $this->custodyBalance());

        $this->expectException(SettlementAlreadyPostedException::class);
        app(CustodySettlementService::class)->post($settlement);
    }

    public function test_a_draft_can_be_edited_then_deleted(): void
    {
        $this->post(route('inventory.custody-settlements.store'), $this->payload(['type' => 'positive', 'post' => 0]));
        $settlement = CustodySettlement::firstOrFail();

        $this->put(route('inventory.custody-settlements.update', $settlement), $this->payload([
            'type' => 'positive',
            'post' => 0,
            'items' => [['product_id' => $this->candle->id, 'quantity' => 9]],
        ]));
        $this->assertDatabaseHas('custody_settlement_items', ['custody_settlement_id' => $settlement->id, 'quantity' => 9]);
        $this->assertDatabaseCount('custody_settlement_items', 1);

        $this->delete(route('inventory.custody-settlements.destroy', $settlement))->assertSessionHas('success');
        $this->assertDatabaseCount('custody_settlements', 0);
        $this->assertSame(0, $this->custodyBalance());
    }

    public function test_the_custody_file_shows_the_settlement_history(): void
    {
        $this->giveTechnician(5);
        $this->post(route('inventory.custody-settlements.store'), $this->payload());

        $this->get(route('inventory.employee-stocks.show', $this->technician))
            ->assertOk()
            ->assertSee('ST-0001')
            ->assertSee('عجز في الجرد')
            ->assertSee('تسوية بالسالب');
    }

    public function test_settlement_pages_render(): void
    {
        $this->post(route('inventory.custody-settlements.store'), $this->payload(['type' => 'positive', 'post' => 0]));
        $settlement = CustodySettlement::firstOrFail();

        $this->get(route('inventory.custody-settlements.index'))->assertOk()->assertSee('ST-0001');
        $this->get(route('inventory.custody-settlements.create', ['user_id' => $this->technician->id]))->assertOk();
        $this->get(route('inventory.custody-settlements.edit', $settlement))->assertOk();
        $this->get(route('inventory.custody-settlements.panel', $settlement))->assertOk()->assertSee($this->candle->name);
    }

    private function requestData(array $overrides): CustodySettlementData
    {
        return CustodySettlementData::fromArray($this->payload($overrides));
    }
}
