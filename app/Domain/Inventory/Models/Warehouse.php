<?php

namespace App\Domain\Inventory\Models;

use Database\Factories\WarehouseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    /** @use HasFactory<WarehouseFactory> */
    use HasFactory;

    protected static string $factory = WarehouseFactory::class;

    protected $fillable = ['code', 'name', 'governorate', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function balances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** خيارات القوائم المنسدلة: المعرّف => الاسم */
    public static function options(): array
    {
        return static::query()->active()->orderBy('name')->pluck('name', 'id')->all();
    }
}
