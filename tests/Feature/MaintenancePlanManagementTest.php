<?php

namespace Tests\Feature;

use App\Domain\Maintenance\Enums\MaintenancePlanStatus;
use App\Domain\Maintenance\Models\MaintenancePlan;
use App\Domain\Products\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenancePlanManagementTest extends TestCase
{
    use RefreshDatabase;

    private Product $filter;

    private Product $membrane;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filter = Product::factory()->create(['name' => 'شمعة قطنية 10 بوصة', 'price' => 65]);
        $this->membrane = Product::factory()->create(['name' => 'ممبرين 100 جالون', 'price' => 780]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'باقة صيانة سنوية شاملة',
            'description' => 'استبدال الشمعات والممبرين خلال سنة',
            'duration_months' => 12,
            'status' => MaintenancePlanStatus::Active->value,
            'items' => [
                ['product_id' => $this->filter->id, 'replacement_date' => '2026-12-01'],
                ['product_id' => $this->membrane->id, 'replacement_date' => '2027-08-01'],
            ],
        ], $overrides);
    }

    public function test_index_lists_plans(): void
    {
        $plan = MaintenancePlan::factory()->create(['name' => 'باقة نصف سنوية']);

        $this->get(route('maintenance-plans.index'))
            ->assertOk()
            ->assertSee($plan->name);
    }

    public function test_it_creates_a_plan_with_its_items(): void
    {
        $response = $this->post(route('maintenance-plans.store'), $this->validPayload());

        $plan = MaintenancePlan::sole();

        $response->assertRedirect(route('maintenance-plans.show', $plan));

        $this->assertSame('باقة صيانة سنوية شاملة', $plan->name);
        $this->assertSame(12, $plan->duration_months);
        $this->assertSame(MaintenancePlanStatus::Active, $plan->status);
        $this->assertCount(2, $plan->items);

        $this->assertDatabaseHas('maintenance_plan_items', [
            'maintenance_plan_id' => $plan->id,
            'product_id' => $this->filter->id,
        ]);
    }

    public function test_it_requires_a_name_and_at_least_one_item(): void
    {
        $this->post(route('maintenance-plans.store'), $this->validPayload(['name' => '', 'items' => []]))
            ->assertSessionHasErrors(['name', 'items']);

        $this->assertDatabaseCount('maintenance_plans', 0);
    }

    public function test_it_rejects_invalid_intervals_dates_and_duplicate_products(): void
    {
        $this->post(route('maintenance-plans.store'), $this->validPayload([
            'items' => [
                ['product_id' => $this->filter->id, 'replacement_date' => 'ليس تاريخًا'],
                ['product_id' => $this->filter->id, 'replacement_date' => '2026-12-01'],
            ],
        ]))->assertSessionHasErrors([
            'items.0.replacement_date',
            'items.0.product_id',
        ]);

        $this->post(route('maintenance-plans.store'), $this->validPayload([
            'items' => [
                ['product_id' => 9999, 'replacement_date' => '2026-12-01'],
            ],
        ]))->assertSessionHasErrors('items.0.product_id');

        $this->assertDatabaseCount('maintenance_plans', 0);
    }

    public function test_empty_item_rows_are_ignored(): void
    {
        $this->post(route('maintenance-plans.store'), $this->validPayload([
            'items' => [
                ['product_id' => $this->filter->id, 'replacement_date' => '2026-12-01'],
                ['product_id' => '', 'replacement_date' => ''],
            ],
        ]))->assertSessionHasNoErrors();

        $this->assertCount(1, MaintenancePlan::sole()->items);
    }

    public function test_it_updates_a_plan_and_syncs_its_items(): void
    {
        $this->post(route('maintenance-plans.store'), $this->validPayload());
        $plan = MaintenancePlan::sole();

        $this->put(route('maintenance-plans.update', $plan), $this->validPayload([
            'name' => 'باقة معدّلة',
            'status' => MaintenancePlanStatus::Inactive->value,
            'items' => [
                ['product_id' => $this->membrane->id, 'replacement_date' => '2027-01-01'],
            ],
        ]))->assertRedirect(route('maintenance-plans.show', $plan));

        $plan->refresh()->load('items');

        $this->assertSame('باقة معدّلة', $plan->name);
        $this->assertSame(MaintenancePlanStatus::Inactive, $plan->status);
        $this->assertCount(1, $plan->items);
        $this->assertSame($this->membrane->id, $plan->items->first()->product_id);
        $this->assertSame('2027-01-01', $plan->items->first()->replacement_date->format('Y-m-d'));
        $this->assertNull($plan->items->first()->interval_months);
        $this->assertDatabaseMissing('maintenance_plan_items', [
            'maintenance_plan_id' => $plan->id,
            'product_id' => $this->filter->id,
        ]);
    }

    public function test_it_shows_plan_details_with_items(): void
    {
        $this->post(route('maintenance-plans.store'), $this->validPayload());
        $plan = MaintenancePlan::sole();

        $this->get(route('maintenance-plans.show', $plan))
            ->assertOk()
            ->assertSee($plan->name)
            ->assertSee($this->filter->name)
            ->assertSee($this->membrane->name);
    }

    public function test_create_and_edit_pages_render(): void
    {
        $plan = MaintenancePlan::factory()->create();

        $this->get(route('maintenance-plans.create'))->assertOk()->assertSee('بنود الخطة');
        $this->get(route('maintenance-plans.edit', $plan))->assertOk()->assertSee($plan->name);
    }

    public function test_it_soft_deletes_a_plan(): void
    {
        $plan = MaintenancePlan::factory()->create();

        $this->delete(route('maintenance-plans.destroy', $plan))
            ->assertRedirect(route('maintenance-plans.index'));

        $this->assertSoftDeleted($plan);
        $this->get(route('maintenance-plans.index'))->assertDontSee($plan->name);
    }
}
