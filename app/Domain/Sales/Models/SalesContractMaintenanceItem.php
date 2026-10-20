<?php

namespace App\Domain\Sales\Models;

use App\Domain\Maintenance\Enums\MaintenanceType;
use App\Domain\Products\Models\Product;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * شمعة من خطة الصيانة على العقد. product_name هو اسمها في الخطة («الشمعة الأولى»)،
 * وreplacement_date موعدها (تاريخ التركيب + دوريتها) أو الموعد الذي حُدِّد لها.
 * تُغيَّر مرة واحدة: عندها تُحفظ تفاصيل التغيير هنا ويصبح product_id الشمعة المخصومة من عهدة الفني.
 */
class SalesContractMaintenanceItem extends Model
{
    protected $fillable = [
        'product_id', 'product_name', 'interval_months', 'replacement_date', 'technician_id',
        'serviced_at', 'service_type', 'price', 'discount', 'under_warranty', 'technician_commission',
    ];

    protected function casts(): array
    {
        return [
            'interval_months' => 'integer',
            'replacement_date' => 'date',
            'serviced_at' => 'date',
            'service_type' => MaintenanceType::class,
            'price' => 'decimal:2',
            'discount' => 'decimal:2',
            'under_warranty' => 'boolean',
            'technician_commission' => 'decimal:2',
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

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function isDone(): bool
    {
        return $this->serviced_at !== null;
    }

    /** المبلغ المستحق من العميل: صفر في الضمان، وإلا السعر بعد الخصم */
    public function total(): float
    {
        return $this->under_warranty ? 0.0 : max(0.0, (float) $this->price - (float) $this->discount);
    }

    public function scopePending(Builder $query): void
    {
        $query->whereNull('serviced_at');
    }

    public function scopeDone(Builder $query): void
    {
        $query->whereNotNull('serviced_at');
    }

    /** بحث باسم العميل أو كوده أو كود العقد */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->whereHas('contract', fn (Builder $contract) => $contract
            ->where('code', 'like', "%{$term}%")
            ->orWhereHas('customer', fn (Builder $customer) => $customer
                ->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")));
    }
}
