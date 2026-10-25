<?php

namespace Database\Factories;

use App\Domain\Customers\Enums\CustomerStatus;
use App\Domain\Customers\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'code' => 'CUS-'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'name' => fake()->name(),
            'national_id' => fake()->unique()->numerify('2#############'),
            'governorate' => fake()->randomElement(['القاهرة', 'الجيزة', 'الدقهلية']),
            'center' => fake()->randomElement(['المنصورة', 'طلخا', 'ميت غمر']),
            'village' => fake()->randomElement(['كفر الجنينة', 'ميت مزاح', 'البقاشين']),
            'status' => fake()->randomElement(CustomerStatus::cases()),
            'notes' => null,
            'registered_at' => fake()->dateTimeBetween('-2 years')->format('Y-m-d'),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Customer $customer) {
            if ($customer->phones()->exists()) {
                return;
            }

            $customer->phones()->create([
                'phone' => '01'.fake()->numerify('#########'),
                'is_primary' => true,
            ]);
        });
    }

    /** @param  array<int, string>  $numbers */
    public function withPhones(array $numbers): static
    {
        return $this->afterCreating(function (Customer $customer) use ($numbers) {
            $customer->phones()->delete();

            foreach ($numbers as $index => $number) {
                $customer->phones()->create([
                    'phone' => $number,
                    'is_primary' => $index === 0,
                ]);
            }
        });
    }
}
