<?php

namespace Tests\Feature;

use App\Domain\Customers\Models\Customer;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Payroll\Models\Payroll;
use App\Domain\Products\Models\Product;
use App\Domain\Sales\Enums\ContractType;
use App\Domain\Sales\Enums\StockSource;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_sheet_can_search_filter_and_paginate_employees(): void
    {
        User::factory()->count(21)->create(['role' => 'فني تركيب']);
        $target = User::factory()->create(['name' => 'محصل مميز', 'role' => 'محصل']);

        $page = $this->get(route('payroll.index', ['month' => '2026-09']));
        $this->assertCount(20, $page->viewData('employees')->items());
        $this->assertSame(22, $page->viewData('employees')->total());

        $filtered = $this->get(route('payroll.index', [
            'month' => '2026-09', 'q' => 'مميز', 'role' => 'محصل',
        ]));

        $this->assertCount(1, $filtered->viewData('rows'));
        $this->assertTrue($filtered->viewData('rows')->first()['user']->is($target));
    }

    public function test_monthly_sheet_calculates_installments_devices_and_unique_maintenance_customers(): void
    {
        $employee = User::factory()->create([
            'role' => 'فني تركيب',
            'base_salary' => 3000,
            'installment_commission' => 10,
            'installation_commission' => 200,
            'marketing_commission' => 300,
            'maintenance_commission' => 200,
        ]);
        $contract = $this->contract($employee);
        $product = Product::factory()->create();

        $contract->items()->create([
            'product_id' => $product->id, 'product_name' => $product->name,
            'unit_price' => 1000, 'quantity' => 2, 'total' => 2000,
        ]);
        $contract->installments()->create([
            'number' => 1, 'due_date' => '2026-09-01', 'amount' => 500,
            'paid_at' => '2026-09-10', 'paid_amount' => 500, 'collected_by_id' => $employee->id,
        ]);

        $response = $this->get(route('payroll.index', ['month' => '2026-09']))->assertOk();
        $row = $response->viewData('rows')->firstWhere(fn (array $row) => $row['user']->is($employee));

        $this->assertSame(1, $row['values']['installment_count']);
        $this->assertSame(2, $row['values']['installed_device_count']);
        $this->assertSame(2, $row['values']['marketed_device_count']);
        // لا عمليات صيانة مسجّلة لهذا الموظف في الشهر
        $this->assertSame(0, $row['values']['maintenance_count']);
        $this->assertSame(0.0, $row['values']['maintenance_commission']);
    }

    public function test_payroll_can_be_manually_adjusted_and_saved_as_a_monthly_snapshot(): void
    {
        $employee = User::factory()->create(['role' => 'مندوبة']);

        $this->post(route('payroll.store', $employee), [
            'month' => '2026-09', 'base_salary' => 0,
            'installment_count' => 0, 'installment_commission' => 10,
            'installed_device_count' => 3, 'installation_commission' => 300,
            'marketed_device_count' => 0, 'marketing_commission' => 300,
            'maintenance_count' => 2, 'maintenance_commission' => 600,
            'regular_deduction' => 50, 'deduction_reason' => 'خصم',
            'attendance_deduction' => 25, 'bonus' => 100, 'note' => null,
        ])->assertRedirect(route('payroll.index', ['month' => '2026-09']));

        $this->assertDatabaseHas('payrolls', [
            'user_id' => $employee->id, 'month' => '2026-09-01 00:00:00',
            'installed_device_count' => 3, 'maintenance_count' => 2, 'total' => 1525,
        ]);

        $this->post(route('payroll.store', $employee), [
            'month' => '2026-09', 'base_salary' => 0,
            'installment_count' => 0, 'installment_commission' => 10,
            'installed_device_count' => 4, 'installation_commission' => 300,
            'marketed_device_count' => 0, 'marketing_commission' => 300,
            'maintenance_count' => 2, 'maintenance_commission' => 600,
            'regular_deduction' => 50, 'deduction_reason' => 'خصم',
            'attendance_deduction' => 25, 'bonus' => 100, 'note' => 'تم التعديل',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('payrolls', 1);
        $this->assertDatabaseHas('payrolls', [
            'user_id' => $employee->id, 'installed_device_count' => 4, 'total' => 1825,
        ]);
    }

    private function contract(User $employee): SalesContract
    {
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();

        return SalesContract::query()->create([
            'code' => 'CNT-PAYROLL', 'customer_id' => $customer->id, 'warehouse_id' => $warehouse->id,
            'installer_id' => $employee->id, 'sales_rep_id' => $employee->id, 'stock_source' => StockSource::Warehouse,
            'contract_date' => '2026-09-01', 'installation_date' => '2026-09-05',
            'collection_date' => '2026-09-10', 'maintenance_date' => '2026-10-01',
            'type' => ContractType::Cash, 'subtotal' => 2000, 'discount' => 0,
            'total' => 2000, 'paid' => 2000, 'remaining' => 0,
        ]);
    }

    public function test_payroll_report_aggregates_saved_and_automatic_months(): void
    {
        $approved = User::factory()->create(['name' => 'موظف معتمد', 'department' => 'الفنيين', 'base_salary' => 3000, 'role' => 'فني تركيب']);
        $pending = User::factory()->create(['name' => 'موظف غير معتمد', 'department' => 'السكرتارية', 'base_salary' => 2000, 'role' => 'سكرتارية']);

        $this->post(route('payroll.store', $approved), [
            'month' => '2026-09', 'base_salary' => 3000,
            'installment_count' => 0, 'installment_commission' => 10,
            'installed_device_count' => 2, 'installation_commission' => 100,
            'marketed_device_count' => 0, 'marketing_commission' => 0,
            'maintenance_count' => 0, 'maintenance_commission' => 0,
            'regular_deduction' => 50, 'deduction_reason' => 'تأخير', 'attendance_deduction' => 0, 'bonus' => 150, 'absence_days' => 0,
        ])->assertSessionHasNoErrors();
        $payroll = Payroll::sole();
        $this->post(route('payroll.payments.store', $payroll), ['amount' => 1000, 'paid_at' => '2026-09-30']);

        $september = $this->get(route('payroll.report', ['from' => '2026-09-01', 'to' => '2026-09-30']))
            ->assertOk()->assertSee('size: A4 portrait', false);
        foreach (['name="q"', 'name="role"', 'name="department"', 'name="approval"', 'name="payment"', 'name="content"', 'name="sort"'] as $field) {
            $september->assertSee($field, false);
        }

        $row = $september->viewData('rows')->firstWhere(fn ($row) => $row['user']->is($approved));
        $this->assertSame(3300.0, $row['net']);            // 3000 + 2×100 + 150 − 50
        $this->assertSame(200.0, $row['installation']);
        $this->assertSame(1000.0, $row['paid']);
        $this->assertSame(2300.0, $row['remaining']);
        $this->assertSame('partial', $row['status']['key']);

        $other = $september->viewData('rows')->firstWhere(fn ($row) => $row['user']->is($pending));
        $this->assertSame(2000.0, $other['net']);
        $this->assertSame('pending', $other['status']['key']);
        $this->assertSame(5300.0, $september->viewData('totals')['net']);

        // كشف معتمد بصافي صفر: لا يظهر كأنه «تم الصرف»
        // القسم ثابت حتى لا يقع عشوائيًا في «السكرتارية» فيدخل في فلتر القسم أدناه
        $idle = User::factory()->create(['name' => 'موظف بلا مستحقات', 'base_salary' => 0, 'department' => 'الإدارة']);
        $this->post(route('payroll.store', $idle), [
            'month' => '2026-09', 'base_salary' => 0, 'installment_count' => 0, 'installment_commission' => 0,
            'installed_device_count' => 0, 'installation_commission' => 0, 'marketed_device_count' => 0, 'marketing_commission' => 0,
            'maintenance_count' => 0, 'maintenance_commission' => 0, 'regular_deduction' => 0, 'attendance_deduction' => 0,
            'bonus' => 0, 'absence_days' => 0,
        ])->assertSessionHasNoErrors();
        $idleRow = $this->get(route('payroll.report', ['from' => '2026-09-01', 'to' => '2026-09-30']))
            ->viewData('rows')->firstWhere(fn ($row) => $row['user']->is($idle));
        $this->assertSame('none', $idleRow['status']['key']);
        $this->assertSame('لا مستحقات', $idleRow['status']['label']);

        // فترة شهرين: سبتمبر المعتمد + أغسطس المحسوب تلقائيًا من المرتب الثابت
        $twoMonths = $this->get(route('payroll.report', ['from' => '2026-08-01', 'to' => '2026-09-30']))->assertOk();
        $row = $twoMonths->viewData('rows')->firstWhere(fn ($row) => $row['user']->is($approved));
        $this->assertSame(6300.0, $row['net']);
        $this->assertSame(2, $row['months']);
        $this->assertSame(1, $row['approved_months']);

        $onlyApproved = $this->get(route('payroll.report', ['from' => '2026-09-01', 'to' => '2026-09-30', 'approval' => 'approved']))->viewData('rows');
        $this->assertEqualsCanonicalizing(['موظف معتمد', 'موظف بلا مستحقات'], $onlyApproved->pluck('user.name')->all());

        $partial = $this->get(route('payroll.report', ['from' => '2026-09-01', 'to' => '2026-09-30', 'payment' => 'partial']))->viewData('rows');
        $this->assertSame(['موظف معتمد'], $partial->pluck('user.name')->all());

        $byDepartment = $this->get(route('payroll.report', ['from' => '2026-09-01', 'to' => '2026-09-30', 'department' => 'السكرتارية']))->viewData('rows');
        $this->assertSame(['موظف غير معتمد'], $byDepartment->pluck('user.name')->all());

        $this->get(route('payroll.index', ['month' => '2026-09']))
            ->assertOk()->assertSee(e(route('payroll.report', ['from' => '2026-09-01', 'to' => '2026-09-30'])), false);
    }
}
