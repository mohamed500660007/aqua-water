<?php

namespace App\Http\Requests\Sales;

use App\Domain\Reports\Data\ReportPeriod;
use App\Domain\Sales\Enums\ContractKind;
use App\Domain\Sales\Enums\ContractType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * التقرير يستقبل نفس فلاتر قائمة العقود حتى يُطبع ما يراه المستخدم، والفترة هي فترة تاريخ التعاقد.
 */
class SalesContractReportRequest extends FormRequest
{
    public const FILTERS = ['kind', 'type', 'customer_id', 'sales_rep_id', 'installer_id', 'legal', 'payment'];

    public const SORTS = ['contract_date' => 'تاريخ العقد', 'code' => 'رقم العقد', 'total' => 'الإجمالي', 'remaining' => 'المتبقي'];

    /** أجزاء التقرير التي تُطبع: كله، أو الملخص والتوزيعات فقط، أو الكشف فقط */
    public const CONTENTS = ['all' => 'التقرير كاملًا', 'summary' => 'الملخص والتوزيعات فقط', 'list' => 'كشف العقود فقط'];

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:100'],
            'kind' => ['nullable', Rule::enum(ContractKind::class)],
            'type' => ['nullable', Rule::enum(ContractType::class)],
            'customer_id' => ['nullable', 'integer'],
            'sales_rep_id' => ['nullable', 'integer'],
            'installer_id' => ['nullable', 'integer'],
            'legal' => ['nullable', 'in:1'],
            'payment' => ['nullable', 'in:open,settled'],
            'content' => ['nullable', Rule::in(array_keys(self::CONTENTS))],
            'sort' => ['nullable', Rule::in([...array_keys(self::SORTS), 'created_at'])],
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

    /** فلاتر القائمة + حدود الفترة بالصيغة التي يفهمها SalesContract::scopeApplyFilters */
    public function filters(): array
    {
        $period = $this->period();

        return array_filter($this->safe()->only(self::FILTERS)) + [
            'from' => $period->fromDate(),
            'to' => $period->toDate(),
        ];
    }

    /** الفلاتر + البحث + الترتيب — لتمريرها كما هي عند تغيير الفترة أو الرجوع للقائمة */
    public function listQuery(): array
    {
        return array_filter($this->safe()->only([...self::FILTERS, 'q', 'sort', 'dir']));
    }
}
