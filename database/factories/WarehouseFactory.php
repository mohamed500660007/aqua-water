<?php

namespace Database\Factories;

use App\Domain\Inventory\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        return [
            'code' => 'WH-'.fake()->unique()->numerify('###'),
            'name' => 'مخزن '.fake()->unique()->city(),
            'governorate' => fake()->randomElement(['القاهرة', 'الجيزة', 'الدقهلية']),
            'is_active' => true,
        ];
    }
}
