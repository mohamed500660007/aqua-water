<?php

namespace App\Domain\Sales\Models;

use App\Domain\Products\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesContractItem extends Model
{
    protected $fillable = ['product_id', 'product_name', 'unit_price', 'quantity', 'total'];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
            'quantity' => 'integer',
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
