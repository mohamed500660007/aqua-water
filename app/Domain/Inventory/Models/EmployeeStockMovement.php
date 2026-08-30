<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Products\Models\Product;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EmployeeStockMovement extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'type',
        'quantity',
        'balance_after',
        'note',
        'moved_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'quantity' => 'integer',
            'balance_after' => 'integer',
            'moved_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** المستند الذي تسبب في الحركة (عقد بيع مثلًا) */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
