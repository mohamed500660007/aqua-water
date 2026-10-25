<?php

namespace Tests\Feature;

use App\Domain\Customers\Models\Customer;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Sales\Enums\ContractKind;
use App\Domain\Sales\Enums\ContractType;
use App\Domain\Sales\Enums\StockSource;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Settings\Settings;
use App\Support\Lookups;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * ملف الإعدادات: كل قسم يُحفظ ويُتحقق منه ويُستعاد مستقلًا، والقيم تظهر فورًا في كل مكان تُستخدم فيه.
 */
class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_settings_page_shows_every_section_with_its_defaults(): void
    {
        $page = $this->get(route('settings.index'))->assertOk();

        foreach (Settings::sections() as $section) {
            $page->assertSee($section['label']);
        }

        $page->assertSee('أكوا ووتر')
            ->assertSee('القيم الثابتة')->assertSee('فني تركيب')
            ->assertSee('الشمعة السابعة')
            ->assertSee('معلومات النظام')->assertSee(PHP_VERSION);

        $this->get(route('home'))->assertSee(route('settings.index'), false);
    }

    public function test_company_details_appear_across_the_dashboard_and_printouts(): void
    {
        $this->put(route('settings.update', 'company'), $this->company(['company_name' => 'النيل للفلاتر', 'company_phone' => '0222222222']))
            ->assertRedirect(route('settings.index', ['tab' => 'company']))
            ->assertSessionHas('success');

        $this->get(route('home'))->assertOk()->assertSee('النيل للفلاتر')->assertDontSee('أكوا ووتر');
        $this->get(route('reports.finance'))->assertOk()->assertSee('النيل للفلاتر')->assertSee('0222222222');

        $installment = $this->installment();
        $this->get(route('installments.receipt', $installment))->assertOk()->assertSee('النيل للفلاتر')->assertSee('0222222222');
    }

    public function test_receipt_terms_and_report_signatures_come_from_the_printing_settings(): void
    {
        $this->put(route('settings.update', 'printing'), [
            'receipt_terms' => 'غرامة تأخير 100 جنيه',
            'receipt_footnote' => 'احتفظ بالإيصال',
            'report_signatures' => "المحاسب\nالمدير",
        ])->assertSessionHasNoErrors();

        $this->get(route('installments.receipt', $this->installment()))->assertOk()
            ->assertSee('غرامة تأخير 100 جنيه')->assertSee('احتفظ بالإيصال');

        $this->get(route('reports.finance'))->assertOk()
            ->assertSee('المحاسب')->assertSee('المدير')->assertDontSee('اعتماد المدير');
    }

    public function test_lists_are_saved_one_item_per_line_and_feed_the_forms(): void
    {
        $this->put(route('settings.update', 'lists'), $this->lists([
            'departments' => "  المبيعات \n\nالصيانة\nالمبيعات\nالتسويق",
        ]))->assertSessionHasNoErrors();

        $this->assertSame(['المبيعات', 'الصيانة', 'التسويق'], Lookups::departments());
        $this->get(route('users.create'))->assertOk()->assertSee('التسويق');

        // القسم المحذوف لم يعد خيارًا مقبولًا لموظف جديد
        $this->post(route('users.store'), ['name' => 'موظف', 'department' => 'الأرشيف'])->assertSessionHasErrors('department');
    }

    public function test_contract_form_starts_from_the_sales_defaults(): void
    {
        $this->put(route('settings.update', 'sales'), [
            'default_installments_count' => 12,
            'default_installment_period' => 2,
            'first_due_after_months' => 3,
            'default_interest_rate' => 15.5,
        ])->assertSessionHasNoErrors();

        $this->assertSame(12, app(Settings::class)->get('default_installments_count'));
        $this->assertSame(15.5, app(Settings::class)->get('default_interest_rate'));

        // @js يرمّز علامات التنصيص داخل الـ HTML كـ "
        $this->get(route('contracts.create'))
            ->assertSee($this->jsonPair('installmentsCount', '12'), false)
            ->assertSee($this->jsonPair('periodMonths', '2'), false)
            ->assertSee($this->jsonPair('interestRate', '15.5'), false)
            ->assertSee(today()->addMonthsNoOverflow(3)->toDateString());
    }

    public function test_invalid_values_are_rejected_without_saving_the_section(): void
    {
        $this->put(route('settings.update', 'sales'), [
            'default_installments_count' => 0,
            'default_installment_period' => 13,
            'first_due_after_months' => 1,
            'default_interest_rate' => 'abc',
        ])->assertSessionHasErrors(['default_installments_count', 'default_installment_period', 'default_interest_rate']);

        $this->put(route('settings.update', 'company'), $this->company(['company_name' => '', 'company_email' => 'not-an-email']))
            ->assertSessionHasErrors(['company_name', 'company_email']);

        $this->put(route('settings.update', 'lists'), $this->lists(['governorates' => "\n  \n"]))
            ->assertSessionHasErrors('governorates');

        $this->assertDatabaseCount('settings', 0);
        $this->get(route('settings.index'))->assertOk();
        $this->put(route('settings.update', 'unknown'), [])->assertNotFound();
    }

    public function test_a_section_can_be_reset_to_its_defaults(): void
    {
        $this->put(route('settings.update', 'company'), $this->company(['company_name' => 'اسم مؤقت']));
        $this->put(route('settings.update', 'sales'), ['default_installments_count' => 9, 'default_installment_period' => 1, 'first_due_after_months' => 1, 'default_interest_rate' => 0]);

        $this->delete(route('settings.reset', 'company'))->assertSessionHas('success');

        $settings = app(Settings::class);
        $this->assertSame('أكوا ووتر', $settings->get('company_name'));
        $this->assertSame(9, $settings->get('default_installments_count'));
    }

    public function test_settings_are_read_with_a_single_query_per_request(): void
    {
        $this->put(route('settings.update', 'company'), $this->company(['company_name' => 'النيل للفلاتر']));
        app()->forgetInstance(Settings::class);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('reports.index'))->assertOk()->assertSee('النيل للفلاتر');
        $settingsQueries = collect(DB::getQueryLog())->filter(fn (array $query) => str_contains($query['query'], '"settings"') || str_contains($query['query'], '`settings`'));

        $this->assertCount(1, $settingsQueries);
    }

    /** المفتاح والقيمة كما يكتبهما @js داخل الـ HTML (علامات التنصيص مرمّزة) */
    private function jsonPair(string $key, string $value): string
    {
        // chr(92) = «\» — @js يكتب علامة التنصيص كـ «\»+«u0022»
        return $key.chr(92).'u0022:'.$value;
    }

    private function company(array $overrides = []): array
    {
        return array_merge(app(Settings::class)->section('company'), $overrides);
    }

    private function lists(array $overrides = []): array
    {
        $lists = collect(app(Settings::class)->section('lists'))->map(fn (array $items) => implode("\n", $items))->all();

        return array_merge($lists, $overrides);
    }

    private function installment()
    {
        $contract = SalesContract::query()->create([
            'code' => 'AQW-'.fake()->unique()->numerify('####'), 'kind' => ContractKind::Sale, 'type' => ContractType::Installment,
            'customer_id' => Customer::factory()->create()->id, 'warehouse_id' => Warehouse::factory()->create()->id,
            'stock_source' => StockSource::Warehouse, 'contract_date' => today(), 'subtotal' => 1000, 'discount' => 0,
            'total' => 1000, 'paid' => 500, 'remaining' => 500, 'installments_count' => 1,
        ]);

        return $contract->installments()->create([
            'number' => 1, 'due_date' => today(), 'amount' => 500, 'paid_at' => today(), 'paid_amount' => 500,
        ]);
    }
}
