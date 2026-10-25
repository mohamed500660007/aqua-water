<?php

namespace App\Http\Requests\Customers;

use App\Domain\Customers\Enums\CustomerOrigin;
use App\Domain\Customers\Enums\CustomerStatus;
use App\Domain\Reports\Data\ReportPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * التقرير يستقبل نفس فلاتر قائمة العملاء حتى يُطبع ما يراه المستخدم بالضبط، مع فترة للنشاط.
 */
class CustomerReportRequest extends FormRequest
{
    public const FILTERS = ['status', 'origin', 'governorate', 'center', 'village', 'balance'];

    public const SORTS = ['name' => 'الاسم', 'code' => 'الكود', 'registered_at' => 'تاريخ التركيب', 'status' => 'الحالة', 'remaining' => 'المتبقي'];

    /** أجزاء التقرير التي تُطبع: كله، أو الملخص والتوزيعات فقط، أو الكشف فقط */
    public const CONTENTS = ['all' => 'التقرير كاملًا', 'summary' => 'الملخص والتوزيعات فقط', 'list' => 'كشف العملاء فقط'];

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(CustomerStatus::class)],
            'origin' => ['nullable', Rule::enum(CustomerOrigin::class)],
            'governorate' => ['nullable', 'string', 'max:100'],
            'center' => ['nullable', 'string', 'max:100'],
            'village' => ['nullable', 'string', 'max:100'],
            'balance' => ['nullable', 'in:due,clear'],
            'content' => ['nullable', Rule::in(array_keys(self::CONTENTS))],
            'sort' => ['nullable', Rule::in(array_keys(self::SORTS))],
            'dir' => ['nullable', 'in:asc,desc'],
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

    /** @return array<string, string> */
    public function filters(): array
    {
        return array_filter($this->safe()->only(self::FILTERS));
    }

    /** الفلاتر + البحث + الترتيب — لتمريرها كما هي عند تغيير الفترة أو الرجوع للقائمة */
    public function listQuery(): array
    {
        return array_filter($this->safe()->only([...self::FILTERS, 'q', 'sort', 'dir']));
    }
}
