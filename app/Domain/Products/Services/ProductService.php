<?php

namespace App\Domain\Products\Services;

use App\Domain\Products\Data\ProductData;
use App\Domain\Products\Models\Product;

class ProductService
{
    public function create(ProductData $data): Product
    {
        return Product::create($this->attributes($data));
    }

    public function update(Product $product, ProductData $data): Product
    {
        $product->update($this->attributes($data));

        return $product;
    }

    /**
     * حذف ناعم فقط — العقود وخطط الصيانة تحتفظ بارتباطها بالمنتج،
     * والأرصدة تبقى كما هي حتى لا يفقد سجل المخزون معناه.
     */
    public function delete(Product $product): void
    {
        $product->delete();
    }

    /** @return array<string, mixed> */
    private function attributes(ProductData $data): array
    {
        return [
            'sku' => $data->sku,
            'name' => $data->name,
            'category' => $data->category,
            'price' => $data->price,
            'cost' => $data->cost,
            'warranty' => $data->warranty,
            'status' => $data->status,
        ];
    }
}
