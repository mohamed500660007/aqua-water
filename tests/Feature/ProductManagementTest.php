<?php

namespace Tests\Feature;

use App\Domain\Inventory\Data\StockMovementData;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Products\Enums\ProductStatus;
use App\Domain\Products\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'sku' => 'AQ-PRO-7',
            'name' => 'فلتر أكوا برو 7 مراحل',
            'category' => 'فلاتر منزلية',
            'price' => 4850,
            'cost' => 3100,
            'warranty' => '3 سنوات',
            'status' => ProductStatus::Active->value,
        ], $overrides);
    }

    public function test_index_lists_products_with_their_balance(): void
    {
        $product = Product::factory()->create(['name' => 'شمعة قطنية 10 بوصة']);
        $warehouse = Warehouse::factory()->create();

        app(StockService::class)->record(new StockMovementData(
            productId: $product->id,
            warehouseId: $warehouse->id,
            type: StockMovementType::In,
            quantity: 25,
            movedAt: today()->toDateString(),
        ));

        $response = $this->get(route('products.index'))->assertOk()->assertSee($product->name);

        $this->assertSame(25, (int) $response->viewData('products')->first()->quantity);
    }

    public function test_it_creates_a_product(): void
    {
        $response = $this->post(route('products.store'), $this->validPayload());

        $product = Product::sole();

        $response->assertRedirect(route('products.show', $product));

        $this->assertSame('AQ-PRO-7', $product->sku);
        $this->assertSame('4850.00', $product->price);
        $this->assertSame(ProductStatus::Active, $product->status);
    }

    public function test_it_validates_required_unique_and_positive_values(): void
    {
        Product::factory()->create(['sku' => 'AQ-PRO-7']);

        $this->post(route('products.store'), $this->validPayload([
            'name' => '',
            'price' => -5,
            'cost' => -1,
            'status' => 'unknown',
        ]))->assertSessionHasErrors(['name', 'price', 'cost', 'status']);

        $this->post(route('products.store'), $this->validPayload())
            ->assertSessionHasErrors('sku');

        $this->assertDatabaseCount('products', 1);
    }

    public function test_it_updates_a_product(): void
    {
        $product = Product::factory()->create(['sku' => 'AQ-OLD']);

        $this->put(route('products.update', $product), $this->validPayload([
            'sku' => 'AQ-OLD',
            'name' => 'اسم بعد التعديل',
            'price' => 6000,
            'status' => ProductStatus::Inactive->value,
        ]))->assertRedirect(route('products.show', $product));

        $product->refresh();

        $this->assertSame('اسم بعد التعديل', $product->name);
        $this->assertSame('6000.00', $product->price);
        $this->assertSame(ProductStatus::Inactive, $product->status);
    }

    public function test_it_searches_and_filters_products(): void
    {
        $target = Product::factory()->create([
            'name' => 'ممبرين 100 جالون',
            'sku' => 'AQ-MEM-100',
            'category' => 'ممبرين وأغشية',
            'status' => ProductStatus::Active,
        ]);

        Product::factory()->create(['name' => 'منتج آخر', 'category' => 'إكسسوارات', 'status' => ProductStatus::Inactive]);

        foreach (['ممبرين', 'AQ-MEM-100'] as $term) {
            $results = $this->get(route('products.index', ['q' => $term]))->viewData('products');
            $this->assertCount(1, $results->items(), "فشل البحث بـ: {$term}");
            $this->assertTrue($results->first()->is($target));
        }

        $filtered = $this->get(route('products.index', [
            'category' => 'ممبرين وأغشية',
            'status' => ProductStatus::Active->value,
        ]))->viewData('products');

        $this->assertCount(1, $filtered->items());
    }

    public function test_show_page_lists_balances_and_recent_movements(): void
    {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create(['name' => 'مخزن الإسكندرية']);

        app(StockService::class)->record(new StockMovementData(
            productId: $product->id,
            warehouseId: $warehouse->id,
            type: StockMovementType::In,
            quantity: 12,
            movedAt: today()->toDateString(),
        ));

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee('مخزن الإسكندرية')
            ->assertSee('وارد');
    }

    public function test_create_and_edit_pages_render(): void
    {
        $product = Product::factory()->create();

        $this->get(route('products.create'))->assertOk()->assertSee('بيانات المنتج');
        $this->get(route('products.edit', $product))->assertOk()->assertSee($product->name);
    }

    public function test_it_soft_deletes_a_product_and_keeps_its_stock_history(): void
    {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        app(StockService::class)->record(new StockMovementData(
            productId: $product->id,
            warehouseId: $warehouse->id,
            type: StockMovementType::In,
            quantity: 5,
            movedAt: today()->toDateString(),
        ));

        $this->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'));

        $this->assertSoftDeleted($product);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'quantity' => 5]);
        $this->get(route('products.index'))->assertDontSee($product->sku);
    }
}
