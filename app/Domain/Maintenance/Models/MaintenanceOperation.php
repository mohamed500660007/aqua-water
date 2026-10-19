<?php

namespace App\Domain\Maintenance\Models;

use App\Domain\Customers\Models\Customer;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Maintenance\Enums\MaintenanceOperationType;
use App\Domain\Sales\Enums\StockSource;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * عملية صيانة فعلية: تغيير شمعة أو أكثر لجهاز عميل، بخصم من المخزن أو من عهدة الفني.
 */
class MaintenanceOperation extends Model
{
    protected $fillable = [
        'type', 'customer_id', 'sales_contract_id', 'maintenance_package_id', 'technician_id', 'sales_rep_id',
        'stock_source', 'warehouse_id', 'serviced_at', 'total', 'technician_commission', 'sales_rep_commission', 'note',
    ];

    protected function casts(): array
    {
        return [
            'type' => MaintenanceOperationType::class,
            'stock_source' => StockSource::class,
            'serviced_at' => 'date',
            'total' => 'decimal:2',
            'technician_commission' => 'decimal:2',
            'sales_rep_commission' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(SalesContract::class, 'sales_contract_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(MaintenancePackage::class, 'maintenance_package_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MaintenanceOperationItem::class)->orderBy('position');
    }

    /** رقم العملية المعروض (MO-0007) */
    public function number(): string
    {
        return 'MO-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    /** بحث باسم العميل أو كوده أو كود عقد الجهاز */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(fn (Builder $query) => $query
            ->whereHas('customer', fn (Builder $customer) => $customer
                ->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%"))
            ->orWhereHas('device', fn (Builder $device) => $device->where('code', 'like', "%{$term}%")));
    }

    /** @param  array<string, string|null>  $filters */
    public function scopeApplyFilters(Builder $query, array $filters): void
    {
        foreach (['type', 'technician_id'] as $column) {
            $value = trim((string) ($filters[$column] ?? ''));

            $query->when($value !== '', fn (Builder $query) => $query->where($column, $value));
        }

        $from = trim((string) ($filters['from'] ?? ''));
        $to = trim((string) ($filters['to'] ?? ''));

        $query->when($from !== '', fn (Builder $query) => $query->whereDate('serviced_at', '>=', $from));
        $query->when($to !== '', fn (Builder $query) => $query->whereDate('serviced_at', '<=', $to));
    }
}
