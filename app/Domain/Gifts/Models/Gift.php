<?php

namespace App\Domain\Gifts\Models;

use App\Domain\Customers\Models\Customer;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Products\Models\Product;
use App\Domain\Sales\Enums\StockSource;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * قطعة غيار أُهديت لعميل مجانًا — تُخصم من المخزن أو من عهدة الفني الذي سلّمها.
 */
class Gift extends Model
{
    public const SORTABLE = ['gift_date'];

    protected $fillable = [
        'customer_id', 'technician_id', 'product_id',
        'stock_source', 'warehouse_id', 'quantity', 'gift_date', 'note',
    ];

    protected function casts(): array
    {
        return [
            'stock_source' => StockSource::class,
            'quantity' => 'integer',
            'gift_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** بحث باسم العميل أو كوده */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->whereHas('customer', fn (Builder $customer) => $customer
            ->where('name', 'like', "%{$term}%")
            ->orWhere('code', 'like', "%{$term}%"));
    }

    /** @param  array<string, string|null>  $filters */
    public function scopeApplyFilters(Builder $query, array $filters): void
    {
        foreach (['customer_id', 'technician_id', 'product_id'] as $column) {
            $value = trim((string) ($filters[$column] ?? ''));

            $query->when($value !== '', fn (Builder $query) => $query->where($column, $value));
        }
    }

    public function scopeApplySort(Builder $query, ?string $column, ?string $direction): void
    {
        $column = in_array($column, self::SORTABLE, true) ? $column : 'gift_date';
        $direction = strtolower((string) $direction) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($column, $direction)->orderBy('id', 'desc');
    }
}
