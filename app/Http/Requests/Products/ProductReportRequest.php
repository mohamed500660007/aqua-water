<?php

namespace App\Http\Requests\Products;

use App\Domain\Products\Enums\ProductKind;
use App\Domain\Products\Enums\ProductStatus;
use App\Domain\Reports\Data\ReportPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * التقرير يستقبل نفس فلاتر قائمة المنتجات + المخزن ووجود الرصيد، والفترة تخص الحركات والمبيعات.
 */
class ProductReportRequest extends FormRequest
{
    public const FILTERS = ['category', 'kind', 'status', 'warehouse_id', 'stock'];

    public const SORTS = ['name' => 'اسم المنتج', 'sku' => 'الكود', 'price' => 'السعر', 'stock' => 'الرصيد', 'sold' => 'المباع في الفترة'];

    /** أجزاء التقرير التي تُطبع: كله، أو الملخص والتوزيعات فقط، أو الكشف فقط */
    public const CONTENTS = ['all' => 'التقرير كاملًا', 'summary' => 'الملخص والتوزيعات فقط', 'list' => 'كشف المنتجات فقط'];

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'kind' => ['nullable', Rule::enum(ProductKind::class)],
            'status' => ['nullable', Rule::enum(ProductStatus::class)],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'stock' => ['nullable', 'in:in,out'],
            'content' => ['nullable', Rule::in(array_keys(self::CONTENTS))],
            'sort' => ['nullable', Rule::in([...array_keys(self::SORTS), 'category', 'status'])],
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

    public function warehouseId(): ?int
    {
        return $this->validated('warehouse_id') ? (int) $this->validated('warehouse_id') : null;
    }

    /** @return array<string, string> */
    public function filters(): array
    {
        return array_filter($this->safe()->only(self::FILTERS));
    }

    /** الفلاتر + البحث + الترتيب — لرابط الرجوع إلى قائمة المنتجات */
    public function listQuery(): array
    {
        return array_filter($this->safe()->only(['category', 'kind', 'status', 'q', 'sort', 'dir']));
    }
}
