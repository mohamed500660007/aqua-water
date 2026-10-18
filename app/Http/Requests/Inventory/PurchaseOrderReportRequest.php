<?php

namespace App\Http\Requests\Inventory;

use App\Domain\Reports\Data\ReportPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * التقرير يستقبل نفس فلاتر صفحة المخزن + المورد والمنتج، والفترة هي فترة تاريخ الطلبية.
 */
class PurchaseOrderReportRequest extends FormRequest
{
    public const FILTERS = ['warehouse_id', 'supplier', 'product_id'];

    public const SORTS = ['date' => 'تاريخ الطلبية', 'amount' => 'المبلغ', 'pieces' => 'عدد القطع', 'supplier' => 'المورد'];

    /** أجزاء التقرير التي تُطبع: كله، أو الملخص والتوزيعات فقط، أو الكشف فقط */
    public const CONTENTS = ['all' => 'التقرير كاملًا', 'summary' => 'الملخص والتوزيعات فقط', 'list' => 'كشف الطلبيات فقط'];

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:100'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'supplier' => ['nullable', 'string', 'max:150'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
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

    /** فلاتر الصفحة + حدود الفترة بالصيغة التي يفهمها PurchaseOrder::scopeApplyFilters */
    public function filters(): array
    {
        $period = $this->period();

        return array_filter($this->safe()->only(self::FILTERS)) + [
            'from' => $period->fromDate(),
            'to' => $period->toDate(),
        ];
    }

    /** الفلاتر + البحث — لرابط الرجوع إلى صفحة المخزن */
    public function listQuery(): array
    {
        return array_filter($this->safe()->only(['q', 'warehouse_id']));
    }
}
