<?php

namespace App\Domain\Sales\Services;

use App\Domain\Products\Models\Product;
use App\Domain\Sales\Data\SalesContractData;
use App\Domain\Sales\Data\SalesContractItemData;
use Illuminate\Support\Collection;

/**
 * تسعير بنود العقد من أسعار المنتجات المحفوظة — يستخدمه التحقق والحفظ معًا.
 */
class ContractPricing
{
    /**
     * عقد البيع يُسعَّر من بنوده، وعقد الصيانة سعره ثابت يُكتب يدويًا ولا بنود له.
     *
     * @param  Collection<int, Product>|null  $products  منتجات محمّلة مسبقًا (مفهرسة بالمعرّف) لتسعير دفعة عقود بلا استعلام لكل عقد
     * @return array{lines: list<array<string, mixed>>, subtotal: float}
     */
    public function forContract(SalesContractData $data, ?Collection $products = null): array
    {
        if ($data->isMaintenance()) {
            return ['lines' => [], 'subtotal' => round($data->price, 2)];
        }

        return $this->calculate($data->items, $products);
    }

    /**
     * @param  list<SalesContractItemData>  $items
     * @param  Collection<int, Product>|null  $products
     * @return array{lines: list<array<string, mixed>>, subtotal: float}
     */
    public function calculate(array $items, ?Collection $products = null): array
    {
        $products ??= Product::query()
            ->whereIn('id', array_map(fn (SalesContractItemData $item) => $item->productId, $items))
            ->get(['id', 'name', 'price'])
            ->keyBy('id');

        $lines = [];
        $subtotal = 0.0;

        foreach ($items as $item) {
            $product = $products->get($item->productId);

            if (! $product) {
                continue;
            }

            $unitPrice = $item->unitPrice ?? (float) $product->price;
            $total = round($unitPrice * $item->quantity, 2);
            $subtotal += $total;

            $lines[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'unit_price' => $unitPrice,
                'quantity' => $item->quantity,
                'total' => $total,
            ];
        }

        return ['lines' => $lines, 'subtotal' => round($subtotal, 2)];
    }
}
