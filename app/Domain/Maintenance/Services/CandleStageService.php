<?php

namespace App\Domain\Maintenance\Services;

use App\Domain\Maintenance\Models\CandleStage;
use Illuminate\Support\Facades\DB;

/**
 * حفظ إعداد المراحل السبع ثم إعادة حساب مواعيد كل الأجهزة على الإعداد الجديد.
 */
class CandleStageService
{
    public function __construct(private readonly CandleScheduleService $schedules) {}

    /** @param  array<int, array{product_id: int, interval_months: int}>  $stages  رقم المرحلة => الإعداد (المرحلة الغائبة تُلغى) */
    public function save(array $stages): void
    {
        DB::transaction(function () use ($stages) {
            CandleStage::query()->whereNotIn('position', array_keys($stages))->delete();

            foreach ($stages as $position => $stage) {
                CandleStage::updateOrCreate(['position' => $position], $stage);
            }

            $this->schedules->syncAll();
        });
    }
}
