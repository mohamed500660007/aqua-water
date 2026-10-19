<?php

namespace App\Domain\Maintenance\Models;

use App\Domain\Products\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** شمعة مرحلة واحدة غُيّرت في العملية — اسم المنتج وسعره محفوظان كما كانا وقتها */
class MaintenanceOperationItem extends Model
{
    protected $fillable = ['position', 'product_id', 'product_name', 'unit_price'];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'unit_price' => 'decimal:2',
        ];
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(MaintenanceOperation::class, 'maintenance_operation_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
