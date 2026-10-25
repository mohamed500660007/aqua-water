<?php

namespace Database\Seeders;

use App\Domain\Inventory\Data\PurchaseOrderData;
use App\Domain\Inventory\Data\PurchaseOrderItemData;
use App\Domain\Inventory\Models\PurchaseOrder;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Services\PurchaseOrderService;
use App\Domain\Products\Enums\ProductKind;
use App\Domain\Products\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * طلبيات وارد تجريبية موزّعة على آخر خمسة أشهر وعلى كل المخازن، لتجربة صفحة المخزن وتقاريره.
 *
 * تمر عبر PurchaseOrderService فتُسجَّل حركات الوارد وتزيد الأرصدة كأي طلبية حقيقية.
 * بلا Faker (غير مثبَّت في الإنتاج)، والقيم ثابتة فتتكرر النتيجة نفسها في كل بيئة.
 *
 * التشغيل: php artisan db:seed --class=PurchaseOrderSeeder
 * (يتوقف تلقائيًا إن وُجدت طلبيات تجريبية سابقة حتى لا يكررها).
 */
class PurchaseOrderSeeder extends Seeder
{
    public const MARKER = '[بيانات تجريبية]';

    private const SUPPLIERS = [
        'الشركة المصرية لأنظمة المياه', 'مؤسسة النيل للفلاتر', 'أكوا تك للاستيراد', 'الدلتا للتوريدات',
        'بيور واتر — وكيل معتمد', 'المتحدة لقطع الغيار', 'مصنع العاشر للشمعات',
    ];

    /** [عدد الأيام قبل اليوم، فهرس المورد، أصناف الطلبية بالنوع والكمية] */
    private const ORDERS = [
        [150, 0, [['device', 12], ['cartridge', 120]]],
        [138, 6, [['cartridge', 300], ['cartridge', 150]]],
        [124, 1, [['device', 8], ['other', 20]]],
        [110, 5, [['other', 40], ['other', 25], ['cartridge', 60]]],
        [96, 2, [['device', 15], ['device', 6]]],
        [83, 6, [['cartridge', 250]]],
        [70, 3, [['device', 10], ['cartridge', 80], ['other', 15]]],
        [58, 4, [['device', 5], ['other', 30]]],
        [45, 0, [['cartridge', 180], ['cartridge', 90], ['device', 7]]],
        [33, 5, [['other', 50]]],
        [21, 1, [['device', 20], ['cartridge', 100]]],
        [12, 6, [['cartridge', 400], ['cartridge', 200], ['cartridge', 120]]],
        [6, 2, [['device', 9], ['other', 12]]],
        [2, 3, [['device', 4], ['cartridge', 50], ['other', 10]]],
    ];

    public function run(PurchaseOrderService $orders): void
    {
        if (PurchaseOrder::query()->where('note', 'like', '%'.self::MARKER.'%')->exists()) {
            $this->command?->warn('الطلبيات التجريبية موجودة بالفعل — لم يُضف شيء.');

            return;
        }

        $warehouses = Warehouse::query()->active()->orderBy('id')->pluck('id')->values();
        $products = Product::query()->orderBy('id')->get(['id', 'kind', 'price', 'cost'])->groupBy(fn ($product) => $product->kind->value);

        if ($warehouses->isEmpty() || $products->isEmpty()) {
            $this->command?->error('أضف مخزنًا ومنتجات أولًا (CatalogSeeder) قبل الطلبيات التجريبية.');

            return;
        }

        $today = CarbonImmutable::today();
        $created = 0;

        foreach (self::ORDERS as $index => [$daysAgo, $supplier, $lines]) {
            $items = $this->items($products, $lines, $index);

            if ($items === []) {
                continue;
            }

            $orders->create(new PurchaseOrderData(
                warehouseId: (int) $warehouses[$index % $warehouses->count()],
                orderDate: $today->subDays($daysAgo)->toDateString(),
                items: $items,
                supplier: self::SUPPLIERS[$supplier],
                note: 'فاتورة مورد رقم '.(2400 + $index * 7).' '.self::MARKER,
            ));

            $created++;
        }

        $this->command?->info("تمت إضافة {$created} طلبية وارد تجريبية.");
    }

    /**
     * يختار لكل بند منتجًا من النوع المطلوب بالتناوب، ويدمج تكرار نفس المنتج في بند واحد
     * (الطلبية لا تقبل المنتج مرتين). سعر الشراء = تكلفة المنتج، أو 60% من سعر البيع إن لم تُسجَّل تكلفة.
     *
     * @return list<PurchaseOrderItemData>
     */
    private function items($products, array $lines, int $orderIndex): array
    {
        $quantities = [];
        $prices = [];

        foreach ($lines as $line => [$kind, $quantity]) {
            $pool = $products->get($kind) ?? $products->get(ProductKind::Other->value) ?? $products->first();
            $product = $pool[($orderIndex + $line) % $pool->count()];
            $quantities[$product->id] = ($quantities[$product->id] ?? 0) + $quantity;
            $prices[$product->id] = round((float) ($product->cost ?? $product->price * 0.6), 2);
        }

        return array_map(
            fn (int $productId, int $quantity) => new PurchaseOrderItemData($productId, $quantity, $prices[$productId]),
            array_keys($quantities),
            array_values($quantities),
        );
    }
}
