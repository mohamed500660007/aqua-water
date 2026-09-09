<?php

namespace App\Domain\Maintenance\Models;

use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Maintenance\Enums\WarrantyStatus;
use App\Domain\Products\Models\Product;
use App\Domain\Sales\Enums\StockSource;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceVisit extends Model
{
    public const SORTABLE = ['performed_at', 'price'];

    protected $fillable = [
        'sales_contract_id', 'product_id', 'stock_source', 'warehouse_id',
        'technician_id', 'sales_rep_id', 'quantity', 'price', 'warranty_status',
        'performed_at', 'note',
    ];

    protected function casts(): array
    {
        return [
            'stock_source' => StockSource::class,
            'warranty_status' => WarrantyStatus::class,
            'quantity' => 'integer',
            'price' => 'decimal:2',
            'performed_at' => 'date',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(SalesContract::class, 'sales_contract_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    /** معرّف مصدر الشمعة الفعلي: مخزن أو عهدة فني أو مندوب — حسب stock_source */
    public function stockSourceId(): ?int
    {
        return match ($this->stock_source) {
            StockSource::SalesRep => $this->sales_rep_id,
            StockSource::Installer => $this->technician_id,
            default => $this->warehouse_id,
        };
    }

    /** بحث بكود العقد أو اسم العميل */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->whereHas('contract', fn (Builder $contract) => $contract
            ->where('code', 'like', "%{$term}%")
            ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', "%{$term}%")));
    }

    /** @param  array<string, string|null>  $filters */
    public function scopeApplyFilters(Builder $query, array $filters): void
    {
        $customerId = trim((string) ($filters['customer_id'] ?? ''));

        $query->when(
            $customerId !== '',
            fn (Builder $query) => $query->whereHas('contract', fn (Builder $q) => $q->where('customer_id', $customerId)),
        );

        $warranty = trim((string) ($filters['warranty_status'] ?? ''));

        $query->when($warranty !== '', fn (Builder $query) => $query->where('warranty_status', $warranty));
    }

    public function scopeApplySort(Builder $query, ?string $column, ?string $direction): void
    {
        $column = in_array($column, self::SORTABLE, true) ? $column : 'performed_at';
        $direction = strtolower((string) $direction) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($column, $direction)->orderBy('id', 'desc');
    }
}
