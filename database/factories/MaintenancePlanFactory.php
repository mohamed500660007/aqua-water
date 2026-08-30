<?php

namespace Database\Factories;

use App\Domain\Maintenance\Enums\MaintenancePlanStatus;
use App\Domain\Maintenance\Models\MaintenancePlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenancePlan>
 */
class MaintenancePlanFactory extends Factory
{
    protected $model = MaintenancePlan::class;

    public function definition(): array
    {
        return [
            'name' => 'خطة '.fake()->unique()->word(),
            'description' => 'خطة صيانة دورية',
            'duration_months' => 12,
            'status' => MaintenancePlanStatus::Active,
        ];
    }

    /** خطة ببنود جاهزة — تُستخدم في الاختبارات وربط العقود */
    public function withItems(array $items): static
    {
        return $this->afterCreating(function (MaintenancePlan $plan) use ($items) {
            foreach ($items as $item) {
                $plan->items()->create([
                    'product_id' => $item['product_id'],
                    'interval_months' => $item['interval_months'] ?? 6,
                    'replacement_date' => $item['replacement_date'] ?? now()->addMonths(6)->toDateString(),
                ]);
            }
        });
    }
}
