<?php

namespace App\Http\Requests\Inventory;

use App\Domain\Inventory\Data\CustodySettlementData;
use App\Domain\Inventory\Exceptions\SettlementMatchesCustodyException;
use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CustodySettlementRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->whereIn('role', User::CUSTODY_ROLES)->whereNull('deleted_at'),
            ],
            'settlement_date' => ['required', 'date', 'before_or_equal:today'],
            'reason' => ['required', 'string', 'max:150'],
            'note' => ['nullable', 'string', 'max:500'],
            'post' => ['boolean'],

            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'distinct', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'items.*.counted_quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    /**
     * لا معنى لتسوية كل كمياتها الفعلية مطابقة للعهدة — رسالة واضحة قبل الحفظ،
     * والخدمة تعيد حساب الفروق تحت القفل لحظة الترحيل.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $balances = app(EmployeeStockService::class)->availabilityMap((int) $this->input('user_id'));
                $differs = collect($this->input('items', []))
                    ->contains(fn (array $item) => (int) $item['counted_quantity'] !== ($balances[(int) $item['product_id']] ?? 0));

                if (! $differs) {
                    $validator->errors()->add('items', SettlementMatchesCustodyException::make()->getMessage());
                }
            },
        ];
    }

    public function toData(): CustodySettlementData
    {
        return CustodySettlementData::fromArray($this->validated());
    }

    public function shouldPost(): bool
    {
        return $this->boolean('post');
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->filter(fn ($item) => is_array($item) && filled($item['product_id'] ?? null))
            ->values()
            ->all();

        $this->merge([
            'items' => $items,
            'settlement_date' => $this->input('settlement_date') ?: now()->toDateString(),
            'reason' => trim((string) $this->input('reason')),
            'note' => trim((string) $this->input('note')) ?: null,
        ]);
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'الموظف',
            'settlement_date' => 'تاريخ التسوية',
            'reason' => 'سبب التسوية',
            'note' => 'الملاحظات',
            'items' => 'بنود التسوية',
            'items.*.product_id' => 'المنتج',
            'items.*.counted_quantity' => 'الكمية الفعلية',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.exists' => 'الموظف المختار غير موجود أو ليس مندوب مبيعات أو فني تركيب.',
            'settlement_date.before_or_equal' => 'تاريخ التسوية لا يمكن أن يكون في المستقبل.',
            'items.required' => 'يجب إضافة منتج واحد على الأقل للتسوية.',
            'items.min' => 'يجب إضافة منتج واحد على الأقل للتسوية.',
            'items.*.product_id.distinct' => 'لا يمكن تكرار نفس المنتج داخل التسوية.',
            'items.*.counted_quantity.min' => 'الكمية الفعلية لا تقل عن صفر.',
        ];
    }
}
