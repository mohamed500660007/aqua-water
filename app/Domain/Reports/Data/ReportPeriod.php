<?php

namespace App\Domain\Reports\Data;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

/**
 * فترة التقرير (من — إلى، شاملة الطرفين). الافتراضي هو الشهر الحالي.
 */
final readonly class ReportPeriod
{
    public function __construct(
        public CarbonImmutable $from,
        public CarbonImmutable $to,
    ) {}

    public static function fromStrings(?string $from, ?string $to): self
    {
        $start = $from ? CarbonImmutable::parse($from) : CarbonImmutable::today()->startOfMonth();
        $end = $to ? CarbonImmutable::parse($to) : $start->endOfMonth();

        return $end->lt($start)
            ? new self($end->startOfDay(), $start->startOfDay())
            : new self($start->startOfDay(), $end->startOfDay());
    }

    public function fromDate(): string
    {
        return $this->from->toDateString();
    }

    public function toDate(): string
    {
        return $this->to->toDateString();
    }

    /**
     * حدود whereBetween: الحد الأعلى نهاية اليوم لأن أعمدة التاريخ قد تُخزَّن مع وقت (00:00:00)
     * فيُستبعد اليوم الأخير لو قورن بتاريخ مجرد.
     *
     * @return array{0: string, 1: string}
     */
    public function bounds(): array
    {
        return [$this->fromDate(), $this->to->endOfDay()->toDateTimeString()];
    }

    /**
     * أول يوم من كل شهر تلمسه الفترة — المرتبات شهرية، فتُحسب لكل شهر داخل الفترة كاملًا.
     *
     * @return list<CarbonImmutable>
     */
    public function months(): array
    {
        $months = [];

        foreach (CarbonPeriod::create($this->from->startOfMonth(), '1 month', $this->to->startOfMonth()) as $month) {
            $months[] = CarbonImmutable::instance($month);
        }

        return $months;
    }

    public function label(): string
    {
        return $this->fromDate().' — '.$this->toDate();
    }
}
