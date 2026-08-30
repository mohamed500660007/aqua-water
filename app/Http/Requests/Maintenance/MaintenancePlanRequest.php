<?php

namespace App\Http\Requests\Maintenance;

use App\Domain\Maintenance\Data\MaintenancePlanData;
use App\Domain\Maintenance\Enums\MaintenancePlanStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaintenancePlanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'status' => ['required', Rule::enum(MaintenancePlanStatus::class)],

            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.product_id' => [
                'required',
                'distinct',
                Rule::exists('products', 'id')->whereNull('deleted_at'),
            ],
            'items.*.interval_months' => ['required', 'integer', 'min:1', 'max:60'],
            'items.*.replacement_date' => ['required', 'date'],
        ];
    }

    public function toData(): MaintenancePlanData
    {
        return MaintenancePlanData::fromArray($this->validated());
    }

    /** تجاهل الصفوف الفارغة التي يتركها المستخدم في النموذج */
    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->filter(fn ($item) => is_array($item) && filled($item['product_id'] ?? null))
            ->values()
            ->all();

        $this->merge([
            'name' => trim((string) $this->input('name')) ?: null,
            'description' => trim((string) $this->input('description')) ?: null,
            'duration_months' => filled($this->input('duration_months')) ? $this->input('duration_months') : null,
            'items' => $items,
        ]);
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم الخطة',
            'description' => 'وصف الخطة',
            'duration_months' => 'مدة الخطة بالأشهر',
            'status' => 'الحالة',
            'items' => 'بنود الخطة',
            'items.*.product_id' => 'المنتج',
            'items.*.interval_months' => 'الدورية بالأشهر',
            'items.*.replacement_date' => 'تاريخ الاستبدال',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'يجب إضافة بند واحد على الأقل للخطة.',
            'items.min' => 'يجب إضافة بند واحد على الأقل للخطة.',
            'items.*.product_id.required' => 'اختر المنتج في كل بند.',
            'items.*.product_id.distinct' => 'لا يمكن تكرار نفس المنتج داخل الخطة.',
            'items.*.product_id.exists' => 'المنتج المختار غير موجود.',
            'items.*.interval_months.min' => 'الدورية يجب ألا تقل عن شهر واحد.',
            'items.*.interval_months.max' => 'الدورية يجب ألا تزيد عن 60 شهرًا.',
            'items.*.replacement_date.required' => 'حدّد تاريخ الاستبدال في كل بند.',
        ];
    }
}
