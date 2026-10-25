<?php

namespace App\Support\Spreadsheets;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

/**
 * شيت استيراد عربي: يبني قالبًا فارغًا (عناوين ملوّنة، ملاحظات، قوائم منسدلة، أعمدة نصية/تاريخ،
 * ورقة تعليمات) ويقرأ الملف المرفوع إلى صفوف نصية موحّدة مفهرسة بمفاتيح الأعمدة.
 */
class ImportSheet
{
    private const LISTS_SHEET = 'قوائم';

    /**
     * @param  list<SheetColumn>  $columns
     * @param  list<string>  $instructions
     */
    public function __construct(
        private readonly string $title,
        private readonly array $columns,
        private readonly int $maxRows,
        private readonly array $instructions = [],
    ) {}

    public function template(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()->setTitle("قالب استيراد {$this->title}")->setCreator('أكوا ووتر');

        $sheet = $spreadsheet->getActiveSheet()->setTitle($this->title)->setRightToLeft(true);
        $this->writeHeader($sheet);
        $this->formatDataRows($sheet);

        if ($this->instructions !== []) {
            $this->writeInstructions($spreadsheet->createSheet());
        }

        $this->addDropdowns($spreadsheet, $sheet);
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * صفوف الملف المرفوع (غير الفارغة) مع رقم الصف في الإكسيل لعرض الأخطاء بدقة.
     *
     * @return list<array{row: int, values: array<string, string>}>
     */
    public function rows(string $path): array
    {
        // قارئ Xlsx صريح بدل اكتشاف النوع تلقائيًا من ملف يرفعه المستخدم
        $reader = new XlsxReader;
        $reader->setReadDataOnly(true);

        try {
            $info = $reader->listWorksheetInfo($path)[0] ?? null;
        } catch (Throwable) {
            throw SpreadsheetImportException::unreadable();
        }

        if (! $info) {
            throw SpreadsheetImportException::unreadable();
        }

        if ($this->maxRows < $info['totalRows'] - 1) {
            throw SpreadsheetImportException::tooManyRows($this->maxRows);
        }

        $reader->setLoadSheetsOnly([$info['worksheetName']]);
        $cells = $reader->load($path)->getSheet(0)->toArray(null, false, false);

        $positions = $this->mapHeader(array_shift($cells) ?? []);
        $rows = [];

        foreach ($cells as $index => $cellRow) {
            $values = array_fill_keys(array_map(fn (SheetColumn $column) => $column->key, $this->columns), '');

            foreach ($positions as $position => $column) {
                $values[$column->key] = $this->cellText($cellRow[$position] ?? null, $column->format);
            }

            if (implode('', $values) !== '') {
                $rows[] = ['row' => $index + 2, 'values' => $values];
            }
        }

        if ($rows === []) {
            throw SpreadsheetImportException::empty();
        }

        return $rows;
    }

    /** @return array<int, SheetColumn> موضع العمود في الملف => تعريفه — ترتيب الأعمدة لا يهم */
    private function mapHeader(array $header): array
    {
        $byTitle = [];

        foreach ($this->columns as $column) {
            $byTitle[$column->title] = $column;
        }

        $positions = [];

        foreach ($header as $position => $title) {
            $title = trim(str_replace('*', '', (string) $title));

            if (isset($byTitle[$title])) {
                $positions[$position] = $byTitle[$title];
            }
        }

        $missing = array_filter(
            $this->columns,
            fn (SheetColumn $column) => $column->required && ! in_array($column, $positions, true),
        );

        if ($missing !== []) {
            throw SpreadsheetImportException::invalidTemplate(array_map(fn (SheetColumn $column) => $column->title, array_values($missing)));
        }

        return $positions;
    }

    private function cellText(mixed $value, ?string $format): string
    {
        if ($value === null) {
            return '';
        }

        if ($format === SheetColumn::DATE && is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        // رقم صحيح مخزّن كرقم (هاتف أو رقم قومي) يصل float — نمنع الصيغة العلمية
        if (is_float($value) && floor($value) === $value) {
            return sprintf('%.0f', $value);
        }

        return trim((string) $value);
    }

    private function writeHeader(Worksheet $sheet): void
    {
        foreach ($this->columns as $index => $column) {
            $letter = Coordinate::stringFromColumnIndex($index + 1);
            $cell = $sheet->getCell("{$letter}1");

            $cell->setValue($column->required ? "{$column->title} *" : $column->title);
            $sheet->getColumnDimension($letter)->setWidth($column->width);
            $cell->getStyle()->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => $column->required ? '1A1A1A' : 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $column->required ? 'D4FF5B' : '1A1A1A']],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ]);

            if ($column->note) {
                $sheet->getComment("{$letter}1")->setAuthor('أكوا ووتر')->getText()->createText($column->note);
            }
        }

        $sheet->getRowDimension(1)->setRowHeight(34);
        $sheet->freezePane('A2');
    }

    private function formatDataRows(Worksheet $sheet): void
    {
        $lastRow = $this->maxRows + 1;
        $lastLetter = Coordinate::stringFromColumnIndex(count($this->columns));

        // حدود متصلة لكل خلايا الجدول بدل خطوط الشبكة الباهتة المتقطعة
        $sheet->getStyle("A1:{$lastLetter}{$lastRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('BFC5CC');

        foreach ($this->columns as $index => $column) {
            $format = match ($column->format) {
                SheetColumn::TEXT => NumberFormat::FORMAT_TEXT,
                SheetColumn::DATE => 'yyyy-mm-dd',
                default => null,
            };

            if ($format) {
                $letter = Coordinate::stringFromColumnIndex($index + 1);
                $sheet->getStyle("{$letter}2:{$letter}{$lastRow}")->getNumberFormat()->setFormatCode($format);
            }
        }
    }

    /**
     * القوائم تُكتب في ورقة مخفية وتُربط بالخلايا كنطاق، فلا يقيدها حد الـ 255 حرفًا
     * للقوائم المكتوبة داخل قاعدة التحقق (قائمة المنتجات قد تطول).
     */
    private function addDropdowns(Spreadsheet $spreadsheet, Worksheet $sheet): void
    {
        $withOptions = array_filter($this->columns, fn (SheetColumn $column) => $column->options !== []);

        if ($withOptions === []) {
            return;
        }

        $lists = $spreadsheet->createSheet()->setTitle(self::LISTS_SHEET);
        $lists->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
        $listIndex = 0;

        foreach ($this->columns as $index => $column) {
            if ($column->options === []) {
                continue;
            }

            $listLetter = Coordinate::stringFromColumnIndex(++$listIndex);
            $lists->fromArray(array_map(fn (string $option) => [$option], $column->options), null, "{$listLetter}1");

            $validation = new DataValidation;
            $validation->setType(DataValidation::TYPE_LIST)
                ->setFormula1(sprintf("'%s'!\$%s\$1:\$%s\$%d", self::LISTS_SHEET, $listLetter, $listLetter, count($column->options)))
                ->setShowDropDown(true)
                ->setAllowBlank(true)
                ->setShowErrorMessage(true)
                ->setErrorStyle($column->strict ? DataValidation::STYLE_STOP : DataValidation::STYLE_INFORMATION)
                ->setErrorTitle('قيمة غير موجودة في القائمة')
                ->setError($column->strict ? 'اختر قيمة من القائمة المنسدلة.' : 'القيمة ليست في القائمة المقترحة — تأكد منها.');

            $letter = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setDataValidation("{$letter}2:{$letter}".($this->maxRows + 1), $validation);
        }
    }

    private function writeInstructions(Worksheet $sheet): void
    {
        $sheet->setTitle('تعليمات')->setRightToLeft(true);
        $sheet->getColumnDimension('A')->setWidth(110);

        foreach ($this->instructions as $index => $line) {
            $sheet->setCellValue('A'.($index + 1), $line);
            $sheet->getRowDimension($index + 1)->setRowHeight(24);
        }

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    }
}
