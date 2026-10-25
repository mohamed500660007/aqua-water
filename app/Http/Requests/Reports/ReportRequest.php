<?php

namespace App\Http\Requests\Reports;

use App\Domain\Reports\Data\ReportPeriod;
use Illuminate\Foundation\Http\FormRequest;

/**
 * فلاتر تقارير الإدارة: الفترة (يوم/شهر/سنة أو أي مدى) وموظف اختياري (فني أو صاحب عهدة).
 */
class ReportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'user_id' => ['nullable', 'integer'],
        ];
    }

    public function period(): ReportPeriod
    {
        return ReportPeriod::fromStrings($this->validated('from'), $this->validated('to'));
    }

    public function userId(): ?int
    {
        return $this->validated('user_id') ? (int) $this->validated('user_id') : null;
    }
}
