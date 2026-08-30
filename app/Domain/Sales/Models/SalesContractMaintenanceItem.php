<?php

namespace App\Domain\Sales\Models;

use App\Domain\Products\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * نسخة ثابتة من بند خطة الصيانة وقت التعاقد — لا تتأثر بتعديل الخطة لاحقًا.
 */
class SalesContractMaintenanceItem extends Model
{
    protected $fillable = ['product_id', 'product_name', 'interval_months', 'replacement_date'];

    protected function casts(): array
    {
        return [
            'interval_months' => 'integer',
            'replacement_date' => 'date',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(SalesContract::class, 'sales_contract_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
