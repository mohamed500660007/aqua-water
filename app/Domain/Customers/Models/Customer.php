<?php

namespace App\Domain\Customers\Models;

use App\Domain\Customers\Enums\CustomerStatus;
use App\Support\Digits;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, SoftDeletes;

    public const SORTABLE = ['name', 'code', 'status', 'registered_at'];

    protected static string $factory = CustomerFactory::class;

    protected $fillable = [
        'code',
        'name',
        'national_id',
        'governorate',
        'center',
        'village',
        'detailed_address',
        'status',
        'notes',
        'registered_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CustomerStatus::class,
            'registered_at' => 'date',
        ];
    }

    public function phones(): HasMany
    {
        return $this->hasMany(CustomerPhone::class)->orderByDesc('is_primary')->orderBy('id');
    }

    public function primaryPhone(): HasOne
    {
        return $this->hasOne(CustomerPhone::class)->where('is_primary', true);
    }

    /** بحث بالكود أو الاسم أو الرقم القومي أو أي رقم هاتف للعميل */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $digits = Digits::normalize($term);

        $query->where(function (Builder $query) use ($term, $digits) {
            $query->where('code', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%")
                ->orWhere('national_id', 'like', "%{$term}%");

            if ($digits !== '') {
                $query->orWhereHas('phones', fn (Builder $phones) => $phones->where('phone', 'like', "%{$digits}%"));
            }
        });
    }

    /** @param  array<string, string|null>  $filters */
    public function scopeApplyFilters(Builder $query, array $filters): void
    {
        foreach (['status', 'governorate', 'center', 'village'] as $column) {
            $value = trim((string) ($filters[$column] ?? ''));

            $query->when($value !== '', fn (Builder $query) => $query->where($column, $value));
        }
    }

    public function scopeApplySort(Builder $query, ?string $column, ?string $direction): void
    {
        $column = in_array($column, self::SORTABLE, true) ? $column : 'registered_at';
        $direction = strtolower((string) $direction) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($column, $direction)->orderBy('id', 'desc');
    }

    /** القيم المستخدمة فعليًا في عمود موقع — لبناء قوائم الفلترة */
    public static function usedValuesOf(string $column): array
    {
        if (! in_array($column, ['governorate', 'center', 'village'], true)) {
            return [];
        }

        return static::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();
    }
}
