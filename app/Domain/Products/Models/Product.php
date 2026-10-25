<?php

namespace App\Domain\Products\Models;

use App\Domain\Inventory\Models\EmployeeStock;
use App\Domain\Inventory\Models\StockBalance;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Products\Enums\ProductKind;
use App\Domain\Products\Enums\ProductStatus;
use App\Domain\Sales\Models\SalesContractItem;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    public const SORTABLE = ['name', 'sku', 'price', 'category', 'status'];

    protected static string $factory = ProductFactory::class;

    protected $fillable = [
        'sku',
        'name',
        'category',
        'kind',
        'price',
        'cost',
        'warranty',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost' => 'decimal:2',
            'status' => ProductStatus::class,
            'kind' => ProductKind::class,
        ];
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function employeeStocks(): HasMany
    {
        return $this->hasMany(EmployeeStock::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function contractItems(): HasMany
    {
        return $this->hasMany(SalesContractItem::class);
    }

    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term) {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%");
        });
    }

    /** @param  array<string, string|null>  $filters */
    public function scopeApplyFilters(Builder $query, array $filters): void
    {
        foreach (['category', 'kind', 'status'] as $column) {
            $value = trim((string) ($filters[$column] ?? ''));

            $query->when($value !== '', fn (Builder $query) => $query->where($column, $value));
        }

        // in: له رصيد في المخازن (أو في مخزن بعينه إن حُدّد) — out: نفد منها
        $warehouseId = $filters['warehouse_id'] ?? null;
        $hasStock = fn (Builder $balances) => $balances
            ->where('quantity', '>', 0)
            ->when($warehouseId, fn (Builder $balances) => $balances->where('warehouse_id', $warehouseId));

        match ($filters['stock'] ?? null) {
            'in' => $query->whereHas('stockBalances', $hasStock),
            'out' => $query->whereDoesntHave('stockBalances', $hasStock),
            default => null,
        };
    }

    public function scopeApplySort(Builder $query, ?string $column, ?string $direction): void
    {
        $column = in_array($column, self::SORTABLE, true) ? $column : 'name';
        $direction = strtolower((string) $direction) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($column, $direction)->orderBy('id');
    }

    public static function categories(): array
    {
        return static::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();
    }
}
