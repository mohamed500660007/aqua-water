<?php

namespace App\Domain\Complaints\Models;

use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Products\Models\Product;
use App\Domain\Sales\Enums\StockSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintPart extends Model
{
    protected $fillable = ['product_id', 'stock_source', 'warehouse_id', 'quantity', 'price'];

    protected function casts(): array
    {
        return [
            'stock_source' => StockSource::class,
            'quantity' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** الموظف الذي خُصمت القطعة من عهدته (فني الشكوى أو مندوبتها) — null لو المصدر مخزن */
    public function sourceEmployeeId(): ?int
    {
        return match ($this->stock_source) {
            StockSource::SalesRep => $this->complaint->sales_rep_id,
            StockSource::Installer => $this->complaint->technician_id,
            default => null,
        };
    }
}
