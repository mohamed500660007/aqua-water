<?php

namespace Tests\Feature;

use App\Domain\Customers\Models\Customer;
use App\Domain\Inventory\Data\StockMovementData;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Maintenance\Models\MaintenancePlan;
use App\Domain\Products\Models\Product;
use App\Domain\Sales\Enums\ContractType;
use App\Domain\Sales\Enums\StockSource;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SalesContractManagementTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Warehouse $warehouse;

    private Product $device;

    private Product $filter;

    private StockService $stock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stock = app(StockService::class);
        $this->customer = Customer::factory()->create(['name' => 'أحمد سيد عبد الرحمن']);
        $this->warehouse = Warehouse::factory()->create(['name' => 'المخزن الرئيسي']);
        $this->device = Product::factory()->create(['name' => 'فلتر أكوا برو 7 مراحل', 'price' => 5000]);
        $this->filter = Product::factory()->create(['name' => 'شمعة قطنية', 'price' => 100]);

        $this->receive($this->device, 10);
        $this->receive($this->filter, 50);
    }

    private function receive(Product $product, int $quantity, ?Warehouse $warehouse = null): void
    {
        $this->stock->record(new StockMovementData(
            productId: $product->id,
            warehouseId: ($warehouse ?? $this->warehouse)->id,
            type: StockMovementType::In,
            quantity: $quantity,
            movedAt: today()->toDateString(),
        ));
    }

    private function available(Product $product): int
    {
        return $this->stock->available($product->id, $this->warehouse->id);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'contract_date' => '2026-08-01',
            'type' => ContractType::Cash->value,
            'notes' => 'تركيب خلال 48 ساعة',
            'items' => [
                ['product_id' => $this->device->id, 'quantity' => 2],
                ['product_id' => $this->filter->id, 'quantity' => 4],
            ],
            'discount' => 400,
            'paid' => 10000,
        ], $overrides);
    }

    private function installmentPayload(array $overrides = []): array
    {
        return $this->validPayload(array_merge([
            'type' => ContractType::Installment->value,
            'paid' => 2400,
            'installments_count' => 6,
            'installment_period_months' => 1,
            'first_due_date' => '2026-09-01',
        ], $overrides));
    }

    public function test_it_creates_a_cash_contract_with_items_and_totals(): void
    {
        $response = $this->post(route('contracts.store'), $this->validPayload());

        $contract = SalesContract::sole();

        $response->assertRedirect(route('contracts.show', $contract));

        // 2 × 5000 + 4 × 100 = 10,400 قبل الخصم
        $this->assertSame('10400.00', $contract->subtotal);
        $this->assertSame('400.00', $contract->discount);
        $this->assertSame('10000.00', $contract->total);
        $this->assertSame('10000.00', $contract->paid);
        $this->assertSame('0.00', $contract->remaining);
        $this->assertNull($contract->installment_amount);
        $this->assertCount(2, $contract->items);
        $this->assertCount(0, $contract->installments);
        $this->assertStringStartsWith('AQW-2608-', $contract->code);
    }

    public function test_item_prices_come_from_the_product_not_the_request(): void
    {
        $this->post(route('contracts.store'), $this->validPayload([
            'items' => [['product_id' => $this->device->id, 'quantity' => 1, 'unit_price' => 1]],
            'discount' => 0,
            'paid' => 0,
        ]));

        $item = SalesContract::sole()->items->first();

        $this->assertSame('5000.00', $item->unit_price);
        $this->assertSame('5000.00', $item->total);
        $this->assertSame('فلتر أكوا برو 7 مراحل', $item->product_name);
    }

    public function test_creating_a_contract_issues_stock_out_movements(): void
    {
        $this->post(route('contracts.store'), $this->validPayload());

        $contract = SalesContract::sole();

        $this->assertSame(8, $this->available($this->device));
        $this->assertSame(46, $this->available($this->filter));
        $this->assertCount(2, $contract->stockMovements);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->device->id,
            'type' => StockMovementType::Out->value,
            'quantity' => 2,
            'reference_type' => SalesContract::class,
            'reference_id' => $contract->id,
        ]);
    }

    public function test_it_rejects_quantities_above_the_available_stock(): void
    {
        $this->post(route('contracts.store'), $this->validPayload([
            'items' => [['product_id' => $this->device->id, 'quantity' => 11]],
        ]))->assertSessionHasErrors('items.0.quantity');

        $this->assertDatabaseCount('sales_contracts', 0);
        $this->assertSame(10, $this->available($this->device));
    }

    public function test_it_creates_an_installment_contract_with_a_schedule(): void
    {
        $this->post(route('contracts.store'), $this->installmentPayload())
            ->assertSessionHasNoErrors();

        $contract = SalesContract::sole();

        $this->assertSame(ContractType::Installment, $contract->type);
        $this->assertSame('7600.00', $contract->remaining);
        $this->assertSame('1266.67', $contract->installment_amount);
        $this->assertCount(6, $contract->installments);

        $installments = $contract->installments;
        $this->assertSame('2026-09-01', $installments->first()->due_date->format('Y-m-d'));
        $this->assertSame('2027-02-01', $installments->last()->due_date->format('Y-m-d'));

        // مجموع الأقساط يساوي المتبقي تمامًا رغم التقريب
        $this->assertSame(7600.0, round($installments->sum(fn ($i) => (float) $i->amount), 2));
    }

    public function test_installment_periods_longer_than_a_month_are_supported(): void
    {
        $this->post(route('contracts.store'), $this->installmentPayload([
            'installments_count' => 4,
            'installment_period_months' => 3,
        ]))->assertSessionHasNoErrors();

        $installments = SalesContract::sole()->installments;

        $this->assertSame('2026-09-01', $installments->first()->due_date->format('Y-m-d'));
        $this->assertSame('2027-06-01', $installments->last()->due_date->format('Y-m-d'));
    }

    public function test_cash_contracts_ignore_installment_fields(): void
    {
        $this->post(route('contracts.store'), $this->validPayload([
            'installments_count' => 12,
            'installment_period_months' => 1,
            'first_due_date' => '2026-09-01',
        ]));

        $contract = SalesContract::sole();

        $this->assertNull($contract->installments_count);
        $this->assertNull($contract->first_due_date);
        $this->assertCount(0, $contract->installments);
    }

    public function test_it_assigns_a_sales_rep_and_an_installer(): void
    {
        $salesRep = User::factory()->create(['role' => 'مندوب مبيعات']);
        $installer = User::factory()->create(['role' => 'فني تركيب']);

        $this->post(route('contracts.store'), $this->validPayload([
            'sales_rep_id' => $salesRep->id,
            'installer_id' => $installer->id,
        ]))->assertSessionHasNoErrors();

        $contract = SalesContract::sole();

        $this->assertSame($salesRep->id, $contract->sales_rep_id);
        $this->assertSame($installer->id, $contract->installer_id);
        $this->assertTrue($contract->salesRep->is($salesRep));
        $this->assertTrue($contract->installer->is($installer));
    }

    public function test_sales_rep_and_installer_are_optional(): void
    {
        $this->post(route('contracts.store'), $this->validPayload())
            ->assertSessionHasNoErrors();

        $contract = SalesContract::sole();

        $this->assertNull($contract->sales_rep_id);
        $this->assertNull($contract->installer_id);
    }

    public function test_it_rejects_a_user_with_the_wrong_role_as_sales_rep_or_installer(): void
    {
        $installer = User::factory()->create(['role' => 'فني تركيب']);
        $salesRep = User::factory()->create(['role' => 'مندوب مبيعات']);

        $this->post(route('contracts.store'), $this->validPayload([
            'sales_rep_id' => $installer->id,
        ]))->assertSessionHasErrors('sales_rep_id');

        $this->post(route('contracts.store'), $this->validPayload([
            'installer_id' => $salesRep->id,
        ]))->assertSessionHasErrors('installer_id');

        $this->assertDatabaseCount('sales_contracts', 0);
    }

    public function test_contract_create_page_lists_sales_reps_and_installers(): void
    {
        $salesRep = User::factory()->create(['role' => 'مندوب مبيعات']);
        $installer = User::factory()->create(['role' => 'فني تركيب']);

        $this->get(route('contracts.create'))
            ->assertOk()
            ->assertSee('تعيين موظفين')
            ->assertSee($salesRep->name)
            ->assertSee($installer->name);
    }

    public function test_show_page_displays_assigned_sales_rep_and_installer(): void
    {
        $salesRep = User::factory()->create(['role' => 'مندوب مبيعات', 'name' => 'ريهام عبد الفتاح']);
        $installer = User::factory()->create(['role' => 'فني تركيب', 'name' => 'سيد عبد الحميد']);

        $this->post(route('contracts.store'), $this->validPayload([
            'sales_rep_id' => $salesRep->id,
            'installer_id' => $installer->id,
        ]));

        $this->get(route('contracts.show', SalesContract::sole()))
            ->assertOk()
            ->assertSee($salesRep->name)
            ->assertSee($installer->name);
    }

    public function test_it_validates_customer_items_and_money(): void
    {
        $this->post(route('contracts.store'), $this->validPayload([
            'customer_id' => '',
            'items' => [],
        ]))->assertSessionHasErrors(['customer_id', 'items']);

        $this->post(route('contracts.store'), $this->validPayload(['discount' => 99999]))
            ->assertSessionHasErrors('discount');

        $this->post(route('contracts.store'), $this->validPayload(['paid' => 99999]))
            ->assertSessionHasErrors('paid');

        $this->post(route('contracts.store'), $this->validPayload([
            'items' => [['product_id' => $this->device->id, 'quantity' => 0]],
        ]))->assertSessionHasErrors('items.0.quantity');

        $this->assertDatabaseCount('sales_contracts', 0);
    }

    public function test_installment_contracts_require_schedule_fields_and_a_remaining_amount(): void
    {
        $this->post(route('contracts.store'), $this->validPayload([
            'type' => ContractType::Installment->value,
        ]))->assertSessionHasErrors(['installments_count', 'installment_period_months', 'first_due_date']);

        $this->post(route('contracts.store'), $this->installmentPayload([
            'paid' => 10000,
        ]))->assertSessionHasErrors('paid');

        $this->post(route('contracts.store'), $this->installmentPayload([
            'first_due_date' => '2026-07-01',
        ]))->assertSessionHasErrors('first_due_date');

        $this->assertDatabaseCount('sales_contracts', 0);
    }

    public function test_it_snapshots_the_maintenance_plan_items(): void
    {
        $plan = MaintenancePlan::factory()
            ->withItems([['product_id' => $this->filter->id, 'interval_months' => 3, 'replacement_date' => '2026-11-01']])
            ->create(['name' => 'باقة سنوية']);

        $this->post(route('contracts.store'), $this->validPayload(['maintenance_plan_id' => $plan->id]));

        $contract = SalesContract::sole();

        $this->assertCount(1, $contract->maintenanceItems);
        $this->assertSame('شمعة قطنية', $contract->maintenanceItems->first()->product_name);

        // تعديل الخطة لاحقًا لا يمس نسخة العقد
        $plan->items()->update(['interval_months' => 12]);
        $plan->update(['name' => 'باقة معدّلة']);

        $this->assertSame(3, $contract->fresh()->maintenanceItems->first()->interval_months);
    }

    public function test_updating_a_contract_adjusts_stock_by_the_difference(): void
    {
        $this->post(route('contracts.store'), $this->validPayload());
        $contract = SalesContract::sole();

        $this->assertSame(8, $this->available($this->device));

        $this->put(route('contracts.update', $contract), $this->validPayload([
            'items' => [
                ['product_id' => $this->device->id, 'quantity' => 5],
                ['product_id' => $this->filter->id, 'quantity' => 1],
            ],
            'discount' => 0,
            'paid' => 0,
        ]))->assertRedirect(route('contracts.show', $contract));

        // الجهاز: 2 ← 5 (صرف 3 إضافية)، الشمعة: 4 ← 1 (رد 3)
        $this->assertSame(5, $this->available($this->device));
        $this->assertSame(49, $this->available($this->filter));

        $contract->refresh();
        $this->assertSame('25100.00', $contract->subtotal);
        $this->assertSame('25100.00', $contract->remaining);
    }

    public function test_update_allows_keeping_quantities_already_reserved_by_the_contract(): void
    {
        $this->post(route('contracts.store'), $this->validPayload([
            'items' => [['product_id' => $this->device->id, 'quantity' => 10]],
            'discount' => 0,
            'paid' => 0,
        ]))->assertSessionHasNoErrors();

        $contract = SalesContract::sole();
        $this->assertSame(0, $this->available($this->device));

        $this->put(route('contracts.update', $contract), $this->validPayload([
            'items' => [['product_id' => $this->device->id, 'quantity' => 10]],
            'discount' => 0,
            'paid' => 0,
        ]))->assertSessionHasNoErrors();

        $this->assertSame(0, $this->available($this->device));
    }

    public function test_deleting_a_contract_returns_the_stock(): void
    {
        $this->post(route('contracts.store'), $this->validPayload());
        $contract = SalesContract::sole();

        $this->delete(route('contracts.destroy', $contract))
            ->assertRedirect(route('contracts.index'));

        $this->assertSoftDeleted($contract);
        $this->assertSame(10, $this->available($this->device));
        $this->assertSame(50, $this->available($this->filter));
    }

    public function test_contract_pages_render(): void
    {
        $this->post(route('contracts.store'), $this->installmentPayload());
        $contract = SalesContract::sole();

        $this->get(route('contracts.index'))->assertOk()->assertSee($contract->code);
        $this->get(route('contracts.create'))->assertOk()->assertSee('الملخص المالي');
        $this->get(route('contracts.show', $contract))->assertOk()
            ->assertSee($this->customer->name)
            ->assertSee('جدول الأقساط');
        $this->get(route('contracts.edit', $contract))->assertOk()->assertSee($contract->code);
    }

    public function test_it_sources_stock_from_the_assigned_sales_rep_instead_of_the_warehouse(): void
    {
        $salesRep = User::factory()->create(['role' => 'مندوب مبيعات']);
        app(EmployeeStockService::class)->handOver($salesRep->id, $this->device->id, $this->warehouse->id, 5, today()->toDateString());

        $this->post(route('contracts.store'), $this->validPayload([
            'sales_rep_id' => $salesRep->id,
            'stock_source' => StockSource::SalesRep->value,
            'items' => [['product_id' => $this->device->id, 'quantity' => 2]],
            'discount' => 0,
            'paid' => 0,
        ]))->assertSessionHasNoErrors();

        $contract = SalesContract::sole();

        $this->assertSame(StockSource::SalesRep, $contract->stock_source);
        // المخزن كان قد فقد 5 وحدات بالفعل عند تسليمها للمندوب، ولم يتأثر إضافيًا بالبيع
        $this->assertSame(5, $this->available($this->device));
        $this->assertSame(3, app(EmployeeStockService::class)->available($salesRep->id, $this->device->id));
        $this->assertDatabaseHas('employee_stock_movements', [
            'user_id' => $salesRep->id,
            'product_id' => $this->device->id,
            'type' => 'out',
            'quantity' => 2,
            'reference_type' => SalesContract::class,
            'reference_id' => $contract->id,
        ]);
    }

    public function test_it_sources_stock_from_the_assigned_installer(): void
    {
        $installer = User::factory()->create(['role' => 'فني تركيب']);
        app(EmployeeStockService::class)->handOver($installer->id, $this->filter->id, $this->warehouse->id, 10, today()->toDateString());

        $this->post(route('contracts.store'), $this->validPayload([
            'installer_id' => $installer->id,
            'stock_source' => StockSource::Installer->value,
            'items' => [['product_id' => $this->filter->id, 'quantity' => 4]],
            'discount' => 0,
            'paid' => 0,
        ]))->assertSessionHasNoErrors();

        // المخزن فقد 10 وحدات بالفعل عند تسليمها للفني، ولم يتأثر إضافيًا بالبيع
        $this->assertSame(40, $this->available($this->filter));
        $this->assertSame(6, app(EmployeeStockService::class)->available($installer->id, $this->filter->id));
    }

    public function test_stock_source_requires_the_matching_employee_to_be_assigned(): void
    {
        $this->post(route('contracts.store'), $this->validPayload([
            'stock_source' => StockSource::SalesRep->value,
        ]))->assertSessionHasErrors('sales_rep_id');

        $this->post(route('contracts.store'), $this->validPayload([
            'stock_source' => StockSource::Installer->value,
        ]))->assertSessionHasErrors('installer_id');

        $this->assertDatabaseCount('sales_contracts', 0);
    }

    public function test_it_rejects_quantities_above_what_the_employee_holds(): void
    {
        $salesRep = User::factory()->create(['role' => 'مندوب مبيعات']);
        app(EmployeeStockService::class)->handOver($salesRep->id, $this->device->id, $this->warehouse->id, 1, today()->toDateString());

        $this->post(route('contracts.store'), $this->validPayload([
            'sales_rep_id' => $salesRep->id,
            'stock_source' => StockSource::SalesRep->value,
            'items' => [['product_id' => $this->device->id, 'quantity' => 2]],
        ]))->assertSessionHasErrors('items.0.quantity');

        $this->assertDatabaseCount('sales_contracts', 0);
        // رصيد المندوب لم يُمس رغم رفض العملية
        $this->assertSame(1, app(EmployeeStockService::class)->available($salesRep->id, $this->device->id));
    }

    public function test_deleting_an_employee_sourced_contract_returns_stock_to_the_employee(): void
    {
        $salesRep = User::factory()->create(['role' => 'مندوب مبيعات']);
        app(EmployeeStockService::class)->handOver($salesRep->id, $this->device->id, $this->warehouse->id, 5, today()->toDateString());

        $this->post(route('contracts.store'), $this->validPayload([
            'sales_rep_id' => $salesRep->id,
            'stock_source' => StockSource::SalesRep->value,
            'items' => [['product_id' => $this->device->id, 'quantity' => 2]],
            'discount' => 0,
            'paid' => 0,
        ]));

        $contract = SalesContract::sole();

        $this->delete(route('contracts.destroy', $contract))->assertRedirect(route('contracts.index'));

        $this->assertSoftDeleted($contract);
        $this->assertSame(5, app(EmployeeStockService::class)->available($salesRep->id, $this->device->id));
        // المخزن لم يتأثر طوال العملية — فقدت 5 وحدات عند تسليمها للمندوب فقط
        $this->assertSame(5, $this->available($this->device));
    }

    public function test_updating_moves_stock_correctly_when_the_source_changes_from_warehouse_to_employee(): void
    {
        $salesRep = User::factory()->create(['role' => 'مندوب مبيعات']);
        app(EmployeeStockService::class)->handOver($salesRep->id, $this->device->id, $this->warehouse->id, 5, today()->toDateString());

        $this->post(route('contracts.store'), $this->validPayload([
            'items' => [['product_id' => $this->device->id, 'quantity' => 2]],
            'discount' => 0,
            'paid' => 0,
        ]));

        $contract = SalesContract::sole();
        // المخزن بدأ بـ 10، أخذ منه المندوب 5 عبر handOver، ثم صُرف بندان من المخزن أيضًا
        $this->assertSame(3, $this->available($this->device));
        $this->assertSame(5, app(EmployeeStockService::class)->available($salesRep->id, $this->device->id));

        $this->put(route('contracts.update', $contract), $this->validPayload([
            'sales_rep_id' => $salesRep->id,
            'stock_source' => StockSource::SalesRep->value,
            'items' => [['product_id' => $this->device->id, 'quantity' => 2]],
            'discount' => 0,
            'paid' => 0,
        ]))->assertSessionHasNoErrors();

        // القديم رجع للمخزن بالكامل، والجديد صُرف من المندوب
        $this->assertSame(5, $this->available($this->device));
        $this->assertSame(3, app(EmployeeStockService::class)->available($salesRep->id, $this->device->id));
    }

    public function test_show_page_displays_the_stock_source_label(): void
    {
        $salesRep = User::factory()->create(['role' => 'مندوب مبيعات']);
        app(EmployeeStockService::class)->handOver($salesRep->id, $this->device->id, $this->warehouse->id, 5, today()->toDateString());

        $this->post(route('contracts.store'), $this->validPayload([
            'sales_rep_id' => $salesRep->id,
            'stock_source' => StockSource::SalesRep->value,
            'items' => [['product_id' => $this->device->id, 'quantity' => 1]],
            'discount' => 0,
            'paid' => 0,
        ]));

        $this->get(route('contracts.show', SalesContract::sole()))
            ->assertOk()
            ->assertSee('من مندوب المبيعات');
    }

    public function test_index_does_not_run_a_query_per_contract(): void
    {
        $this->receive($this->filter, 200);

        foreach (range(1, 8) as $number) {
            $this->post(route('contracts.store'), $this->validPayload([
                'items' => [['product_id' => $this->filter->id, 'quantity' => 1]],
                'discount' => 0,
                'paid' => 0,
            ]));
        }

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->get(route('contracts.index'))->assertOk();

        // قائمة + عدّاد البنود + العملاء + الإحصائيات — لا استعلام لكل عقد
        $this->assertLessThanOrEqual(8, $queries);
    }

    public function test_index_searches_and_filters_contracts(): void
    {
        $this->post(route('contracts.store'), $this->validPayload());
        $other = Customer::factory()->create(['name' => 'شركة النيل']);
        $this->post(route('contracts.store'), $this->installmentPayload([
            'customer_id' => $other->id,
            'items' => [['product_id' => $this->filter->id, 'quantity' => 2]],
            'discount' => 0,
            'paid' => 50,
        ]));

        $this->assertCount(1, $this->get(route('contracts.index', ['q' => 'النيل']))->viewData('contracts')->items());
        $this->assertCount(
            1,
            $this->get(route('contracts.index', ['type' => ContractType::Cash->value]))->viewData('contracts')->items(),
        );
        $this->assertCount(
            1,
            $this->get(route('contracts.index', ['customer_id' => $this->customer->id]))->viewData('contracts')->items(),
        );
    }
}
