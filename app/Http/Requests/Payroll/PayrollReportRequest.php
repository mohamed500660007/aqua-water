<?php

namespace App\Http\Requests\Payroll;

use App\Domain\Payroll\Services\PayrollReport;
use App\Domain\Reports\Data\ReportPeriod;
use App\Support\Lookups;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * المرتبات شهرية: الفترة تُحسب بالشهور الكاملة التي تلمسها (من أول شهر «من» إلى آخر شهر «إلى»).
 */
class PayrollReportRequest extends FormRequest
{
    public const FILTERS = ['role', 'department', 'branch'];

    public const SORTS = ['name' => 'الاسم', 'department' => 'القسم', 'net' => 'صافي المستحق', 'remaining' => 'المتبقي للصرف'];

    public const APPROVALS = ['approved' => 'معتمد', 'pending' => 'غير معتمد (محسوب تلقائيًا)'];

    /** أجزاء التقرير التي تُطبع: كله، أو الملخص والتوزيعات فقط، أو الكشف فقط */
    public const CONTENTS = ['all' => 'التقرير كاملًا', 'summary' => 'الملخص والتوزيعات فقط', 'list' => 'كشف المرتبات فقط'];

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(Lookups::roles())],
            'department' => ['nullable', Rule::in(Lookups::departments())],
            'branch' => ['nullable', Rule::in(Lookups::branches())],
            'approval' => ['nullable', Rule::in(array_keys(self::APPROVALS))],
            'payment' => ['nullable', Rule::in(array_keys(PayrollReport::PAYMENTS))],
            'content' => ['nullable', Rule::in(array_keys(self::CONTENTS))],
            'sort' => ['nullable', Rule::in(array_keys(self::SORTS))],
            'dir' => ['nullable', 'in:asc,desc'],
        ];
    }

    /** الفترة ممدودة لشهور كاملة حتى يطابق ترويسة التقرير الشهور المحسوبة فعلًا */
    public function period(): ReportPeriod
    {
        $period = ReportPeriod::fromStrings($this->validated('from'), $this->validated('to'));

        return new ReportPeriod($period->from->startOfMonth(), $period->to->endOfMonth()->startOfDay());
    }

    public function content(): string
    {
        return $this->validated('content') ?? 'all';
    }

    /** @return array<string, string> */
    public function filters(): array
    {
        return array_filter($this->safe()->only([...self::FILTERS, 'approval', 'payment']));
    }

    /** للرجوع إلى صفحة المرتبات على أول شهر في الفترة */
    public function backQuery(): array
    {
        return array_filter(['month' => $this->period()->from->format('Y-m'), 'q' => $this->validated('q'), 'role' => $this->validated('role')]);
    }
}
