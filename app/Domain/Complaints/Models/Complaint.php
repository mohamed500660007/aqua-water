<?php

namespace App\Domain\Complaints\Models;

use App\Domain\Sales\Models\SalesContract;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Complaint extends Model
{
    public const SORTABLE = ['complaint_date', 'visit_fee'];

    protected $fillable = [
        'sales_contract_id', 'technician_id', 'sales_rep_id',
        'complaint_date', 'visit_fee', 'resolved', 'note',
    ];

    protected function casts(): array
    {
        return [
            'complaint_date' => 'date',
            'visit_fee' => 'decimal:2',
            'resolved' => 'boolean',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(SalesContract::class, 'sales_contract_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function parts(): HasMany
    {
        return $this->hasMany(ComplaintPart::class);
    }

    /** إجمالي قطع الغيار (الكمية × السعر) — يتطلب تحميل parts مسبقًا */
    public function partsTotal(): float
    {
        return (float) $this->parts->sum(fn (ComplaintPart $part) => $part->quantity * (float) $part->price);
    }

    /** ما دفعه العميل: قطع الغيار + رسوم الزيارة */
    public function total(): float
    {
        return round($this->partsTotal() + (float) $this->visit_fee, 2);
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

        $resolved = trim((string) ($filters['resolved'] ?? ''));

        $query->when($resolved !== '', fn (Builder $query) => $query->where('resolved', $resolved === '1'));
    }

    public function scopeApplySort(Builder $query, ?string $column, ?string $direction): void
    {
        $column = in_array($column, self::SORTABLE, true) ? $column : 'complaint_date';
        $direction = strtolower((string) $direction) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($column, $direction)->orderBy('id', 'desc');
    }
}
