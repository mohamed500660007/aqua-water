<?php

namespace Database\Seeders;

use App\Domain\Inventory\Data\StockMovementData;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Products\Enums\ProductStatus;
use App\Domain\Products\Models\Product;
use Illuminate\Database\Seeder;

/**
 * المنتجات والمستودعات والرصيد الافتتاحي — الرصيد يدخل عبر حركات مخزون حقيقية.
 */
class CatalogSeeder extends Seeder
{
    public function __construct(private readonly StockService $stock) {}

    public function run(): void
    {
        $warehouses = collect([
            ['code' => 'WH-CAI', 'name' => 'المخزن الرئيسي — القاهرة', 'governorate' => 'القاهرة'],
            ['code' => 'WH-ALX', 'name' => 'مخزن الإسكندرية', 'governorate' => 'الإسكندرية'],
            ['code' => 'WH-DKH', 'name' => 'مخزن الدقهلية', 'governorate' => 'الدقهلية'],
        ])->map(fn (array $row) => Warehouse::updateOrCreate(['code' => $row['code']], $row + ['is_active' => true]));

        $mainWarehouse = $warehouses->first();

        foreach ($this->products() as $row) {
            $product = Product::updateOrCreate(
                ['sku' => $row['sku']],
                [
                    'name' => $row['name'],
                    'category' => $row['category'],
                    'price' => $row['price'],
                    'cost' => $row['cost'],
                    'warranty' => $row['warranty'],
                    'status' => ProductStatus::Active,
                ],
            );

            if ($this->stock->available($product->id, $mainWarehouse->id) === 0 && $row['stock'] > 0) {
                $this->stock->record(new StockMovementData(
                    productId: $product->id,
                    warehouseId: $mainWarehouse->id,
                    type: StockMovementType::In,
                    quantity: $row['stock'],
                    movedAt: now()->toDateString(),
                    note: 'رصيد افتتاحي',
                ));
            }
        }
    }

    /** بيانات تجريبية للمنتجات مع رصيدها الافتتاحي */
    private function products(): array
    {
        return [
            ['id' => 1,  'sku' => 'AQ-PRO-7',   'name' => 'فلتر أكوا برو 7 مراحل',       'category' => 'فلاتر منزلية',        'price' => 4850, 'cost' => 3100, 'stock' => 64,  'warranty' => '3 سنوات', 'sales' => 218,  'rating' => 4.8, 'status' => 'متاح'],
            ['id' => 2,  'sku' => 'AQ-HOME-5',  'name' => 'فلتر أكوا هوم 5 مراحل',        'category' => 'فلاتر منزلية',        'price' => 3200, 'cost' => 2050, 'stock' => 112, 'warranty' => 'سنتان',   'sales' => 341,  'rating' => 4.6, 'status' => 'متاح'],
            ['id' => 3,  'sku' => 'AQ-RO-9',    'name' => 'فلتر أكوا RO 9 مراحل قلوي',    'category' => 'فلاتر منزلية',        'price' => 6900, 'cost' => 4600, 'stock' => 27,  'warranty' => '3 سنوات', 'sales' => 96,   'rating' => 4.9, 'status' => 'متاح'],
            ['id' => 4,  'sku' => 'AQ-CTN-10',  'name' => 'شمعة قطنية 10 بوصة',           'category' => 'شمعات وفلاتر بديلة',  'price' => 65,   'cost' => 32,   'stock' => 840, 'warranty' => 'لا يوجد', 'sales' => 1920, 'rating' => 4.4, 'status' => 'متاح'],
            ['id' => 5,  'sku' => 'AQ-CRB-BL',  'name' => 'شمعة كربون بلوك',              'category' => 'شمعات وفلاتر بديلة',  'price' => 120,  'cost' => 58,   'stock' => 415, 'warranty' => 'لا يوجد', 'sales' => 1436, 'rating' => 4.5, 'status' => 'متاح'],
            ['id' => 6,  'sku' => 'AQ-MEM-100', 'name' => 'ممبرين 100 جالون يومي',        'category' => 'ممبرين وأغشية',       'price' => 780,  'cost' => 470,  'stock' => 18,  'warranty' => 'سنة',     'sales' => 402,  'rating' => 4.7, 'status' => 'منخفض'],
            ['id' => 7,  'sku' => 'AQ-TNK-32',  'name' => 'خزان مياه 3.2 جالون',          'category' => 'إكسسوارات وقطع غيار', 'price' => 640,  'cost' => 390,  'stock' => 73,  'warranty' => 'سنة',     'sales' => 288,  'rating' => 4.3, 'status' => 'متاح'],
            ['id' => 8,  'sku' => 'AQ-TAP-SS',  'name' => 'حنفية استانلس ستيل',           'category' => 'إكسسوارات وقطع غيار', 'price' => 210,  'cost' => 118,  'stock' => 0,   'warranty' => 'سنة',     'sales' => 512,  'rating' => 4.2, 'status' => 'نفد'],
            ['id' => 9,  'sku' => 'AQ-CEN-20',  'name' => 'فلتر مركزي 20 بوصة',           'category' => 'فلاتر مركزية',        'price' => 2400, 'cost' => 1550, 'stock' => 34,  'warranty' => 'سنتان',   'sales' => 74,   'rating' => 4.5, 'status' => 'متاح'],
            ['id' => 10, 'sku' => 'AQ-DLX-DS',  'name' => 'جهاز تحلية أكوا ديلوكس',       'category' => 'أجهزة تحلية',         'price' => 9500, 'cost' => 6800, 'stock' => 11,  'warranty' => '3 سنوات', 'sales' => 31,   'rating' => 4.9, 'status' => 'منخفض'],
            ['id' => 11, 'sku' => 'AQ-PMP-24',  'name' => 'مضخة ضغط 24 فولت',             'category' => 'إكسسوارات وقطع غيار', 'price' => 950,  'cost' => 610,  'stock' => 46,  'warranty' => 'سنة',     'sales' => 167,  'rating' => 4.4, 'status' => 'متاح'],
            ['id' => 12, 'sku' => 'AQ-ALK-01',  'name' => 'شمعة مياه قلوية Alkaline',     'category' => 'شمعات وفلاتر بديلة',  'price' => 340,  'cost' => 195,  'stock' => 129, 'warranty' => 'لا يوجد', 'sales' => 623,  'rating' => 4.6, 'status' => 'متاح'],
            ['id' => 13, 'sku' => 'AQ-UV-11',   'name' => 'مصباح UV معقم 11 وات',         'category' => 'إكسسوارات وقطع غيار', 'price' => 690,  'cost' => 420,  'stock' => 22,  'warranty' => 'سنة',     'sales' => 138,  'rating' => 4.5, 'status' => 'متاح'],
            ['id' => 14, 'sku' => 'AQ-MNT-YR',  'name' => 'باقة صيانة سنوية شاملة',       'category' => 'باقات الصيانة',       'price' => 1200, 'cost' => 640,  'stock' => 999, 'warranty' => 'سنة',     'sales' => 366,  'rating' => 4.7, 'status' => 'متاح'],
            ['id' => 15, 'sku' => 'AQ-CEN-40',  'name' => 'فلتر مركزي 40 بوصة تجاري',     'category' => 'فلاتر مركزية',        'price' => 5800, 'cost' => 4100, 'stock' => 8,   'warranty' => 'سنتان',   'sales' => 19,   'rating' => 4.6, 'status' => 'منخفض'],
        ];
    }
}
