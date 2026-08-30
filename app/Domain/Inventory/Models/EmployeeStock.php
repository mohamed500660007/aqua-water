<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Products\Models\Product;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ما يحمله موظف بعينه من منتجات أخذها من المخزن الرئيسي.
 */
class EmployeeStock extends Model
{
    protected $fillable = ['user_id', 'product_id', 'quantity'];

    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
