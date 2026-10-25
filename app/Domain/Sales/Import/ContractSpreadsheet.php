<?php

namespace App\Domain\Sales\Import;

use App\Domain\Sales\Enums\ContractKind;
use App\Domain\Sales\Enums\ContractType;
use App\Support\Spreadsheets\ImportSheet;
use App\Support\Spreadsheets\SheetColumn;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * أعمدة شيت استيراد العقود القديمة: عقد واحد في كل صف، وحتى ثلاثة منتجات للعقد.
 */
class ContractSpreadsheet
{
    public const MAX_ROWS = 500;

    public const PRODUCT_SLOTS = 3;

    public function template(ContractReferences $references): Spreadsheet
    {
        return $this->sheet($references)->template();
    }

    /** @return list<array{row: int, values: array<string, string>}> */
    public function rows(string $path, ContractReferences $references): array
    {
        return $this->sheet($references)->rows($path);
    }

    private function sheet(ContractReferences $references): ImportSheet
    {
        $defaultWarehouse = $references->defaultWarehouseName();

        // ترتيب الأعمدة يتبع جدول العقود الرئيسي: العميل ← تاريخ العقد ← نوع الجهاز ← القسط ← الفني
        return new ImportSheet('العقود', [
            new SheetColumn('customer', 'العميل (كود أو هاتف)', required: true, width: 20, format: SheetColumn::TEXT,
                note: 'كود العميل مثل CUS-00012 أو رقم هاتفه المسجل — العميل لازم يكون مسجلًا في النظام.'),
            new SheetColumn('contract_date', 'تاريخ العقد', required: true, format: SheetColumn::DATE, note: 'مثال: 2025-03-15'),
            new SheetColumn('kind', 'نوع التعاقد', width: 14, note: 'بيع جهاز أو عقد صيانة — الفارغ = بيع جهاز.',
                options: array_values(ContractKind::options())),
            ...$this->productColumns($references),
            new SheetColumn('price', 'سعر عقد الصيانة', width: 15, note: 'لعقود الصيانة فقط — عقد البيع يُحسب من المنتجات.'),
            new SheetColumn('type', 'طريقة الدفع', required: true, width: 13, options: array_values(ContractType::options())),
            new SheetColumn('discount', 'الخصم', width: 12),
            new SheetColumn('interest', 'الفائدة', width: 12, note: 'للتقسيط فقط — مبلغ الفائدة المضاف على المتبقي بعد المقدم.'),
            new SheetColumn('paid', 'المدفوع حتى الآن', width: 16,
                note: 'كل ما دفعه العميل حتى اليوم (المقدم + الأقساط التي حُصّلت).'),
            new SheetColumn('installments_count', 'عدد الأقساط المتبقية', width: 17,
                note: 'للتقسيط فقط — عدد الأقساط التي لم تُدفع بعد، ويُقسَّم عليها المبلغ المتبقي.'),
            new SheetColumn('installment_period_months', 'القسط كل كام شهر', width: 16, note: 'للتقسيط — الفارغ = كل شهر.'),
            new SheetColumn('first_due_date', 'تاريخ أول قسط متبقٍ', width: 18, format: SheetColumn::DATE,
                note: 'للتقسيط — تاريخ استحقاق أول قسط لم يُدفع.'),
            new SheetColumn('installer', 'فني التركيب', width: 20, options: $references->installerNames()),
            new SheetColumn('sales_rep', 'مندوب المبيعات', width: 20, options: $references->salesRepNames()),
            new SheetColumn('installation_date', 'تاريخ التركيب', format: SheetColumn::DATE, note: 'الفارغ = تاريخ العقد.'),
            new SheetColumn('maintenance_date', 'تاريخ الصيانة', format: SheetColumn::DATE, note: 'الفارغ = تاريخ التركيب.'),
            new SheetColumn('collection_date', 'تاريخ التحصيل', format: SheetColumn::DATE, note: 'اختياري.'),
            new SheetColumn('plan', 'خطة الصيانة', width: 20, options: $references->planNames()),
            new SheetColumn('warehouse', 'المخزن', options: $references->warehouseNames(),
                note: $defaultWarehouse ? "الفارغ = {$defaultWarehouse}. العقد القديم لا يخصم من المخزون." : null),
            new SheetColumn('notes', 'ملاحظات', width: 34),
        ], self::MAX_ROWS, [
            'طريقة ملء شيت العقود القديمة',
            '١. كل عقد في صف واحد داخل ورقة «العقود» بدءًا من الصف الثاني — لا تغيّر عناوين الأعمدة.',
            '٢. العميل لازم يكون مسجلًا في النظام: اكتب كوده (CUS-00012) أو رقم هاتفه. سجّل العملاء أولًا من شيت العملاء.',
            '٣. عقد البيع: اختر المنتج من القائمة واكتب الكمية (الفارغة = 1). سعر القطعة اختياري — الفارغ = سعر المنتج الحالي في النظام.',
            '٤. عقد الصيانة: لا منتجات له — اكتب «سعر عقد الصيانة».',
            '٥. «المدفوع حتى الآن» = كل ما دفعه العميل لليوم. في التقسيط اكتب عدد الأقساط المتبقية وتاريخ أول قسط متبقٍ، ويُقسَّم المتبقي عليها.',
            '٦. العقود القديمة لا تخصم أي كمية من المخزن أو عهدة الموظفين.',
            '٧. الحد الأقصى '.self::MAX_ROWS.' عقد في الملف الواحد.',
            '٨. لو في أي صف خطأ، لن يُستورد أي عقد وسيظهر لك رقم الصف وسبب الخطأ — صحّحه وارفع الملف مرة أخرى.',
        ]);
    }

    /** @return list<SheetColumn> */
    private function productColumns(ContractReferences $references): array
    {
        $columns = [];

        for ($slot = 1; $slot <= self::PRODUCT_SLOTS; $slot++) {
            $columns[] = new SheetColumn("product_{$slot}", "المنتج {$slot}", width: 24, options: $references->productNames(),
                note: $slot === 1 ? 'لعقد البيع منتج واحد على الأقل — اختر من القائمة.' : 'اختياري.');
            $columns[] = new SheetColumn("quantity_{$slot}", "الكمية {$slot}", width: 10, note: 'الفارغة = 1.');
            $columns[] = new SheetColumn("unit_price_{$slot}", "سعر القطعة {$slot}", width: 13,
                note: 'سعر البيع وقت العقد — الفارغ = سعر المنتج الحالي.');
        }

        return $columns;
    }
}
