<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Products\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'type',
        'quantity',
        'balance_after',
        'note',
        'moved_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'quantity' => 'integer',
            'balance_after' => 'integer',
            'moved_at' => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** المستند الذي تسبب في الحركة (عقد بيع مثلًا) */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /** @param  array<string, string|null>  $filters */
    public function scopeApplyFilters(Builder $query, array $filters): void
    {
        foreach (['product_id', 'warehouse_id', 'type'] as $column) {
            $value = trim((string) ($filters[$column] ?? ''));

            $query->when($value !== '', fn (Builder $query) => $query->where($column, $value));
        }
    }
}
