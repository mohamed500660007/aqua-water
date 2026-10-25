<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Products\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * سطر جرد في التسوية: counted_quantity ما رجع به الموظف فعلًا، وbalance_before رصيد عهدته،
 * وquantity مقدار الفرق المطلق. في المسودة الأرصدة معاينة وقت الحفظ، وتُثبَّت لحظة الترحيل.
 */
class CustodySettlementItem extends Model
{
    protected $fillable = ['product_id', 'counted_quantity', 'quantity', 'balance_before', 'balance_after'];

    protected function casts(): array
    {
        return [
            'counted_quantity' => 'integer',
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

    /** الفرق بإشارته: سالب = عجز في العهدة، موجب = زيادة */
    public function difference(): int
    {
        return (int) $this->balance_after - (int) $this->balance_before;
    }

    /** «−2» / «+1» / «0» للعرض */
    public function signedDifference(): string
    {
        $difference = $this->difference();

        return ($difference > 0 ? '+' : ($difference < 0 ? '−' : '')).number_format(abs($difference));
    }
}
