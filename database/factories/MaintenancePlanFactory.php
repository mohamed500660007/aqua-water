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
            'name' => 'خطة الصيانة',
            'status' => MaintenancePlanStatus::Active,
        ];
    }
}
