<?php

namespace App\Domain\Maintenance\Models;

use App\Domain\Sales\Models\SalesContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * موعد مرحلة واحدة من فلتر جهاز مركّب: آخر تغيير والموعد القادم.
 * يُحفظ محسوبًا (لا يُشتق وقت العرض) حتى تكون قائمة المستحقين استعلامًا مفهرسًا بسيطًا.
 */
class CandleSchedule extends Model
{
    protected $fillable = ['sales_contract_id', 'position', 'last_replaced_at', 'next_due_at'];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'last_replaced_at' => 'date',
            'next_due_at' => 'date',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(SalesContract::class, 'sales_contract_id');
    }

    public function isOverdue(): bool
    {
        return $this->next_due_at->lt(today());
    }
}
