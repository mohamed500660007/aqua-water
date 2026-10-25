<?php

namespace App\Domain\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * دفعة صرف من مرتب شهر — المرتب قد يُصرف على أكثر من دفعة حسب السيولة.
 */
class PayrollPayment extends Model
{
    protected $fillable = ['amount', 'paid_at', 'note'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
        ];
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }
}
