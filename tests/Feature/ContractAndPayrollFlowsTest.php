<?php

namespace Tests\Feature;

use App\Domain\Customers\Enums\CustomerOrigin;
use App\Domain\Customers\Models\Customer;
use App\Domain\Inventory\Data\StockMovementData;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Payroll\Models\Payroll;
use App\Domain\Products\Enums\ProductKind;
use App\Domain\Products\Models\Product;
use App\Domain\Sales\Enums\ContractKind;
use App\Domain\Sales\Enums\ContractType;
use App\Domain\Sales\Enums\StockSource;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * مسارات عمل عابرة للوحدات: عقد الصيانة، التحويل للشؤون القانونية، وصرف المرتبات على دفعات.
 */
class ContractAndPayrollFlowsTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;

    private Product $device;

    private Product $cartridge;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-15');

        $this->warehouse = Warehouse::factory()->create();
        $this->device = Product::factory()->create(['kind' => ProductKind::Device, 'price' => 5000]);
        $this->cartridge = Product::factory()->create(['kind' => ProductKind::Cartridge, 'price' => 100]);

        foreach ([$this->device, $this->cartridge] as $product) {
            app(StockService::class)->record(new StockMovementData(
                productId: $product->id, warehouseId: $this->warehouse->id, type: StockMovementType::In,
                quantity: 100, movedAt: '2026-01-01',
            ));
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_a_maintenance_contract_has_a_manual_price_and_no_stock_items(): void
    {
        $customer = Customer::factory()->create(['origin' => CustomerOrigin::External]);

        $this->post(route('contracts.store'), [
            'kind' => ContractKind::Maintenance->value,
            'customer_id' => $customer->id,
            'warehouse_id' => $this->warehouse->id,
            'contract_date' => '2026-10-01',
            'maintenance_date' => '2026-11-01',
            'type' => ContractType::Installment->value,
            'price' => 1200,
            'paid' => 200,
            'installments_count' => 4,
            'installment_period_months' => 1,
            'first_due_date' => '2026-11-01',
            // بنود متبقية في النموذج المخفي تُهمل لعقد الصيانة
            'items' => [['product_id' => $this->device->id, 'quantity' => 1]],
        ])->assertSessionHasNoErrors();

        $contract = SalesContract::sole();

        $this->assertSame(ContractKind::Maintenance, $contract->kind);
        $this->assertStringStartsWith('AQM-2610-', $contract->code);
        $this->assertSame('1200.00', $contract->total);
        $this->assertSame('1000.00', $contract->remaining);
        $this->assertCount(0, $contract->items);
        $this->assertCount(4, $contract->installments);
        $this->assertSame(100, app(StockService::class)->available($this->device->id, $this->warehouse->id));
    }

    public function test_contracts_can_be_referred_to_legal_and_withdrawn(): void
    {
        $late = $this->installmentContract(['code' => 'AQW-LATE']);
        $legal = $this->installmentContract(['code' => 'AQW-LEGAL']);

        $this->post(route('contracts.legal.store', $legal))->assertSessionHasNoErrors();
        $this->assertNotNull($legal->fresh()->legal_referred_at);

        $this->assertNull($late->fresh()->legal_referred_at);

        $this->delete(route('contracts.legal.destroy', $legal));
        $this->assertNull($legal->fresh()->legal_referred_at);
    }

    public function test_payroll_can_be_paid_in_partial_payments_and_printed(): void
    {
        $employee = User::factory()->create(['role' => 'محصل', 'department' => 'المحصّلين']);

        $this->post(route('payroll.store', $employee), $this->payrollPayload(['base_salary' => 5000, 'absence_days' => 2]))
            ->assertSessionHasNoErrors();
        $payroll = Payroll::sole();
        $this->assertSame(2, $payroll->absence_days);

        $this->post(route('payroll.payments.store', $payroll), ['amount' => 2000, 'paid_at' => '2026-10-10']);
        $this->assertSame(3000.0, $payroll->fresh()->remainingAmount());
        $this->assertSame('صُرف جزئيًا', $payroll->fresh()->paymentStatus()['label']);

        $this->post(route('payroll.payments.store', $payroll), ['amount' => 3500, 'paid_at' => '2026-10-11'])
            ->assertSessionHas('error');
        $this->assertSame(3000.0, $payroll->fresh()->remainingAmount());

        $this->get(route('payroll.payslip', $payroll))->assertOk()->assertSee($employee->name)->assertSee('5,000.00');

        $summary = $this->get(route('payroll.index', ['month' => '2026-10']))->viewData('summary');
        $this->assertSame(2000.0, $summary['paid']);
        $this->assertSame(3000.0, $summary['remaining']);
    }

    private function installmentContract(array $attributes = [], ?string $paidAt = null): SalesContract
    {
        $contract = SalesContract::query()->create($this->contractAttributes(Customer::factory()->create(), $attributes + [
            'code' => 'AQW-'.fake()->unique()->numerify('####'),
            'type' => ContractType::Installment, 'total' => 1500, 'paid' => $paidAt ? 1500 : 1000,
            'remaining' => $paidAt ? 0 : 500,
        ]));

        $contract->installments()->create([
            'number' => 1, 'due_date' => '2026-10-05', 'amount' => 500,
            'paid_at' => $paidAt, 'paid_amount' => $paidAt ? 500 : null,
        ]);

        return $contract;
    }

    private function contractAttributes(Customer $customer, array $overrides): array
    {
        return $overrides + [
            'kind' => ContractKind::Sale, 'customer_id' => $customer->id, 'warehouse_id' => $this->warehouse->id,
            'stock_source' => StockSource::Warehouse, 'contract_date' => '2026-10-01',
            'installation_date' => '2026-10-02', 'maintenance_date' => '2026-11-01',
            'type' => ContractType::Cash, 'subtotal' => $overrides['total'] ?? 0, 'discount' => 0,
        ];
    }

    private function payrollPayload(array $overrides = []): array
    {
        return $overrides + [
            'month' => '2026-10', 'base_salary' => 0,
            'installment_count' => 0, 'installment_commission' => 0,
            'installed_device_count' => 0, 'installation_commission' => 0,
            'marketed_device_count' => 0, 'marketing_commission' => 0,
            'maintenance_count' => 0, 'maintenance_commission' => 0,
            'regular_deduction' => 0, 'attendance_deduction' => 0, 'bonus' => 0, 'absence_days' => 0,
        ];
    }
}
