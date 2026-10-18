<?php

namespace App\Domain\Sales\Data;

use App\Domain\Sales\Enums\ContractKind;
use App\Domain\Sales\Enums\ContractType;
use App\Domain\Sales\Enums\StockSource;
use App\Domain\Users\Models\User;
use Illuminate\Validation\Rule;

/**
 * توحيد مدخلات العقد وقواعد التحقق منها — مشتركة بين نموذج العقد واستيراد العقود القديمة من الإكسيل.
 */
final class SalesContractInput
{
    /**
     * @param  array<string, mixed>  $input  المدخلات بعد normalize
     * @param  bool  $checkReferences  الاستيراد يحلّ العميل والمنتجات والموظفين دفعة واحدة بدل استعلام لكل صف
     * @return array<string, mixed>
     */
    public static function rules(array $input, bool $checkReferences = true): array
    {
        $isInstallment = ($input['type'] ?? null) === ContractType::Installment->value;
        $isSale = ! self::isMaintenance($input);
        $exists = fn (string $table, ?callable $scope = null) => $checkReferences
            ? [tap(Rule::exists($table, 'id'), fn ($rule) => $scope ? $scope($rule) : null)]
            : ['integer'];

        return [
            'kind' => ['required', Rule::enum(ContractKind::class)],
            'customer_id' => ['required', ...$exists('customers', fn ($rule) => $rule->whereNull('deleted_at'))],
            'sales_rep_id' => ['nullable', ...$exists('users', fn ($rule) => $rule
                ->whereIn('role', User::CONTRACT_SALES_ROLES)->whereNull('deleted_at'))],
            'installer_id' => ['nullable', ...$exists('users', fn ($rule) => $rule
                ->where('role', User::INSTALLER_ROLE)->whereNull('deleted_at'))],
            'warehouse_id' => ['required', ...$exists('warehouses', fn ($rule) => $rule->where('is_active', true))],
            'stock_source' => ['required', Rule::enum(StockSource::class)],
            'maintenance_plan_id' => ['nullable', ...$exists('maintenance_plans', fn ($rule) => $rule->whereNull('deleted_at'))],
            'contract_date' => ['required', 'date'],
            'installation_date' => [$isSale ? 'required' : 'nullable', 'date', 'after_or_equal:contract_date'],
            'collection_date' => ['nullable', 'date', 'after_or_equal:contract_date'],
            'maintenance_date' => ['required', 'date', 'after_or_equal:contract_date'],
            'type' => ['required', Rule::enum(ContractType::class)],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => $isSale ? ['required', 'array', 'min:1', 'max:50'] : ['array'],
            'price' => $isSale ? ['nullable'] : ['required', 'numeric', 'min:0.01', 'max:9999999'],
            'items.*.product_id' => ['required', 'distinct', ...$exists('products', fn ($rule) => $rule->whereNull('deleted_at'))],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],

            'discount' => ['nullable', 'numeric', 'min:0'],
            'paid' => ['nullable', 'numeric', 'min:0'],

            'installments_count' => [Rule::requiredIf($isInstallment), 'nullable', 'integer', 'min:1', 'max:120'],
            'installment_period_months' => [Rule::requiredIf($isInstallment), 'nullable', 'integer', 'min:1', 'max:12'],
            'first_due_date' => [Rule::requiredIf($isInstallment), 'nullable', 'date', 'after_or_equal:contract_date'],
        ];
    }

    /**
     * القيم الافتراضية وتنظيف المدخلات قبل التحقق.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalize(array $input): array
    {
        $isMaintenance = self::isMaintenance($input);

        // سعر القطعة لا يُقبل من نموذج الواجهة أبدًا — السعر يُقرأ من المنتج وقت الحفظ
        $items = collect($input['items'] ?? [])
            ->filter(fn ($item) => is_array($item) && filled($item['product_id'] ?? null))
            ->map(fn (array $item) => ['product_id' => $item['product_id'], 'quantity' => $item['quantity'] ?? null])
            ->values()
            ->all();

        return array_merge($input, [
            'kind' => ($input['kind'] ?? null) ?: ContractKind::Sale->value,
            // عقد الصيانة لا يصرف بضاعة: تُهمل أي بنود متبقية في النموذج المخفي
            'items' => $isMaintenance ? [] : $items,
            'discount' => ($input['discount'] ?? null) ?: 0,
            'paid' => ($input['paid'] ?? null) ?: 0,
            'notes' => trim((string) ($input['notes'] ?? '')) ?: null,
            'collection_date' => ($input['collection_date'] ?? null) ?: null,
            'maintenance_plan_id' => ($input['maintenance_plan_id'] ?? null) ?: null,
            'sales_rep_id' => ($input['sales_rep_id'] ?? null) ?: null,
            'installer_id' => ($input['installer_id'] ?? null) ?: null,
            'stock_source' => $isMaintenance
                ? StockSource::Warehouse->value
                : (($input['stock_source'] ?? null) ?: StockSource::Warehouse->value),
            'installation_date' => ($input['installation_date'] ?? null) ?: null,
        ]);
    }

    /**
     * الخصم لا يتجاوز الإجمالي، والمدفوع لا يتجاوز المستحق، والتقسيط يحتاج مبلغًا متبقيًا.
     *
     * @return array{0: string, 1: string}|null [الحقل، الرسالة] أو null لو سليم
     */
    public static function moneyError(SalesContractData $data, float $subtotal): ?array
    {
        if ($data->discount > $subtotal) {
            return ['discount', 'الخصم لا يمكن أن يتجاوز سعر العقد.'];
        }

        $total = round($subtotal - $data->discount, 2);

        if ($data->paid > $total) {
            return ['paid', 'المبلغ المدفوع لا يمكن أن يتجاوز إجمالي العقد.'];
        }

        if ($data->type === ContractType::Installment && round($total - $data->paid, 2) <= 0) {
            return ['paid', 'لا يمكن إنشاء عقد تقسيط بدون مبلغ متبقٍّ للسداد.'];
        }

        return null;
    }

    /** @return array<string, string> */
    public static function attributes(): array
    {
        return [
            'kind' => 'نوع التعاقد',
            'price' => 'سعر عقد الصيانة',
            'customer_id' => 'العميل',
            'sales_rep_id' => 'مندوب المبيعات',
            'installer_id' => 'فني التركيب',
            'warehouse_id' => 'المخزن',
            'stock_source' => 'مصدر البضاعة',
            'maintenance_plan_id' => 'خطة الصيانة',
            'contract_date' => 'تاريخ العقد',
            'installation_date' => 'تاريخ التركيب',
            'collection_date' => 'تاريخ التحصيل',
            'maintenance_date' => 'تاريخ الصيانة',
            'type' => 'نوع العقد',
            'notes' => 'الملاحظات',
            'items' => 'بنود العقد',
            'items.*.product_id' => 'المنتج',
            'items.*.quantity' => 'الكمية',
            'discount' => 'الخصم',
            'paid' => 'المبلغ المدفوع',
            'installments_count' => 'عدد الأقساط',
            'installment_period_months' => 'دورية السداد',
            'first_due_date' => 'تاريخ أول استحقاق',
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'customer_id.required' => 'يجب اختيار العميل.',
            'customer_id.exists' => 'العميل المختار غير موجود.',
            'sales_rep_id.exists' => 'المندوب المختار غير موجود أو لا يحمل دور مندوب مبيعات.',
            'installer_id.exists' => 'الفني المختار غير موجود أو لا يحمل دور فني تركيب.',
            'items.required' => 'يجب إضافة بند واحد على الأقل للعقد.',
            'items.min' => 'يجب إضافة بند واحد على الأقل للعقد.',
            'items.*.product_id.distinct' => 'لا يمكن تكرار نفس المنتج داخل العقد.',
            'items.*.quantity.min' => 'الكمية يجب ألا تقل عن 1.',
            'installation_date.required' => 'حدّد تاريخ التركيب.',
            'installation_date.after_or_equal' => 'تاريخ التركيب لا يمكن أن يسبق تاريخ العقد.',
            'collection_date.after_or_equal' => 'تاريخ التحصيل لا يمكن أن يسبق تاريخ العقد.',
            'maintenance_date.required' => 'حدّد تاريخ الصيانة.',
            'maintenance_date.after_or_equal' => 'تاريخ الصيانة لا يمكن أن يسبق تاريخ العقد.',
            'installments_count.required' => 'حدّد عدد الأقساط للعقد بالتقسيط.',
            'installment_period_months.required' => 'حدّد دورية السداد بالأشهر.',
            'first_due_date.required' => 'حدّد تاريخ أول استحقاق.',
            'first_due_date.after_or_equal' => 'تاريخ أول استحقاق يجب ألا يسبق تاريخ العقد.',
        ];
    }

    /** @param  array<string, mixed>  $input */
    private static function isMaintenance(array $input): bool
    {
        return ($input['kind'] ?? null) === ContractKind::Maintenance->value;
    }
}
