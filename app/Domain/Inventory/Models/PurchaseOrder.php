<?php

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * طلبية بضاعة واردة للمخزن — كل بند بسعر شراء القطعة، وإجمالي الطلبية مجموع بنودها.
 */
class PurchaseOrder extends Model
{
    protected $fillable = ['warehouse_id', 'supplier', 'order_date', 'total_amount', 'note'];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /** رقم الطلبية المعروض والمطبوع (PO-0007) */
    public function number(): string
    {
        return 'PO-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    /** بحث برقم الطلبية أو المورد أو الملاحظة أو اسم/كود أي منتج فيها */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $id = self::idFromNumber($term);

        $query->where(function (Builder $query) use ($term, $id) {
            $query->where('supplier', 'like', "%{$term}%")
                ->orWhere('note', 'like', "%{$term}%")
                ->orWhereHas('items.product', fn (Builder $product) => $product
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%"));

            if ($id > 0) {
                $query->orWhere('id', $id);
            }
        });
    }

    /** معرّف الطلبية من رقمها المعروض (PO-0007 أو 7) — صفر إن لم يكن رقمًا */
    public static function idFromNumber(string $term): int
    {
        return (int) ltrim(preg_replace('/^PO-?/i', '', trim($term)), '0');
    }

    /** @param  array<string, string|null>  $filters */
    public function scopeApplyFilters(Builder $query, array $filters): void
    {
        $warehouseId = trim((string) ($filters['warehouse_id'] ?? ''));
        $from = trim((string) ($filters['from'] ?? ''));
        $to = trim((string) ($filters['to'] ?? ''));

        $query->when($warehouseId !== '', fn (Builder $query) => $query->where('warehouse_id', $warehouseId));
        $query->when($from !== '', fn (Builder $query) => $query->whereDate('order_date', '>=', $from));
        $query->when($to !== '', fn (Builder $query) => $query->whereDate('order_date', '<=', $to));

        $supplier = trim((string) ($filters['supplier'] ?? ''));
        $productId = trim((string) ($filters['product_id'] ?? ''));

        $query->when($supplier !== '', fn (Builder $query) => $query->where('supplier', $supplier));
        $query->when($productId !== '', fn (Builder $query) => $query
            ->whereHas('items', fn (Builder $items) => $items->where('product_id', $productId)));
    }

    /** الموردون المسجلون فعلًا في الطلبيات — لقوائم الفلترة */
    public static function suppliers(): array
    {
        return static::query()
            ->whereNotNull('supplier')
            ->distinct()
            ->orderBy('supplier')
            ->pluck('supplier')
            ->all();
    }
}
