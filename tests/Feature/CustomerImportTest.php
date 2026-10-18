<?php

namespace Tests\Feature;

use App\Domain\Customers\Enums\CustomerOrigin;
use App\Domain\Customers\Enums\CustomerStatus;
use App\Domain\Customers\Import\CustomerSpreadsheet;
use App\Domain\Customers\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CustomerImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * يملأ القالب الحقيقي كما تملؤه الموظفة — الأعمدة بترتيب القالب:
     * الاسم، الهاتف، هاتف 2، هاتف 3، الرقم القومي، المحافظة، المركز، القرية، العنوان، الحالة، النوع، التاريخ، ملاحظات.
     *
     * @param  list<list<mixed>>  $rows
     */
    private function sheetFile(array $rows, ?Spreadsheet $spreadsheet = null): UploadedFile
    {
        $spreadsheet ??= app(CustomerSpreadsheet::class)->template();
        $sheet = $spreadsheet->getSheet(0);

        foreach ($rows as $rowIndex => $values) {
            foreach ($values as $columnIndex => $value) {
                if ($value !== null) {
                    $sheet->setCellValue([$columnIndex + 1, $rowIndex + 2], $value);
                }
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'customers').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'customers.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function upload(UploadedFile $file)
    {
        return $this->from(route('customers.import.create'))
            ->post(route('customers.import.store'), ['file' => $file]);
    }

    public function test_the_template_downloads_with_the_expected_columns_and_dropdowns(): void
    {
        $response = $this->get(route('customers.import.template'))->assertOk();
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('Content-Type'));

        $path = tempnam(sys_get_temp_dir(), 'tpl').'.xlsx';
        file_put_contents($path, $response->streamedContent());
        $sheet = IOFactory::load($path)->getSheet(0);

        $this->assertSame('اسم العميل *', $sheet->getCell('A1')->getValue());
        $this->assertSame('الهاتف الأساسي *', $sheet->getCell('B1')->getValue());
        $this->assertTrue($sheet->getRightToLeft());
        $this->assertStringContainsString('قوائم', $sheet->getCell('J2')->getDataValidation()->getFormula1());
        $this->assertSame('@', $sheet->getStyle('B2')->getNumberFormat()->getFormatCode());
    }

    public function test_it_imports_customers_with_phones_codes_and_mapped_values(): void
    {
        $this->upload($this->sheetFile([
            ['أحمد علي', '01012345678', '01112345678', null, '29501011234567', 'الدقهلية', 'المنصورة', 'ميت مزاح', 'شارع النيل', 'مرتجع', 'خارجي', '2026-01-15', 'عميل قديم'],
            // الإكسيل حوّل الهاتف والرقم القومي إلى أرقام، والتاريخ إلى رقم مسلسل
            ['منى حسن', 1098765432, null, null, 29802021234567, null, null, null, null, null, null, ExcelDate::PHPToExcel('2025-12-01'), null],
        ]))->assertRedirect(route('customers.index'))->assertSessionHas('success');

        $ahmed = Customer::where('name', 'أحمد علي')->with('phones')->firstOrFail();
        $this->assertSame('CUS-00001', $ahmed->code);
        $this->assertSame(CustomerStatus::Returned, $ahmed->status);
        $this->assertSame(CustomerOrigin::External, $ahmed->origin);
        $this->assertSame('2026-01-15', $ahmed->registered_at->toDateString());
        $this->assertSame(['01012345678', '01112345678'], $ahmed->phones->pluck('phone')->all());
        $this->assertTrue($ahmed->phones->first()->is_primary);
        $this->assertFalse($ahmed->phones->last()->is_primary);

        $mona = Customer::where('name', 'منى حسن')->with('phones')->firstOrFail();
        $this->assertSame('CUS-00002', $mona->code);
        $this->assertSame('29802021234567', $mona->national_id);
        $this->assertSame('01098765432', $mona->phones->first()->phone);
        $this->assertSame(CustomerStatus::Active, $mona->status);
        $this->assertSame(CustomerOrigin::Internal, $mona->origin);
        $this->assertSame('2025-12-01', $mona->registered_at->toDateString());
    }

    public function test_one_invalid_row_imports_nothing_and_reports_errors_by_row(): void
    {
        $this->upload($this->sheetFile([
            ['عميل سليم', '01012345678'],
            [null, '123'],
            ['عميل ثالث', '01012345679', null, null, '123', null, null, null, null, 'محذوف'],
        ]))->assertRedirect(route('customers.import.create'))->assertSessionHas('importRows', 3);

        $errors = session('importErrors');
        $this->assertSame([3, 4], array_keys($errors));
        $this->assertContains('رقم الهاتف غير صحيح — اكتبه بالصيغة المحلية مثل 01012345678.', $errors[3]);
        $this->assertContains('الرقم القومي يجب أن يتكون من 14 رقمًا.', $errors[4]);
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_it_rejects_national_ids_duplicated_in_the_file_or_already_registered(): void
    {
        Customer::factory()->create(['national_id' => '29501011234567']);

        $this->upload($this->sheetFile([
            ['مكرر مع النظام', '01012345678', null, null, '29501011234567'],
            ['الأول', '01012345679', null, null, '29802021234567'],
            ['مكرر في الملف', '01012345670', null, null, '29802021234567'],
        ]));

        $errors = session('importErrors');
        $this->assertSame([2, 4], array_keys($errors));
        $this->assertStringContainsString('الصف 3', $errors[4][0]);
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_it_rejects_a_file_that_does_not_match_the_template(): void
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([['الاسم', 'التليفون'], ['أحمد', '01012345678']]);

        $this->upload($this->sheetFile([], $spreadsheet))->assertSessionHasErrors('file');

        $this->assertDatabaseCount('customers', 0);
    }

    public function test_it_rejects_an_empty_template_and_non_excel_files(): void
    {
        $this->upload($this->sheetFile([]))->assertSessionHasErrors('file');
        $this->upload(UploadedFile::fake()->create('customers.csv', 5, 'text/csv'))->assertSessionHasErrors('file');

        $this->assertDatabaseCount('customers', 0);
    }

    /** انحدار: التاريخ بصيغة يوم/شهر/سنة كان يُسقط الطلب كله (Carbon يرمي استثناء عند عدم تطابق الصيغة) */
    public function test_it_accepts_day_month_year_and_arabic_digit_dates(): void
    {
        $this->upload($this->sheetFile([
            ['أحمد', '01012345678', null, null, null, null, null, null, null, null, null, '15/01/2026'],
            ['منى', '01012345679', null, null, null, null, null, null, null, null, null, '٢٠٢٥-١٢-٠١'],
            ['سارة', '01012345670', null, null, null, null, null, null, null, null, null, '31/02/2025'],
        ]));

        $this->assertSame([4], array_keys(session('importErrors')));
        $this->assertContains('تاريخ التسجيل غير مفهوم — اكتبه بصيغة 2026-10-18.', session('importErrors')[4]);
    }

    public function test_text_cells_keep_the_leading_zero(): void
    {
        $spreadsheet = app(CustomerSpreadsheet::class)->template();
        $spreadsheet->getSheet(0)->setCellValue('A2', 'سارة');
        $spreadsheet->getSheet(0)->setCellValueExplicit('B2', '0225551234', DataType::TYPE_STRING);

        $this->upload($this->sheetFile([], $spreadsheet))->assertSessionHas('success');

        $this->assertDatabaseHas('customer_phones', ['phone' => '0225551234', 'is_primary' => true]);
    }

    public function test_the_import_page_renders_with_the_download_button(): void
    {
        $this->get(route('customers.import.create'))
            ->assertOk()
            ->assertSee(route('customers.import.template'))
            ->assertSee('تحميل القالب');

        $this->get(route('customers.index'))->assertOk()->assertSee(route('customers.import.create'));
    }
}
