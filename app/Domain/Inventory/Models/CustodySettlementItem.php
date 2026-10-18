<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Products\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustodySettlementItem extends Model
{
    protected $fillable = ['product_id', 'quantity', 'balance_before', 'balance_after'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'balance_before' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(CustodySettlement::class, 'custody_settlement_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
