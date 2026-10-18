<?php

namespace Database\Seeders;

use App\Domain\Maintenance\Enums\MaintenancePlanStatus;
use App\Domain\Maintenance\Models\MaintenancePlan;
use App\Domain\Products\Models\Product;
use Illuminate\Database\Seeder;

class MaintenancePlanSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::query()->whereIn('sku', ['AQ-CTN-10', 'AQ-CRB-BL', 'AQ-MEM-100', 'AQ-ALK-01'])->get()->keyBy('sku');

        if ($products->isEmpty()) {
            return;
        }

        $plans = [
            [
                'name' => 'باقة صيانة سنوية شاملة',
                'description' => 'استبدال الشمعات والممبرين خلال سنة كاملة',
                'duration_months' => 12,
                'items' => [
                    ['sku' => 'AQ-CTN-10', 'months_ahead' => 3],
                    ['sku' => 'AQ-CRB-BL', 'months_ahead' => 6],
                    ['sku' => 'AQ-MEM-100', 'months_ahead' => 12],
                ],
            ],
            [
                'name' => 'باقة نصف سنوية',
                'description' => 'استبدال الشمعات الأساسية كل ستة أشهر',
                'duration_months' => 6,
                'items' => [
                    ['sku' => 'AQ-CTN-10', 'months_ahead' => 3],
                    ['sku' => 'AQ-ALK-01', 'months_ahead' => 6],
                ],
            ],
        ];

        foreach ($plans as $row) {
            $plan = MaintenancePlan::updateOrCreate(
                ['name' => $row['name']],
                [
                    'description' => $row['description'],
                    'duration_months' => $row['duration_months'],
                    'status' => MaintenancePlanStatus::Active,
                ],
            );

            foreach ($row['items'] as $item) {
                $product = $products->get($item['sku']);

                if (! $product) {
                    continue;
                }

                $plan->items()->updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'replacement_date' => now()->addMonths($item['months_ahead'])->toDateString(),
                    ],
                );
            }
        }
    }
}
