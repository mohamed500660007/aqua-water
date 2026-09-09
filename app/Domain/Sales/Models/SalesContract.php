<?php

namespace App\Domain\Sales\Models;

use App\Domain\Customers\Models\Customer;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Maintenance\Models\MaintenancePlan;
use App\Domain\Maintenance\Models\MaintenanceVisit;
use App\Domain\Sales\Enums\ContractType;
use App\Domain\Sales\Enums\StockSource;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesContract extends Model
{
    use SoftDeletes;

    public const SORTABLE = ['code', 'contract_date', 'total', 'created_at'];

    protected $fillable = [
        'code',
        'customer_id',
        'sales_rep_id',
        'installer_id',
        'warehouse_id',
        'stock_source',
        'maintenance_plan_id',
        'contract_date',
        'installation_date',
        'collection_date',
        'maintenance_date',
        'type',
        'notes',
        'subtotal',
        'discount',
        'total',
        'paid',
        'remaining',
        'installments_count',
        'installment_period_months',
        'first_due_date',
        'installment_amount',
    ];

    protected function casts(): array
    {
        return [
            'type' => ContractType::class,
            'stock_source' => StockSource::class,
            'contract_date' => 'date',
            'installation_date' => 'date',
            'collection_date' => 'date',
            'maintenance_date' => 'date',
            'first_due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid' => 'decimal:2',
            'remaining' => 'decimal:2',
            'installment_amount' => 'decimal:2',
            'installments_count' => 'integer',
            'installment_period_months' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function installer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'installer_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function maintenancePlan(): BelongsTo
    {
        return $this->belongsTo(MaintenancePlan::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesContractItem::class)->orderBy('id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(SalesContractInstallment::class)->orderBy('number');
    }

    public function maintenanceItems(): HasMany
    {
        return $this->hasMany(SalesContractMaintenanceItem::class)->orderBy('replacement_date');
    }

    public function maintenanceVisits(): HasMany
    {
        return $this->hasMany(MaintenanceVisit::class, 'sales_contract_id')->orderByDesc('performed_at');
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function isInstallment(): bool
    {
        return $this->type === ContractType::Installment;
    }

    /** معرّف مصدر البضاعة الفعلي: مخزن، مندوب مبيعات، أو فني تركيب — حسب stock_source */
    public function stockSourceId(): ?int
    {
        return match ($this->stock_source) {
            StockSource::SalesRep => $this->sales_rep_id,
            StockSource::Installer => $this->installer_id,
            default => $this->warehouse_id,
        };
    }

    /** بحث بكود العقد أو اسم العميل أو كود العميل */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term) {
            $query->where('code', 'like', "%{$term}%")
                ->orWhereHas('customer', fn (Builder $customer) => $customer
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%"));
        });
    }

    /** @param  array<string, string|null>  $filters */
    public function scopeApplyFilters(Builder $query, array $filters): void
    {
        foreach (['type', 'customer_id', 'maintenance_plan_id'] as $column) {
            $value = trim((string) ($filters[$column] ?? ''));

            $query->when($value !== '', fn (Builder $query) => $query->where($column, $value));
        }

        $from = trim((string) ($filters['from'] ?? ''));
        $to = trim((string) ($filters['to'] ?? ''));

        $query->when($from !== '', fn (Builder $query) => $query->whereDate('contract_date', '>=', $from));
        $query->when($to !== '', fn (Builder $query) => $query->whereDate('contract_date', '<=', $to));
    }

    public function scopeApplySort(Builder $query, ?string $column, ?string $direction): void
    {
        $column = in_array($column, self::SORTABLE, true) ? $column : 'contract_date';
        $direction = strtolower((string) $direction) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($column, $direction)->orderBy('id', 'desc');
    }
}
