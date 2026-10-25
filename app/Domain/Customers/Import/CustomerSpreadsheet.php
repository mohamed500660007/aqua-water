<?php

namespace App\Domain\Customers\Import;

use App\Domain\Customers\Enums\CustomerOrigin;
use App\Domain\Customers\Enums\CustomerStatus;
use App\Support\Lookups;
use App\Support\Spreadsheets\ImportSheet;
use App\Support\Spreadsheets\SheetColumn;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * أعمدة شيت استيراد العملاء — نفس التعريف يبني القالب ويقرأ الملف المرفوع.
 */
class CustomerSpreadsheet
{
    public const MAX_ROWS = 1000;

    public function template(): Spreadsheet
    {
        return $this->sheet()->template();
    }

    /** @return list<array{row: int, values: array<string, string>}> */
    public function rows(string $path): array
    {
        return $this->sheet()->rows($path);
    }

    private function sheet(): ImportSheet
    {
        return new ImportSheet('العملاء', [
            new SheetColumn('name', 'اسم العميل', required: true, width: 28, note: 'الاسم الكامل للعميل.'),
            new SheetColumn('phone_1', 'الهاتف الأساسي', required: true, width: 18, format: SheetColumn::TEXT,
                note: 'مثال: 01012345678 — يُكتب بالصفر في أوله.'),
            new SheetColumn('phone_2', 'هاتف إضافي', width: 18, format: SheetColumn::TEXT, note: 'اختياري.'),
            new SheetColumn('phone_3', 'هاتف إضافي 2', width: 18, format: SheetColumn::TEXT, note: 'اختياري.'),
            new SheetColumn('national_id', 'الرقم القومي', width: 20, format: SheetColumn::TEXT, note: 'اختياري — 14 رقمًا.'),
            // المحافظة قائمة مقترحة فقط — يُسمح بكتابة غيرها كما في نموذج العميل
            new SheetColumn('governorate', 'المحافظة', note: 'اختر من القائمة أو اكتب محافظة أخرى.',
                options: Lookups::governorates(), strict: false),
            new SheetColumn('center', 'المركز'),
            new SheetColumn('village', 'القرية'),
            new SheetColumn('detailed_address', 'العنوان التفصيلي', width: 34),
            new SheetColumn('status', 'الحالة', width: 15, note: 'اختر من القائمة — لو تُركت فارغة تُسجَّل «نشط».',
                options: array_values(CustomerStatus::options())),
            new SheetColumn('origin', 'نوع العميل', width: 14, note: 'داخلي: اشترى الجهاز منا. خارجي: صيانة فقط. الفارغ = داخلي.',
                options: array_values(CustomerOrigin::options())),
            new SheetColumn('registered_at', 'تاريخ التسجيل', format: SheetColumn::DATE,
                note: 'مثال: 2026-10-18 — لو تُرك فارغًا يُسجَّل تاريخ اليوم.'),
            new SheetColumn('notes', 'ملاحظات', width: 34),
        ], self::MAX_ROWS, [
            'طريقة ملء شيت العملاء',
            '١. اكتب كل عميل في صف واحد داخل ورقة «العملاء» بدءًا من الصف الثاني — لا تغيّر عناوين الأعمدة.',
            '٢. الأعمدة الخضراء المعلَّمة بـ * إجبارية: اسم العميل والهاتف الأساسي.',
            '٣. اكتب أرقام الهواتف بالصفر في أولها (مثال 01012345678)، والرقم القومي 14 رقمًا.',
            '٤. الحالة ونوع العميل والمحافظة تُختار من القائمة المنسدلة في الخلية.',
            '٥. تاريخ التسجيل بصيغة 2026-10-18، ولو تُرك فارغًا يُسجَّل تاريخ اليوم.',
            '٦. الحد الأقصى '.self::MAX_ROWS.' عميل في الملف الواحد.',
            '٧. لو في أي صف خطأ، لن يُستورد أي عميل وسيظهر لك رقم الصف وسبب الخطأ — صحّحه وارفع الملف مرة أخرى.',
        ]);
    }
}
