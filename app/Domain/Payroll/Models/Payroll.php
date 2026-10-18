<?php

namespace App\Domain\Payroll\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    protected $fillable = [
        'user_id', 'month', 'base_salary', 'installment_count', 'installment_total',
        'installment_commission', 'device_count', 'device_commission',
        'installed_device_count', 'installation_commission',
        'marketed_device_count', 'marketing_commission',
        'maintenance_count', 'maintenance_commission', 'adjustment', 'total', 'note',
        'regular_deduction', 'deduction_reason', 'attendance_deduction', 'bonus',
        'absence_days', 'device_sales_total',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'date',
            'installment_count' => 'integer',
            'device_count' => 'integer',
            'installed_device_count' => 'integer',
            'marketed_device_count' => 'integer',
            'maintenance_count' => 'integer',
            'absence_days' => 'integer',
            'device_sales_total' => 'decimal:2',
            'base_salary' => 'decimal:2',
            'installment_total' => 'decimal:2',
            'installment_commission' => 'decimal:2',
            'device_commission' => 'decimal:2',
            'installation_commission' => 'decimal:2',
            'marketing_commission' => 'decimal:2',
            'maintenance_commission' => 'decimal:2',
            'adjustment' => 'decimal:2',
            'regular_deduction' => 'decimal:2',
            'attendance_deduction' => 'decimal:2',
            'bonus' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PayrollPayment::class)->orderBy('paid_at')->orderBy('id');
    }

    public function paidAmount(): float
    {
        return round((float) ($this->relationLoaded('payments')
            ? $this->payments->sum('amount')
            : $this->payments()->sum('amount')), 2);
    }

    public function remainingAmount(): float
    {
        return max(0, round((float) $this->total - $this->paidAmount(), 2));
    }

    /** @return array{label: string, tone: string} */
    public function paymentStatus(): array
    {
        $paid = $this->paidAmount();

        return match (true) {
            $paid <= 0 => ['label' => 'لم يُصرف', 'tone' => 'red'],
            $this->remainingAmount() > 0 => ['label' => 'صُرف جزئيًا', 'tone' => 'amber'],
            default => ['label' => 'تم الصرف', 'tone' => 'green'],
        };
    }
}
