<?php

namespace App\Http\Requests\Sales;

use App\Domain\Reports\Data\ReportPeriod;
use App\Domain\Sales\Services\InstallmentReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * فلاتر تقرير الأقساط: الفترة (يوم/شهر/سنة أو أي مدى)، ومنطقة العميل، والمحصّل، ومستوى تجميع المناطق.
 */
class InstallmentReportRequest extends FormRequest
{
    public const FILTERS = ['governorate', 'center', 'village', 'collected_by_id'];

    /** أجزاء التقرير التي تُطبع */
    public const CONTENTS = [
        'all' => 'التقرير كاملًا',
        'collections' => 'التحصيل والمناطق فقط',
        'overdue' => 'المتأخرات فقط',
        'milestones' => 'أول قسط وإنهاء الأقساط فقط',
    ];

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'governorate' => ['nullable', 'string', 'max:100'],
            'center' => ['nullable', 'string', 'max:100'],
            'village' => ['nullable', 'string', 'max:100'],
            'collected_by_id' => ['nullable', 'integer'],
            'area_level' => ['nullable', Rule::in(array_keys(InstallmentReport::AREA_LEVELS))],
            'content' => ['nullable', Rule::in(array_keys(self::CONTENTS))],
        ];
    }

    public function period(): ReportPeriod
    {
        return ReportPeriod::fromStrings($this->validated('from'), $this->validated('to'));
    }

    public function content(): string
    {
        return $this->validated('content') ?? 'all';
    }

    public function areaLevel(): string
    {
        return $this->validated('area_level') ?? 'center';
    }

    /** @return array<string, string> */
    public function filters(): array
    {
        return array_filter($this->safe()->only(self::FILTERS));
    }

    /** هل يُطبع هذا الجزء ضمن المحتوى المختار؟ الأجزاء غير المطلوبة لا تُحسب أصلًا */
    public function includes(string $part): bool
    {
        return in_array($this->content(), ['all', $part], true);
    }
}
