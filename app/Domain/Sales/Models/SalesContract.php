<?php

namespace App\Domain\Sales\Models;

use App\Domain\Complaints\Models\Complaint;
use App\Domain\Customers\Models\Customer;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Maintenance\Enums\MaintenancePackageStatus;
use App\Domain\Maintenance\Models\CandleSchedule;
use App\Domain\Maintenance\Models\MaintenanceOperation;
use App\Domain\Maintenance\Models\MaintenancePackage;
use App\Domain\Maintenance\Models\MaintenancePlan;
use App\Domain\Sales\Enums\ContractKind;
use App\Domain\Sales\Enums\ContractType;
use App\Domain\Sales\Enums\StockSource;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesContract extends Model
{
    use SoftDeletes;

    public const SORTABLE = ['code', 'contract_date', 'total', 'remaining', 'created_at'];

    protected $fillable = [
        'code',
        'kind',
        'customer_id',
        'sales_rep_id',
        'installer_id',
        'warehouse_id',
        'stock_source',
        'stock_tracked',
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
        'legal_referred_at',
        'installments_count',
        'installment_period_months',
        'first_due_date',
        'installment_amount',
    ];

    protected function casts(): array
    {
        return [
            'kind' => ContractKind::class,
            'type' => ContractType::class,
            'stock_source' => StockSource::class,
            'stock_tracked' => 'boolean',
            'contract_date' => 'date',
            'installation_date' => 'date',
            'collection_date' => 'date',
            'maintenance_date' => 'date',
            'first_due_date' => 'date',
            'legal_referred_at' => 'date',
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

    /** مواعيد تغيير شمعات الجهاز (لعقود البيع المركّبة فقط) */
    public function candleSchedules(): HasMany
    {
        return $this->hasMany(CandleSchedule::class)->orderBy('position');
    }

    public function maintenanceOperations(): HasMany
    {
        return $this->hasMany(MaintenanceOperation::class);
    }

    /** عقد الصيانة الجملة الساري على هذا الجهاز (واحد على الأكثر) */
    public function activeMaintenancePackage(): HasOne
    {
        return $this->hasOne(MaintenancePackage::class)->where('status', MaintenancePackageStatus::Active->value);
    }

    /** الأجهزة المركّبة فعلًا — ما يدخل ملف الصيانة */
    public function scopeInstalledDevices(Builder $query): void
    {
        $query->where('kind', ContractKind::Sale)->whereNotNull('installation_date');
    }

    /** جهاز مركّب فعلًا يدخل ملف الصيانة */
    public function isInstalledDevice(): bool
    {
        return $this->kind === ContractKind::Sale && $this->installation_date !== null;
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'sales_contract_id')->orderByDesc('complaint_date');
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    /**
     * كل ما تعرضه لوحة معاينة العقد محمّلًا دفعة واحدة — عدد ثابت من الاستعلامات مهما كثرت
     * البنود والأقساط والزيارات.
     */
    public function loadDetails(): static
    {
        $person = fn ($query) => $query->select(['id', 'name']);
        $product = fn ($query) => $query->select(['id', 'name']);

        return $this->load([
            'customer:id,code,name,status,origin,governorate,center,village',
            'customer.phones',
            'salesRep' => $person,
            'installer' => $person,
            'warehouse:id,name',
            'maintenancePlan:id,name',
            'items',
            'installments.collectedBy' => $person,
            'maintenanceItems',
            'complaints.technician' => $person,
            'complaints.parts.product' => $product,
        ]);
    }

    public function isInstallment(): bool
    {
        return $this->type === ContractType::Installment;
    }

    public function isMaintenance(): bool
    {
        return $this->kind === ContractKind::Maintenance;
    }

    public function isReferredToLegal(): bool
    {
        return $this->legal_referred_at !== null;
    }

    public function scopeOfKind(Builder $query, ContractKind $kind): void
    {
        $query->where('kind', $kind->value);
    }

    /**
     * Total quantity reserved per product across the contract's items.
     *
     * @return array<int, int>
     */
    public function reservedQuantitiesByProduct(): array
    {
        // items() applies a default ORDER BY id; MySQL's only_full_group_by rejects
        // ordering by a non-grouped column in an aggregate, so it must be dropped here.
        return $this->items()
            ->reorder()
            ->selectRaw('product_id, SUM(quantity) as quantity')
            ->groupBy('product_id')
            ->pluck('quantity', 'product_id')
            ->map(fn ($quantity) => (int) $quantity)
            ->all();
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
        foreach (['kind', 'type', 'customer_id', 'maintenance_plan_id', 'sales_rep_id', 'installer_id'] as $column) {
            $value = trim((string) ($filters[$column] ?? ''));

            $query->when($value !== '', fn (Builder $query) => $query->where($column, $value));
        }

        $from = trim((string) ($filters['from'] ?? ''));
        $to = trim((string) ($filters['to'] ?? ''));

        $query->when($from !== '', fn (Builder $query) => $query->whereDate('contract_date', '>=', $from));
        $query->when($to !== '', fn (Builder $query) => $query->whereDate('contract_date', '<=', $to));

        $query->when(($filters['legal'] ?? '') === '1', fn (Builder $query) => $query->whereNotNull('legal_referred_at'));

        match ($filters['payment'] ?? null) {
            'open' => $query->where('remaining', '>', 0),
            'settled' => $query->where('remaining', '<=', 0),
            default => null,
        };
    }

    public function scopeApplySort(Builder $query, ?string $column, ?string $direction): void
    {
        $column = in_array($column, self::SORTABLE, true) ? $column : 'contract_date';
        $direction = strtolower((string) $direction) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($column, $direction)->orderBy('id', 'desc');
    }
}
