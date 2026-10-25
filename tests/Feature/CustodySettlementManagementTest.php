<?php

namespace Tests\Feature;

use App\Domain\Inventory\Data\CustodySettlementData;
use App\Domain\Inventory\Data\StockMovementData;
use App\Domain\Inventory\Enums\CustodySettlementStatus;
use App\Domain\Inventory\Enums\CustodySettlementType;
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

/**
 * تسوية العهدة بالجرد: الكمية الفعلية التي رجع بها الموظف مقابل رصيد عهدته — الأقل عجز (سالب)
 * والأكثر زيادة (موجب)، ولا يتغير أي مخزن.
 */
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

        $this->receive($this->candle, 50);
    }

    public function test_returning_less_than_the_custody_is_a_negative_settlement(): void
    {
        $this->giveTechnician(10);

        // أخذ 10 ورجع بـ 8 = عجز 2
        $this->post(route('inventory.custody-settlements.store'), $this->payload(['items' => [$this->counted($this->candle, 8)]]))
            ->assertRedirect(route('inventory.employee-stocks.show', $this->technician));

        $settlement = CustodySettlement::sole();
        $this->assertSame(CustodySettlementType::Negative, $settlement->type);
        $this->assertSame(CustodySettlementStatus::Posted, $settlement->status);
        $this->assertSame(8, $this->custodyBalance());
        $this->assertSame(40, $this->warehouseBalance());
        $this->assertDatabaseHas('custody_settlement_items', [
            'counted_quantity' => 8, 'balance_before' => 10, 'balance_after' => 8, 'quantity' => 2,
        ]);
        $this->assertDatabaseHas('employee_stock_movements', [
            'type' => StockMovementType::Out->value, 'quantity' => 2, 'balance_after' => 8,
            'reference_type' => $settlement->getMorphClass(), 'reference_id' => $settlement->id,
        ]);
    }

    public function test_returning_more_than_the_custody_is_a_positive_settlement(): void
    {
        $this->giveTechnician(10);

        $this->post(route('inventory.custody-settlements.store'), $this->payload(['items' => [$this->counted($this->candle, 12)]]));

        $this->assertSame(CustodySettlementType::Positive, CustodySettlement::sole()->type);
        $this->assertSame(12, $this->custodyBalance());
        $this->assertSame(40, $this->warehouseBalance());
        $this->assertDatabaseHas('custody_settlement_items', ['balance_before' => 10, 'balance_after' => 12, 'quantity' => 2]);
    }

    public function test_one_count_can_hold_a_shortage_and_a_surplus(): void
    {
        $filter = Product::factory()->create();
        $this->receive($filter, 10);
        $this->giveTechnician(10);
        $this->employeeStock->handOver($this->technician->id, $filter->id, $this->warehouse->id, 3, today()->toDateString());

        $this->post(route('inventory.custody-settlements.store'), $this->payload([
            'items' => [$this->counted($this->candle, 7), $this->counted($filter, 5)],
        ]))->assertSessionHasNoErrors();

        $this->assertSame(CustodySettlementType::Mixed, CustodySettlement::sole()->type);
        $this->assertSame(7, $this->custodyBalance());
        $this->assertSame(5, $this->employeeStock->available($this->technician->id, $filter->id));
    }

    public function test_a_count_that_matches_the_custody_is_rejected(): void
    {
        $this->giveTechnician(10);

        $this->post(route('inventory.custody-settlements.store'), $this->payload(['items' => [$this->counted($this->candle, 10)]]))
            ->assertSessionHasErrors('items');
        $this->post(route('inventory.custody-settlements.store'), $this->payload(['items' => [$this->counted($this->candle, -1)]]))
            ->assertSessionHasErrors('items.0.counted_quantity');

        $this->assertDatabaseCount('custody_settlements', 0);
        $this->assertSame(10, $this->custodyBalance());
    }

    public function test_a_draft_previews_the_difference_without_changing_any_balance(): void
    {
        $this->giveTechnician(10);

        $this->post(route('inventory.custody-settlements.store'), $this->payload(['post' => 0, 'items' => [$this->counted($this->candle, 8)]]));

        $this->assertSame(10, $this->custodyBalance());
        $this->assertTrue(CustodySettlement::sole()->isDraft());
        $this->assertSame(CustodySettlementType::Negative, CustodySettlement::sole()->type);
        $this->assertDatabaseHas('custody_settlement_items', ['balance_before' => 10, 'balance_after' => 8, 'quantity' => 2]);
    }

    public function test_posting_recalculates_the_difference_from_the_custody_at_that_moment(): void
    {
        $this->giveTechnician(10);
        $settlement = $this->service()->create($this->data(['items' => [$this->counted($this->candle, 8)]]));

        // الموظف استلم 5 أخرى بعد حفظ المسودة: الجرد (8) يُقارن بالعهدة الجديدة (15)
        $this->giveTechnician(5);
        $this->post(route('inventory.custody-settlements.post', $settlement))->assertSessionHas('success');

        $this->assertSame(8, $this->custodyBalance());
        $this->assertDatabaseHas('custody_settlement_items', ['balance_before' => 15, 'balance_after' => 8, 'quantity' => 7]);
    }

    public function test_posting_a_count_that_now_matches_the_custody_fails_and_keeps_the_draft(): void
    {
        $this->giveTechnician(10);
        $settlement = $this->service()->create($this->data(['items' => [$this->counted($this->candle, 12)]]));
        $this->giveTechnician(2);

        $this->post(route('inventory.custody-settlements.post', $settlement))->assertSessionHas('error');

        $this->assertTrue($settlement->fresh()->isDraft());
        $this->assertSame(12, $this->custodyBalance());
    }

    public function test_posting_twice_does_not_apply_the_settlement_twice(): void
    {
        $this->giveTechnician(10);
        $settlement = $this->service()->create($this->data(['items' => [$this->counted($this->candle, 8)]]));

        $this->post(route('inventory.custody-settlements.post', $settlement))->assertSessionHas('success');
        $this->post(route('inventory.custody-settlements.post', $settlement))->assertSessionHas('error');

        $this->assertSame(8, $this->custodyBalance());
    }

    public function test_a_posted_settlement_cannot_be_edited_or_deleted(): void
    {
        $this->giveTechnician(10);
        $this->post(route('inventory.custody-settlements.store'), $this->payload());
        $settlement = CustodySettlement::sole();

        $this->delete(route('inventory.custody-settlements.destroy', $settlement))->assertSessionHas('error');
        $this->put(route('inventory.custody-settlements.update', $settlement), $this->payload(['items' => [$this->counted($this->candle, 1)]]))
            ->assertRedirect(route('inventory.custody-settlements.index'));

        $this->assertDatabaseCount('custody_settlements', 1);
        $this->assertSame(8, $this->custodyBalance());

        $this->expectException(SettlementAlreadyPostedException::class);
        $this->service()->post($settlement);
    }

    public function test_a_draft_can_be_edited_then_deleted(): void
    {
        $this->giveTechnician(10);
        $this->post(route('inventory.custody-settlements.store'), $this->payload(['post' => 0]));
        $settlement = CustodySettlement::sole();

        $this->put(route('inventory.custody-settlements.update', $settlement), $this->payload([
            'post' => 0,
            'items' => [$this->counted($this->candle, 13)],
        ]))->assertSessionHasNoErrors();
        $this->assertSame(CustodySettlementType::Positive, $settlement->fresh()->type);
        $this->assertDatabaseHas('custody_settlement_items', ['custody_settlement_id' => $settlement->id, 'counted_quantity' => 13, 'quantity' => 3]);
        $this->assertDatabaseCount('custody_settlement_items', 1);

        $this->delete(route('inventory.custody-settlements.destroy', $settlement))->assertSessionHas('success');
        $this->assertDatabaseCount('custody_settlements', 0);
        $this->assertSame(10, $this->custodyBalance());
    }

    public function test_the_custody_file_shows_the_settlement_history(): void
    {
        $this->giveTechnician(10);
        $this->post(route('inventory.custody-settlements.store'), $this->payload());

        $this->get(route('inventory.employee-stocks.show', $this->technician))
            ->assertOk()
            ->assertSee(CustodySettlement::sole()->number())
            ->assertSee('عجز في الجرد')
            ->assertSee('تسوية بالسالب')
            ->assertSee('شمعة فلتر (−2)');
    }

    public function test_settlement_pages_render(): void
    {
        $this->giveTechnician(10);
        $this->post(route('inventory.custody-settlements.store'), $this->payload(['post' => 0]));
        $settlement = CustodySettlement::sole();

        $this->get(route('inventory.custody-settlements.index'))
            ->assertOk()
            ->assertSeeInOrder(['كود الموظف', 'الموظف', 'الكمية', 'التسوية', 'إجراءات'])
            ->assertSeeInOrder([$this->technician->code(), $this->technician->name, 'تسوية بالسالب', 'مسودة']);
        $this->get(route('inventory.custody-settlements.create', ['user_id' => $this->technician->id]))
            ->assertOk()->assertSee('الكمية الفعلية')->assertSee('تحميل كل العهدة');
        $this->get(route('inventory.custody-settlements.edit', $settlement))->assertOk();
        $this->get(route('inventory.custody-settlements.panel', $settlement))
            ->assertOk()->assertSee($this->candle->name)->assertSee('في العهدة')->assertSee('−2');
    }

    /** @return array{product_id: int, counted_quantity: int} */
    private function counted(Product $product, int $counted): array
    {
        return ['product_id' => $product->id, 'counted_quantity' => $counted];
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'user_id' => $this->technician->id,
            'settlement_date' => today()->toDateString(),
            'reason' => 'عجز في الجرد',
            'post' => 1,
            'items' => [$this->counted($this->candle, 8)],
        ], $overrides);
    }

    private function data(array $overrides): CustodySettlementData
    {
        return CustodySettlementData::fromArray($this->payload($overrides));
    }

    private function service(): CustodySettlementService
    {
        return app(CustodySettlementService::class);
    }

    private function receive(Product $product, int $quantity): void
    {
        $this->stock->record(new StockMovementData(
            productId: $product->id,
            warehouseId: $this->warehouse->id,
            type: StockMovementType::In,
            quantity: $quantity,
            movedAt: today()->toDateString(),
        ));
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
}
