<?php

namespace App\Domain\Maintenance\Models;

use App\Domain\Maintenance\Enums\MaintenancePlanStatus;
use Database\Factories\MaintenancePlanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenancePlan extends Model
{
    /** @use HasFactory<MaintenancePlanFactory> */
    use HasFactory, SoftDeletes;

    public const SORTABLE = ['name', 'status', 'created_at'];

    protected static string $factory = MaintenancePlanFactory::class;

    protected $fillable = ['name', 'description', 'duration_months', 'status'];

    protected function casts(): array
    {
        return [
            'status' => MaintenancePlanStatus::class,
            'duration_months' => 'integer',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(MaintenancePlanItem::class)->orderBy('replacement_date')->orderBy('id');
    }

    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term) {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    /** @param  array<string, string|null>  $filters */
    public function scopeApplyFilters(Builder $query, array $filters): void
    {
        $status = trim((string) ($filters['status'] ?? ''));

        $query->when($status !== '', fn (Builder $query) => $query->where('status', $status));
    }

    public function scopeApplySort(Builder $query, ?string $column, ?string $direction): void
    {
        $column = in_array($column, self::SORTABLE, true) ? $column : 'created_at';
        $direction = strtolower((string) $direction) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($column, $direction)->orderBy('id', 'desc');
    }

    /** خيارات القوائم المنسدلة: المعرّف => الاسم (الخطط المفعّلة فقط) */
    public static function options(): array
    {
        return static::query()
            ->where('status', MaintenancePlanStatus::Active)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
