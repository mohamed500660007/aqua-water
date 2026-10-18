<?php

namespace App\Domain\Sales\Import;

use App\Domain\Sales\Data\SalesContractData;
use App\Domain\Sales\Data\SalesContractInput;
use App\Domain\Sales\Enums\ContractKind;
use App\Domain\Sales\Enums\ContractType;
use App\Domain\Sales\Enums\StockSource;
use App\Domain\Sales\Services\ContractPricing;
use App\Domain\Sales\Services\SalesContractService;
use App\Support\Spreadsheets\ImportResult;
use App\Support\Spreadsheets\SheetValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * استيراد العقود القديمة: كل الصفوف تُفحص بنفس قواعد نموذج العقد، ولو في أي خطأ لا يُسجَّل أي عقد.
 * العقود تُنشأ بنفس خدمة العقد (تسعير، أقساط، نسخة خطة الصيانة) لكن دون أي حركة مخزون.
 */
class ContractImporter
{
    private const DATE_COLUMNS = ['contract_date', 'first_due_date', 'installation_date', 'maintenance_date', 'collection_date'];

    private const NUMBER_COLUMNS = [
        'price', 'discount', 'paid', 'installments_count', 'installment_period_months',
        'quantity_1', 'quantity_2', 'quantity_3', 'unit_price_1', 'unit_price_2', 'unit_price_3',
    ];

    public function __construct(
        private readonly ContractSpreadsheet $spreadsheet,
        private readonly SalesContractService $contracts,
        private readonly ContractPricing $pricing,
    ) {}

    public function import(string $path): ImportResult
    {
        $references = new ContractReferences;
        $rows = $this->spreadsheet->rows($path, $references);
        $references->loadCustomers(array_column(array_column($rows, 'values'), 'customer'));

        $errors = [];
        $contracts = [];

        foreach ($rows as $row) {
            [$data, $rowErrors] = $this->parse($this->cleaned($row['values']), $references);

            if ($rowErrors !== []) {
                $errors[$row['row']] = $rowErrors;
            } else {
                $contracts[] = $data;
            }
        }

        if ($errors !== []) {
            return new ImportResult(created: 0, rows: count($rows), errors: $errors);
        }

        DB::transaction(function () use ($contracts) {
            foreach ($contracts as $data) {
                $this->contracts->create($data, trackStock: false);
            }
        });

        return new ImportResult(created: count($contracts), rows: count($rows));
    }

    /**
     * @param  array<string, string>  $values
     * @return array{0: SalesContractData|null, 1: list<string>}
     */
    private function parse(array $values, ContractReferences $references): array
    {
        $errors = [];
        // المرجع المكتوب الذي لم يُعثر عليه يأخذ 0 حتى لا تتكرر رسالة «مطلوب» مع سبب عدم العثور عليه
        $reference = function (string $value, callable $resolve) use (&$errors): ?int {
            if ($value === '') {
                return null;
            }

            $resolved = $resolve($value);

            if (is_string($resolved)) {
                $errors[] = $resolved;

                return 0;
            }

            return $resolved;
        };

        $kind = $this->enumValue($values['kind'], ContractKind::options(), ContractKind::Sale->value);
        $type = $this->enumValue($values['type'], ContractType::options(), '');
        $isInstallment = $type === ContractType::Installment->value;
        $installationDate = $values['installation_date'] ?: $values['contract_date'];

        $input = SalesContractInput::normalize([
            'kind' => $kind,
            'type' => $type,
            'customer_id' => $reference($values['customer'], $references->customer(...)),
            'contract_date' => $values['contract_date'],
            'installation_date' => $kind === ContractKind::Sale->value ? $installationDate : $values['installation_date'],
            'maintenance_date' => $values['maintenance_date'] ?: $installationDate,
            'collection_date' => $values['collection_date'],
            'warehouse_id' => $reference($values['warehouse'] ?: (string) $references->defaultWarehouseName(), $references->warehouse(...)),
            'stock_source' => StockSource::Warehouse->value,
            'sales_rep_id' => $reference($values['sales_rep'], $references->salesRep(...)),
            'installer_id' => $reference($values['installer'], $references->installer(...)),
            'maintenance_plan_id' => $reference($values['plan'], $references->plan(...)),
            'price' => $values['price'],
            'discount' => $values['discount'],
            'paid' => $values['paid'],
            'installments_count' => $values['installments_count'],
            'installment_period_months' => $values['installment_period_months'] ?: ($isInstallment ? 1 : null),
            'first_due_date' => $values['first_due_date'],
            'notes' => $values['notes'],
        ]);

        // البنود تُضاف بعد normalize لأنه يحذف سعر القطعة القادم من نموذج الواجهة
        if ($kind === ContractKind::Sale->value) {
            $input['items'] = $this->items($values, $references, $reference, $errors);
        }

        $validator = Validator::make(
            $input,
            SalesContractInput::rules($input, checkReferences: false) + [
                'items.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            ],
            $this->messages(),
            SalesContractInput::attributes() + ['items.*.unit_price' => 'سعر القطعة'],
        );

        $errors = [...$errors, ...$validator->errors()->all()];

        if ($errors !== []) {
            return [null, array_values(array_unique($errors))];
        }

        $data = SalesContractData::fromArray($validator->validated());
        $moneyError = SalesContractInput::moneyError($data, $this->pricing->forContract($data, $references->productsById)['subtotal']);

        return $moneyError ? [null, [$moneyError[1]]] : [$data, []];
    }

    /**
     * @param  array<string, string>  $values
     * @param  list<string>  $errors
     * @return list<array{product_id: int|null, quantity: string, unit_price: string|null}>
     */
    private function items(array $values, ContractReferences $references, callable $reference, array &$errors): array
    {
        $items = [];

        for ($slot = 1; $slot <= ContractSpreadsheet::PRODUCT_SLOTS; $slot++) {
            $product = $values["product_{$slot}"];

            if ($product === '') {
                if ($values["quantity_{$slot}"] !== '' || $values["unit_price_{$slot}"] !== '') {
                    $errors[] = "اكتب اسم المنتج {$slot} — الكمية أو السعر مكتوبان بدون منتج.";
                }

                continue;
            }

            $items[] = [
                'product_id' => $reference($product, $references->product(...)),
                'quantity' => $values["quantity_{$slot}"] ?: '1',
                'unit_price' => $values["unit_price_{$slot}"] !== '' ? $values["unit_price_{$slot}"] : null,
            ];
        }

        return $items;
    }

    /**
     * @param  array<string, string>  $values
     * @return array<string, string>
     */
    private function cleaned(array $values): array
    {
        foreach (self::DATE_COLUMNS as $key) {
            $values[$key] = SheetValue::date($values[$key]);
        }

        foreach (self::NUMBER_COLUMNS as $key) {
            $values[$key] = SheetValue::number($values[$key]);
        }

        return $values;
    }

    /**
     * النص الظاهر في القائمة (تقسيط) أو القيمة المخزّنة (installment)؛ غير المعروف يمر ليرفضه التحقق.
     *
     * @param  array<string, string>  $options
     */
    private function enumValue(string $value, array $options, string $default): string
    {
        if ($value === '') {
            return $default;
        }

        return array_search($value, $options, true) ?: $value;
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'customer_id.required' => 'اكتب كود العميل أو رقم هاتفه.',
            'type.required' => 'اختر طريقة الدفع: نقدي أو تقسيط.',
            'type.enum' => 'طريقة الدفع يجب أن تكون: نقدي أو تقسيط.',
            'kind.enum' => 'نوع التعاقد يجب أن يكون: بيع جهاز أو عقد صيانة.',
            'items.required' => 'عقد البيع يحتاج منتجًا واحدًا على الأقل.',
            'price.required' => 'اكتب سعر عقد الصيانة.',
            'contract_date.date' => 'تاريخ العقد غير مفهوم — اكتبه بصيغة 2025-03-15.',
            'installments_count.required' => 'اكتب عدد الأقساط المتبقية للعقد بالتقسيط.',
            'first_due_date.required' => 'اكتب تاريخ أول قسط متبقٍ للعقد بالتقسيط.',
        ] + SalesContractInput::messages();
    }
}
