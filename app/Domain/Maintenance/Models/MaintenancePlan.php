<?php

namespace App\Domain\Maintenance\Models;

use App\Domain\Maintenance\Enums\MaintenancePlanStatus;
use App\Support\ArabicOrdinal;
use Database\Factories\MaintenancePlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * خطة الصيانة الوحيدة في النظام: سبع شمعات بمواعيد ثابتة تنطبق على كل الفلاتر ولا تُعدَّل.
 * العقد المرتبط بها يأخذ نسخة من الشمعات بمواعيد = تاريخ تركيبه + دورية كل شمعة.
 */
class MaintenancePlan extends Model
{
    /** @use HasFactory<MaintenancePlanFactory> */
    use HasFactory, SoftDeletes;

    /** موضع الشمعة => دوريتها بالأشهر */
    public const CANDLE_INTERVALS = [1 => 1, 2 => 4, 3 => 6, 4 => 12, 5 => 12, 6 => 12, 7 => 12];

    protected static string $factory = MaintenancePlanFactory::class;

    protected $fillable = ['name', 'description', 'duration_months', 'status'];

    protected function casts(): array
    {
        return [
            'status' => MaintenancePlanStatus::class,
            'duration_months' => 'integer',
        ];
    }

    /** الخطة الوحيدة — تُنشئها الـ migration، فلا تكون null إلا في قاعدة بيانات لم تُرحَّل بعد */
    public static function current(): ?self
    {
        return static::query()->orderBy('id')->first();
    }

    /** «الشمعة الأولى» … «الشمعة السابعة» */
    public static function candleLabel(int $position): string
    {
        return 'الشمعة '.ArabicOrdinal::feminine($position);
    }

    /** «كل شهر» / «كل 4 شهور» / «كل سنة» */
    public static function intervalLabel(int $months): string
    {
        return match (true) {
            $months === 1 => 'كل شهر',
            $months === 12 => 'كل سنة',
            default => "كل {$months} شهور",
        };
    }
}
