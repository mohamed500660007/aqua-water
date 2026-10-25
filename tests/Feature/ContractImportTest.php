<?php

namespace Tests\Feature;

use App\Domain\Customers\Models\Customer;
use App\Domain\Inventory\Data\StockMovementData;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Maintenance\Models\MaintenancePlan;
use App\Domain\Products\Models\Product;
use App\Domain\Sales\Enums\ContractKind;
use App\Domain\Sales\Enums\ContractType;
use App\Domain\Sales\Import\ContractReferences;
use App\Domain\Sales\Import\ContractSpreadsheet;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ContractImportTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Warehouse $warehouse;

    private Product $device;

    private Product $filter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::factory()->create(['code' => 'CUS-00012', 'name' => 'أحمد سيد']);
        $this->customer->phones()->update(['phone' => '01012345678']);
        $this->warehouse = Warehouse::factory()->create(['name' => 'المخزن الرئيسي']);
        $this->device = Product::factory()->create(['name' => 'فلتر أكوا برو', 'price' => 5000]);
        $this->filter = Product::factory()->create(['name' => 'شمعة قطنية', 'price' => 100]);

        app(StockService::class)->record(new StockMovementData(
            productId: $this->device->id,
            warehouseId: $this->warehouse->id,
            type: StockMovementType::In,
            quantity: 3,
            movedAt: today()->toDateString(),
        ));
    }

    /**
     * يملأ القالب الحقيقي بأسماء الأعمدة كما تظهر للموظفة.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    private function sheetFile(array $rows): UploadedFile
    {
        $spreadsheet = app(ContractSpreadsheet::class)->template(new ContractReferences);
        $sheet = $spreadsheet->getSheet(0);
        $columns = [];

        foreach ($sheet->getRowIterator(1, 1)->current()->getCellIterator() as $cell) {
            $columns[trim(str_replace('*', '', (string) $cell->getValue()))] = $cell->getColumn();
        }

        foreach ($rows as $index => $values) {
            foreach ($values as $title => $value) {
                $sheet->getCell($columns[$title].($index + 2))->setValueExplicit((string) $value, 's');
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'contracts').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'contracts.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function upload(array $rows)
    {
        return $this->from(route('contracts.import.create'))
            ->post(route('contracts.import.store'), ['file' => $this->sheetFile($rows)]);
    }

    public function test_the_template_lists_the_current_products_and_staff(): void
    {
        User::factory()->create(['role' => 'فني تركيب', 'name' => 'محمود الفني']);

        $response = $this->get(route('contracts.import.template'))->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'tpl').'.xlsx';
        file_put_contents($path, $response->streamedContent());
        $spreadsheet = IOFactory::load($path);

        $sheet = $spreadsheet->getSheet(0);
        // نفس ترتيب جدول العقود: العميل ثم تاريخ العقد أول عمودين
        $this->assertSame('العميل (كود أو هاتف) *', $sheet->getCell('A1')->getValue());
        $this->assertSame('تاريخ العقد *', $sheet->getCell('B1')->getValue());
        $this->assertSame(Border::BORDER_THIN, $sheet->getStyle('C10')->getBorders()->getRight()->getBorderStyle());
        $lists = collect($spreadsheet->getSheetByName('قوائم')->toArray())->flatten()->filter()->all();
        $this->assertContains('فلتر أكوا برو', $lists);
        $this->assertContains('محمود الفني', $lists);
    }

    public function test_it_imports_an_old_installment_contract_without_touching_stock(): void
    {
        $this->upload([[
            'العميل (كود أو هاتف)' => 'cus-12',
            'تاريخ العقد' => '2025-03-15',
            'طريقة الدفع' => 'تقسيط',
            'المنتج 1' => 'فلتر أكوا برو',
            'سعر القطعة 1' => '4,500',
            'المنتج 2' => 'شمعة قطنية',
            'الكمية 2' => '2',
            'الخصم' => '200',
            'المدفوع حتى الآن' => '1500',
            'عدد الأقساط المتبقية' => '6',
            'تاريخ أول قسط متبقٍ' => '01/11/2025',
        ]])->assertRedirect(route('contracts.index'))->assertSessionHas('success');

        $contract = SalesContract::with(['items', 'installments'])->firstOrFail();

        $this->assertFalse($contract->stock_tracked);
        $this->assertSame($this->customer->id, $contract->customer_id);
        $this->assertSame('AQW-2503-0001', $contract->code);
        $this->assertSame(ContractType::Installment, $contract->type);
        // 4500 + 2×100 بسعر المنتج الحالي للشمعة، ثم الخصم والمدفوع
        $this->assertSame('4700.00', $contract->subtotal);
        $this->assertSame('3000.00', $contract->remaining);
        $this->assertSame([4500.0, 100.0], $contract->items->pluck('unit_price')->map(fn ($p) => (float) $p)->all());
        $this->assertCount(6, $contract->installments);
        $this->assertSame('2025-11-01', $contract->installments->first()->due_date->toDateString());
        $this->assertSame($this->warehouse->id, $contract->warehouse_id);
        $this->assertSame('2025-03-15', $contract->installation_date->toDateString());

        $this->assertSame(3, app(StockService::class)->available($this->device->id, $this->warehouse->id));
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_it_finds_the_customer_by_phone_and_imports_maintenance_contracts(): void
    {
        $plan = MaintenancePlan::factory()->create(['name' => 'خطة سنوية']);

        $this->upload([[
            'نوع التعاقد' => 'عقد صيانة',
            'العميل (كود أو هاتف)' => '1012345678',
            'تاريخ العقد' => '2025-06-01',
            'طريقة الدفع' => 'نقدي',
            'سعر عقد الصيانة' => '800',
            'المدفوع حتى الآن' => '800',
            'خطة الصيانة' => 'خطة سنوية',
        ]])->assertSessionHas('success');

        $contract = SalesContract::firstOrFail();
        $this->assertSame(ContractKind::Maintenance, $contract->kind);
        $this->assertSame($this->customer->id, $contract->customer_id);
        $this->assertSame($plan->id, $contract->maintenance_plan_id);
        $this->assertSame('0.00', $contract->remaining);
        $this->assertStringStartsWith('AQM-2506-', $contract->code);
    }

    public function test_one_invalid_row_imports_nothing_and_explains_every_problem(): void
    {
        $this->upload([
            ['العميل (كود أو هاتف)' => 'CUS-00012', 'تاريخ العقد' => '2025-01-01', 'طريقة الدفع' => 'نقدي', 'المنتج 1' => 'فلتر أكوا برو'],
            ['العميل (كود أو هاتف)' => 'CUS-99999', 'تاريخ العقد' => '2025-01-01', 'طريقة الدفع' => 'نقدي', 'المنتج 1' => 'جهاز غير موجود'],
            ['العميل (كود أو هاتف)' => 'CUS-00012', 'تاريخ العقد' => '2025-01-01', 'طريقة الدفع' => 'تقسيط', 'المنتج 1' => 'شمعة قطنية'],
            ['العميل (كود أو هاتف)' => 'CUS-00012', 'تاريخ العقد' => '2025-01-01', 'طريقة الدفع' => 'نقدي', 'المنتج 1' => 'شمعة قطنية', 'المدفوع حتى الآن' => '500'],
        ])->assertRedirect(route('contracts.import.create'))->assertSessionHas('importRows', 4);

        $errors = session('importErrors');
        $this->assertSame([3, 4, 5], array_keys($errors));
        $this->assertContains('لا يوجد عميل بالكود CUS-99999.', $errors[3]);
        $this->assertContains('لا يوجد منتج بالاسم أو الكود «جهاز غير موجود».', $errors[3]);
        $this->assertContains('اكتب عدد الأقساط المتبقية للعقد بالتقسيط.', $errors[4]);
        $this->assertContains('المبلغ المدفوع لا يمكن أن يتجاوز إجمالي العقد.', $errors[5]);
        $this->assertDatabaseCount('sales_contracts', 0);
    }

    public function test_a_phone_shared_by_two_customers_asks_for_the_code(): void
    {
        Customer::factory()->create()->phones()->update(['phone' => '01012345678']);

        $this->upload([['العميل (كود أو هاتف)' => '01012345678', 'تاريخ العقد' => '2025-01-01', 'طريقة الدفع' => 'نقدي', 'المنتج 1' => 'شمعة قطنية']]);

        $this->assertStringContainsString('أكثر من عميل', session('importErrors')[2][0]);
    }

    /** العقد المستورد لم يخصم شيئًا، فتعديل كمياته أو مصدره لاحقًا لا يجوز أن يحرّك المخزون */
    public function test_editing_an_imported_contract_never_moves_stock(): void
    {
        $this->upload([['العميل (كود أو هاتف)' => 'CUS-00012', 'تاريخ العقد' => '2025-01-01', 'طريقة الدفع' => 'نقدي', 'المنتج 1' => 'فلتر أكوا برو', 'الكمية 1' => '1']]);
        $contract = SalesContract::firstOrFail();

        $this->put(route('contracts.update', $contract), [
            'kind' => 'sale',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'stock_source' => 'warehouse',
            'contract_date' => '2025-01-01',
            'installation_date' => '2025-01-01',
            'maintenance_date' => '2025-01-01',
            'type' => 'cash',
            // أكثر من المتاح في المخزن (3) — لا يُرفض لأن العقد لا يمس المخزون
            'items' => [['product_id' => $this->device->id, 'quantity' => 5]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(5, (int) $contract->fresh()->items()->sum('quantity'));
        $this->assertSame(3, app(StockService::class)->available($this->device->id, $this->warehouse->id));
    }

    public function test_the_contract_form_cannot_override_the_product_price(): void
    {
        $this->post(route('contracts.store'), [
            'kind' => 'sale',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'stock_source' => 'warehouse',
            'contract_date' => today()->toDateString(),
            'installation_date' => today()->toDateString(),
            'maintenance_date' => today()->toDateString(),
            'type' => 'cash',
            'items' => [['product_id' => $this->device->id, 'quantity' => 1, 'unit_price' => 1]],
        ]);

        $this->assertSame('5000.00', SalesContract::firstOrFail()->subtotal);
    }

    public function test_the_import_page_renders_and_is_linked_from_the_contracts_list(): void
    {
        $this->get(route('contracts.import.create'))->assertOk()->assertSee(route('contracts.import.template'));
        $this->get(route('contracts.index'))->assertOk()->assertSee(route('contracts.import.create'));
    }
}
