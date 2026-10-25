<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Inventory\Enums\CustodySettlementStatus;
use App\Domain\Inventory\Enums\CustodySettlementType;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * تسوية عهدة: تصحيح مباشر لرصيد ما يحمله الموظف دون تحويل من أو إلى أي مخزن.
 */
class CustodySettlement extends Model
{
    protected $fillable = ['user_id', 'type', 'status', 'settlement_date', 'reason', 'note', 'posted_at'];

    protected function casts(): array
    {
        return [
            'type' => CustodySettlementType::class,
            'status' => CustodySettlementStatus::class,
            'settlement_date' => 'date',
            'posted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustodySettlementItem::class);
    }

    /** رقم التسوية المعروض (ST-0007) */
    public function number(): string
    {
        return 'ST-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function isDraft(): bool
    {
        return $this->status === CustodySettlementStatus::Draft;
    }

    /** @param  array<string, string|null>  $filters */
    public function scopeApplyFilters(Builder $query, array $filters): void
    {
        foreach (['user_id', 'type', 'status'] as $column) {
            $value = trim((string) ($filters[$column] ?? ''));

            $query->when($value !== '', fn (Builder $query) => $query->where($column, $value));
        }
    }
}
