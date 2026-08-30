<?php

namespace App\Domain\Sales\Services;

use App\Domain\Products\Models\Product;
use App\Domain\Sales\Data\SalesContractItemData;

/**
 * تسعير بنود العقد من أسعار المنتجات المحفوظة — يستخدمه التحقق والحفظ معًا.
 */
class ContractPricing
{
    /**
     * @param  list<SalesContractItemData>  $items
     * @return array{lines: list<array<string, mixed>>, subtotal: float}
     */
    public function calculate(array $items): array
    {
        $products = Product::query()
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

            $total = round((float) $product->price * $item->quantity, 2);
            $subtotal += $total;

            $lines[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'unit_price' => (float) $product->price,
                'quantity' => $item->quantity,
                'total' => $total,
            ];
        }

        return ['lines' => $lines, 'subtotal' => round($subtotal, 2)];
    }
}
