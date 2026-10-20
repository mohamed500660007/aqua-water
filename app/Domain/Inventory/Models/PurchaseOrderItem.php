<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Products\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = ['product_id', 'quantity', 'unit_price'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** بحث باسم المنتج أو كوده، أو برقم الطلبية أو المورد أو ملاحظتها */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $orderId = PurchaseOrder::idFromNumber($term);

        $query->where(fn (Builder $query) => $query
            ->whereHas('product', fn (Builder $product) => $product
                ->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%"))
            ->orWhereHas('purchaseOrder', fn (Builder $order) => $order
                ->where('supplier', 'like', "%{$term}%")
                ->orWhere('note', 'like', "%{$term}%")
                ->when($orderId > 0, fn (Builder $order) => $order->orWhere('id', $orderId))));
    }
}
