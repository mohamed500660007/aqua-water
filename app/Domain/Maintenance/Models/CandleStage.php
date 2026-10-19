<?php

namespace App\Domain\Maintenance\Models;

use App\Domain\Products\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * مرحلة من مراحل الفلتر (1 → 7): أي شمعة تُركَّب فيها وكل كام شهر تتغير — إعداد ثابت لكل الأجهزة.
 */
class CandleStage extends Model
{
    public const POSITIONS = [1, 2, 3, 4, 5, 6, 7];

    protected $fillable = ['position', 'product_id', 'interval_months'];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'interval_months' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return Collection<int, self> المراحل المضبوطة مفهرسة برقم المرحلة */
    public static function configured(): Collection
    {
        return static::query()->with('product:id,name,price')->orderBy('position')->get()->keyBy('position');
    }
}
