<?php

namespace App\Domain\Sales\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesContractInstallment extends Model
{
    public const SORTABLE = ['due_date', 'amount'];

    protected $fillable = [
        'number', 'due_date', 'amount', 'paid_at', 'paid_amount',
        'collected_by_id', 'sales_rep_id', 'split_from_id', 'note',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'paid_at' => 'date',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(SalesContract::class, 'sales_contract_id');
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by_id');
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    /** القسط الأصلي الذي تفرّع منه هذا القسط عند دفع جزئي */
    public function splitFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'split_from_id');
    }

    /** الأقساط المتبقية المتفرّعة عن هذا القسط بسبب دفعات جزئية */
    public function splits(): HasMany
    {
        return $this->hasMany(self::class, 'split_from_id');
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    public function isOverdue(): bool
    {
        return ! $this->isPaid() && $this->due_date->isPast();
    }

    /** مستحق خلال الشهر الحالي (لكن لم يفت موعده بعد) */
    public function isDueThisMonth(): bool
    {
        return ! $this->isPaid()
            && ! $this->isOverdue()
            && $this->due_date->lte(today()->endOfMonth());
    }

    /** @return array{value: string, label: string, tone: string} */
    public function status(): array
    {
        return match (true) {
            $this->isPaid() => ['value' => 'paid', 'label' => 'مدفوع', 'tone' => 'green'],
            $this->isOverdue() => ['value' => 'overdue', 'label' => 'متأخر', 'tone' => 'red'],
            $this->isDueThisMonth() => ['value' => 'due_this_month', 'label' => 'مستحق هذا الشهر', 'tone' => 'amber'],
            default => ['value' => 'upcoming', 'label' => 'قادم', 'tone' => 'gray'],
        };
    }

    public function scopeUnpaid(Builder $query): void
    {
        $query->whereNull('paid_at');
    }

    public function scopeOverdue(Builder $query): void
    {
        $query->unpaid()->where('due_date', '<', today());
    }

    public function scopeDueThisMonth(Builder $query): void
    {
        $query->unpaid()
            ->where('due_date', '>=', today())
            ->where('due_date', '<=', today()->endOfMonth());
    }

    /** @param  array<string, string|null>  $filters */
    public function scopeApplyFilters(Builder $query, array $filters): void
    {
        $status = trim((string) ($filters['status'] ?? ''));

        match ($status) {
            'overdue' => $query->overdue(),
            'due_this_month' => $query->dueThisMonth(),
            'paid' => $query->whereNotNull('paid_at'),
            'upcoming' => $query->unpaid()->where('due_date', '>', today()->endOfMonth()),
            default => null,
        };

        $customerId = trim((string) ($filters['customer_id'] ?? ''));

        $query->when(
            $customerId !== '',
            fn (Builder $query) => $query->whereHas('contract', fn (Builder $q) => $q->where('customer_id', $customerId)),
        );

        $from = trim((string) ($filters['from'] ?? ''));
        $to = trim((string) ($filters['to'] ?? ''));

        $query->when($from !== '', fn (Builder $query) => $query->whereDate('due_date', '>=', $from));
        $query->when($to !== '', fn (Builder $query) => $query->whereDate('due_date', '<=', $to));
    }

    /** بحث بكود العقد أو اسم العميل أو كوده */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->whereHas('contract', function (Builder $contract) use ($term) {
            $contract->where('code', 'like', "%{$term}%")
                ->orWhereHas('customer', fn (Builder $customer) => $customer
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%"));
        });
    }

    public function scopeApplySort(Builder $query, ?string $column, ?string $direction): void
    {
        $column = in_array($column, self::SORTABLE, true) ? $column : 'due_date';
        $direction = strtolower((string) $direction) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($column, $direction)->orderBy('id');
    }
}
