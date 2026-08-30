<?php

namespace Database\Factories;

use App\Domain\Products\Enums\ProductStatus;
use App\Domain\Products\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'sku' => 'AQ-'.fake()->unique()->bothify('???-##'),
            'name' => 'فلتر '.fake()->unique()->word(),
            'category' => fake()->randomElement(['فلاتر منزلية', 'شمعات وفلاتر بديلة', 'إكسسوارات وقطع غيار']),
            'price' => fake()->numberBetween(100, 9000),
            'cost' => fake()->numberBetween(50, 6000),
            'warranty' => 'سنة',
            'status' => ProductStatus::Active,
        ];
    }
}
