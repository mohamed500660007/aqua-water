<?php

namespace App\Domain\Maintenance\Models;

use App\Domain\Customers\Models\Customer;
use App\Domain\Maintenance\Enums\MaintenancePackageStatus;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * عقد صيانة جملة (باكيج): رصيد شمعات في حساب العميل (الأساسية + الهدايا) يُستهلك مع كل زيارة.
 * المبلغ نفسه مسجّل في عقد الصيانة (maintenance_contract_id) ولا يُكرر هنا.
 */
class MaintenancePackage extends Model
{
    protected $fillable = [
        'customer_id', 'sales_contract_id', 'maintenance_contract_id', 'technician_id', 'sales_rep_id',
        'candles', 'gift_candles', 'used_candles', 'technician_commission', 'sales_rep_commission',
        'status', 'started_at', 'closed_at', 'note',
    ];

    protected function casts(): array
    {
        return [
            'candles' => 'integer',
            'gift_candles' => 'integer',
            'used_candles' => 'integer',
            'technician_commission' => 'decimal:2',
            'sales_rep_commission' => 'decimal:2',
            'status' => MaintenancePackageStatus::class,
            'started_at' => 'date',
            'closed_at' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** الجهاز (عقد البيع) الذي يُصان */
    public function device(): BelongsTo
    {
        return $this->belongsTo(SalesContract::class, 'sales_contract_id');
    }

    public function maintenanceContract(): BelongsTo
    {
        return $this->belongsTo(SalesContract::class, 'maintenance_contract_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function operations(): HasMany
    {
        return $this->hasMany(MaintenanceOperation::class);
    }

    /** رقم الباكيج المعروض (PK-0007) */
    public function number(): string
    {
        return 'PK-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function totalCandles(): int
    {
        return $this->candles + $this->gift_candles;
    }

    public function remainingCandles(): int
    {
        return max(0, $this->totalCandles() - $this->used_candles);
    }

    public function isActive(): bool
    {
        return $this->status === MaintenancePackageStatus::Active;
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', MaintenancePackageStatus::Active);
    }

    /** بحث باسم العميل أو كوده */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        $query->when($term !== '', fn (Builder $query) => $query->whereHas('customer', fn (Builder $customer) => $customer
            ->where('name', 'like', "%{$term}%")
            ->orWhere('code', 'like', "%{$term}%")));
    }
}
