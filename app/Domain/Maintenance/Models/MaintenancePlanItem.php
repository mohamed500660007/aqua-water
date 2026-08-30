<?php

namespace App\Domain\Maintenance\Models;

use App\Domain\Products\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenancePlanItem extends Model
{
    protected $fillable = ['product_id', 'interval_months', 'replacement_date'];

    protected function casts(): array
    {
        return [
            'interval_months' => 'integer',
            'replacement_date' => 'date',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MaintenancePlan::class, 'maintenance_plan_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
