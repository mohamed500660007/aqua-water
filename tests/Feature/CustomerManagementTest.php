<?php

namespace Tests\Feature;

use App\Domain\Customers\Enums\CustomerStatus;
use App\Domain\Customers\Models\Customer;
use App\Domain\Inventory\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'أحمد سيد عبد الرحمن',
            'national_id' => '29001011234567',
            'governorate' => 'الدقهلية',
            'center' => 'المنصورة',
            'village' => 'ميت مزاح',
            'status' => CustomerStatus::Active->value,
            'notes' => 'عميل من إحالة مندوب المبيعات',
            'registered_at' => '2026-01-15',
            'phones' => ['01012345678', '01234567890'],
            'primary_phone' => 1,
        ], $overrides);
    }

    /** انحدار: كود يدوي غير رقمي (CUS-TEST9) كان يُقرأ «آخر كود» فيعود الترقيم لـ CUS-00001 المكرر */
    public function test_new_customer_code_skips_non_numeric_codes(): void
    {
        Customer::factory()->create(['code' => 'CUS-00014']);
        Customer::factory()->create(['code' => 'CUS-TEST9'])->delete();

        $this->post(route('customers.store'), $this->validPayload())->assertSessionHasNoErrors();

        $this->assertDatabaseHas('customers', ['code' => 'CUS-00015']);
    }

    public function test_index_lists_customers(): void
    {
        $customer = Customer::factory()->create(['name' => 'منى خالد إبراهيم']);

        $this->get(route('customers.index'))
            ->assertOk()
            ->assertSee($customer->name)
            ->assertSee($customer->code);
    }

    public function test_index_paginates_results(): void
    {
        Customer::factory()->count(15)->create();

        $response = $this->get(route('customers.index'));

        $this->assertCount(12, $response->viewData('customers')->items());
        $this->assertSame(15, $response->viewData('customers')->total());
    }

    public function test_index_searches_by_code_name_national_id_and_any_phone(): void
    {
        $target = Customer::factory()
            ->withPhones(['01012345678', '01298765432'])
            ->create(['code' => 'CUS-00777', 'name' => 'ياسر فتحي', 'national_id' => '29505055554444']);

        Customer::factory()->withPhones(['01100000000'])->create(['name' => 'شخص آخر']);

        foreach (['CUS-00777', 'ياسر', '29505055554444', '01012345678', '1298765432'] as $term) {
            $results = $this->get(route('customers.index', ['q' => $term]))->viewData('customers');

            $this->assertCount(1, $results->items(), "فشل البحث بـ: {$term}");
            $this->assertTrue($results->first()->is($target));
        }
    }

    public function test_index_filters_by_status_and_location(): void
    {
        $target = Customer::factory()->create([
            'status' => CustomerStatus::Active,
            'governorate' => 'الدقهلية',
            'center' => 'طلخا',
            'village' => 'البقاشين',
        ]);

        Customer::factory()->create([
            'status' => CustomerStatus::Inactive,
            'governorate' => 'الدقهلية',
            'center' => 'طلخا',
            'village' => 'البقاشين',
        ]);

        $results = $this->get(route('customers.index', [
            'status' => CustomerStatus::Active->value,
            'governorate' => 'الدقهلية',
            'center' => 'طلخا',
            'village' => 'البقاشين',
        ]))->viewData('customers');

        $this->assertCount(1, $results->items());
        $this->assertTrue($results->first()->is($target));
    }

    public function test_index_sorts_by_whitelisted_column_only(): void
    {
        Customer::factory()->create(['code' => 'CUS-00002', 'name' => 'باسم']);
        Customer::factory()->create(['code' => 'CUS-00001', 'name' => 'أحمد']);

        $sorted = $this->get(route('customers.index', ['sort' => 'code', 'dir' => 'asc']))->viewData('customers');
        $this->assertSame(['CUS-00001', 'CUS-00002'], $sorted->pluck('code')->all());

        $ignored = $this->get(route('customers.index', ['sort' => 'notes; drop table customers', 'dir' => 'asc']));
        $ignored->assertOk();
    }

    public function test_index_does_not_run_a_query_per_customer(): void
    {
        Customer::factory()->count(12)->withPhones(['01012345678', '01298765432'])->create();

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->get(route('customers.index'))->assertOk();

        // قائمة + عدّاد + إحصائيات + قوائم الفلاتر — لا استعلام لكل عميل
        $this->assertLessThanOrEqual(8, $queries);
    }

    public function test_it_creates_a_customer_with_multiple_phones(): void
    {
        $response = $this->post(route('customers.store'), $this->validPayload());

        $customer = Customer::firstWhere('name', 'أحمد سيد عبد الرحمن');

        $response->assertRedirect(route('customers.show', $customer));

        $this->assertSame('ميت مزاح', $customer->village);
        $this->assertSame(CustomerStatus::Active, $customer->status);
        $this->assertSame('2026-01-15', $customer->registered_at->format('Y-m-d'));
        $this->assertSame(['01012345678', '01234567890'], $customer->phones()->pluck('phone')->sort()->values()->all());
        $this->assertSame('01234567890', $customer->primaryPhone->phone);
    }

    public function test_it_always_generates_the_code_and_ignores_a_submitted_one(): void
    {
        Customer::factory()->create(['code' => 'CUS-00008']);

        $this->post(route('customers.store'), $this->validPayload(['code' => 'HACKED', 'national_id' => null]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('customers', ['code' => 'CUS-00009']);
        $this->assertDatabaseMissing('customers', ['code' => 'HACKED']);
    }

    public function test_form_has_no_code_field(): void
    {
        $this->get(route('customers.create'))->assertOk()->assertDontSee('name="code"', false);
    }

    public function test_it_accepts_the_returned_and_legal_statuses(): void
    {
        foreach ([CustomerStatus::Returned, CustomerStatus::Legal] as $index => $status) {
            $this->post(route('customers.store'), $this->validPayload([
                'national_id' => null,
                'phones' => ['0101234567'.$index],
                'status' => $status->value,
            ]))->assertSessionHasNoErrors();
        }

        $this->assertSame(1, Customer::where('status', CustomerStatus::Returned)->count());
        $this->assertSame(1, Customer::where('status', CustomerStatus::Legal)->count());
        $this->get(route('customers.index'))->assertOk()->assertSee('مرتجع')->assertSee('شؤون قانونية');
    }

    public function test_it_normalizes_phone_numbers_before_saving(): void
    {
        $this->post(route('customers.store'), $this->validPayload([
            'phones' => ['+20 101 234 5678', '٠١٢٣٤-٥٦٧-٨٩٠'],
        ]));

        $this->assertDatabaseHas('customer_phones', ['phone' => '01012345678']);
        $this->assertDatabaseHas('customer_phones', ['phone' => '01234567890']);
    }

    public function test_it_rejects_the_same_phone_written_in_different_formats(): void
    {
        $this->post(route('customers.store'), $this->validPayload([
            'phones' => ['01012345678', '+201012345678'],
        ]))->assertSessionHasErrors('phones.1');

        $this->assertDatabaseCount('customers', 0);
    }

    public function test_it_validates_required_and_unique_fields(): void
    {
        Customer::factory()->create(['code' => 'CUS-01000', 'national_id' => '29001011234567']);

        $this->post(route('customers.store'), $this->validPayload([
            'name' => '',
            'phones' => [],
            'national_id' => '123',
            'registered_at' => now()->addDay()->toDateString(),
        ]))->assertSessionHasErrors(['name', 'phones', 'national_id', 'registered_at']);

        $this->post(route('customers.store'), $this->validPayload())
            ->assertSessionHasErrors('national_id');

        $this->post(route('customers.store'), $this->validPayload(['phones' => ['12345']]))
            ->assertSessionHasErrors('phones.0');

        $this->assertDatabaseCount('customers', 1);
    }

    public function test_validation_errors_are_rendered_on_the_form(): void
    {
        $this->get(route('customers.create'));

        $this->followingRedirects()
            ->post(route('customers.store'), $this->validPayload(['name' => '', 'phones' => ['12345']]))
            ->assertOk()
            ->assertSee('حقل اسم العميل مطلوب.')
            ->assertSee('رقم الهاتف غير صحيح — اكتبه بالصيغة المحلية مثل 01012345678.');
    }

    public function test_optional_fields_may_be_left_empty(): void
    {
        $this->post(route('customers.store'), $this->validPayload([
            'national_id' => null,
            'governorate' => null,
            'center' => null,
            'village' => null,
            'notes' => null,
            'registered_at' => null,
            'phones' => ['01012345678'],
        ]))->assertSessionHasNoErrors();

        $customer = Customer::sole();

        $this->assertNull($customer->national_id);
        $this->assertSame(today()->toDateString(), $customer->registered_at->format('Y-m-d'));
        $this->assertTrue($customer->primaryPhone->is_primary);
    }

    public function test_primary_phone_falls_back_to_the_first_number(): void
    {
        $this->post(route('customers.store'), $this->validPayload(['primary_phone' => 9]));

        $this->assertSame('01012345678', Customer::sole()->primaryPhone->phone);
    }

    public function test_it_shows_customer_details_with_all_phones(): void
    {
        $customer = Customer::factory()->withPhones(['01012345678', '01298765432'])->create();

        $this->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee($customer->name)
            ->assertSee('01012345678')
            ->assertSee('01298765432');
    }

    public function test_create_and_edit_pages_render(): void
    {
        $customer = Customer::factory()->create();

        $this->get(route('customers.create'))->assertOk()->assertSee('عميل جديد');
        $this->get(route('customers.edit', $customer))->assertOk()->assertSee($customer->name);
    }

    public function test_it_updates_a_customer_and_syncs_phones(): void
    {
        $customer = Customer::factory()->withPhones(['01012345678', '01298765432'])->create();
        $keptPhoneId = $customer->phones()->where('phone', '01298765432')->value('id');

        $originalCode = $customer->code;

        $this->put(route('customers.update', $customer), $this->validPayload([
            'code' => 'CUS-99999',
            'name' => 'اسم بعد التعديل',
            'national_id' => null,
            'phones' => ['01298765432', '01100000000'],
            'primary_phone' => 1,
        ]))->assertRedirect(route('customers.show', $customer));

        $customer->refresh()->load('phones');

        $this->assertSame('اسم بعد التعديل', $customer->name);
        $this->assertSame($originalCode, $customer->code);
        $this->assertSame(['01100000000', '01298765432'], $customer->phones->pluck('phone')->sort()->values()->all());
        $this->assertSame('01100000000', $customer->primaryPhone->phone);
        $this->assertDatabaseHas('customer_phones', ['id' => $keptPhoneId, 'is_primary' => false]);
        $this->assertDatabaseMissing('customer_phones', ['phone' => '01012345678']);
    }

    public function test_update_ignores_the_current_customer_in_unique_rules(): void
    {
        $customer = Customer::factory()->create(['code' => 'CUS-01000', 'national_id' => '29001011234567']);

        $this->put(route('customers.update', $customer), $this->validPayload())
            ->assertSessionHasNoErrors();
    }

    public function test_customers_cannot_be_deleted(): void
    {
        $customer = Customer::factory()->create();

        $this->assertFalse(Route::has('customers.destroy'));
        $this->delete('/dashboard/customers/'.$customer->id)->assertStatus(405);
        $this->assertNotSoftDeleted($customer);

        foreach ([route('customers.index'), route('customers.show', $customer)] as $url) {
            $this->get($url)->assertOk()->assertDontSee('name="_method" value="DELETE"', false);
        }
    }

    public function test_report_renders_a_printable_document_for_the_filtered_customers(): void
    {
        $legal = Customer::factory()->withPhones(['01012345678'])->create([
            'name' => 'عميل قانوني', 'status' => CustomerStatus::Legal, 'registered_at' => '2026-10-05',
        ]);
        Customer::factory()->create(['name' => 'عميل نشط', 'status' => CustomerStatus::Active]);

        $all = $this->get(route('customers.report', ['from' => '2026-10-01', 'to' => '2026-10-31']))->assertOk();
        $this->assertSame(2, $all->viewData('summary')['total']);
        $this->assertSame(1, $all->viewData('summary')['statuses']['legal']);
        $all->assertSee('window.print()', false)->assertSee('صفحة');

        $filtered = $this->get(route('customers.report', ['status' => 'legal', 'from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertOk()
            ->assertSee('عميل قانوني')
            ->assertDontSee('عميل نشط');

        $this->assertSame(1, $filtered->viewData('summary')['new']);
        $this->assertTrue($filtered->viewData('rows')->sole()->is($legal));
    }

    public function test_report_does_not_run_a_query_per_customer(): void
    {
        Customer::factory()->count(20)->withPhones(['01012345678'])->create();

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->get(route('customers.report'))->assertOk();

        // أقسام التقرير + قوائم الفلاتر (مراكز، قرى، محافظات) — ثابت مهما زاد العملاء
        $this->assertLessThanOrEqual(12, $queries);
    }

    public function test_view_action_opens_the_details_panel_instead_of_navigating(): void
    {
        $customer = Customer::factory()->create();

        $this->get(route('customers.index'))
            ->assertOk()
            ->assertSee('$store.drawer.show', false)
            ->assertSee(str_replace('/', '\/', route('customers.panel', $customer)), false);
    }

    public function test_panel_shows_every_section_of_the_customer_profile(): void
    {
        $customer = Customer::factory()->withPhones(['01012345678', '01298765432'])->create([
            'name' => 'عميل اللوحة', 'notes' => 'ملاحظة مهمة', 'detailed_address' => 'بجوار المسجد',
        ]);

        $this->get(route('customers.panel', $customer))
            ->assertOk()
            ->assertDontSee('<html', false)
            ->assertSee('عميل اللوحة')
            ->assertSee($customer->code)
            ->assertSee('01298765432')
            ->assertSee('بجوار المسجد')
            ->assertSee('ملاحظة مهمة')
            ->assertSeeInOrder(['نظرة عامة', 'العقود', 'الأقساط', 'الصيانة', 'الشكاوى', 'الهدايا']);
    }

    public function test_panel_query_count_does_not_grow_with_the_customer_history(): void
    {
        $customer = Customer::factory()->withPhones(['01012345678'])->create();

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->get(route('customers.panel', $customer))->assertOk();

        // ثابت مهما زادت العقود والأقساط: علاقة واحدة = استعلام واحد
        $this->assertLessThanOrEqual(20, $queries);
    }

    public function test_report_toolbar_offers_the_filters_and_applies_them(): void
    {
        $due = Customer::factory()->create(['name' => 'عميل عليه فلوس', 'governorate' => 'الدقهلية']);
        Customer::factory()->create(['name' => 'عميل خالص', 'governorate' => 'الدقهلية']);
        $due->contracts()->create([
            'code' => 'AQW-DUE', 'kind' => 'sale', 'type' => 'installment', 'stock_source' => 'warehouse',
            'warehouse_id' => Warehouse::factory()->create()->id,
            'contract_date' => today(), 'subtotal' => 1000, 'discount' => 0, 'total' => 1000, 'paid' => 200, 'remaining' => 800,
        ]);

        $page = $this->get(route('customers.report'))->assertOk();
        foreach (['name="status"', 'name="origin"', 'name="governorate"', 'name="balance"', 'name="content"', 'name="sort"', 'name="dir"', 'name="q"'] as $field) {
            $page->assertSee($field, false);
        }

        $this->get(route('customers.report', ['balance' => 'due']))
            ->assertOk()->assertSee('عميل عليه فلوس')->assertDontSee('عميل خالص');

        $this->get(route('customers.report', ['balance' => 'clear', 'governorate' => 'الدقهلية']))
            ->assertOk()->assertSee('عميل خالص')->assertDontSee('عميل عليه فلوس');

        $sorted = $this->get(route('customers.report', ['sort' => 'remaining', 'dir' => 'desc']))->viewData('rows');
        $this->assertTrue($sorted->first()->is($due));

        $summaryOnly = $this->get(route('customers.report', ['content' => 'summary']))->assertOk();
        $this->assertCount(0, $summaryOnly->viewData('rows'));
        $summaryOnly->assertDontSee('rpt-section-title">كشف العملاء', false);

        $this->get(route('customers.report', ['content' => 'list']))
            ->assertOk()->assertSee('rpt-section-title">كشف العملاء', false)->assertDontSee('الملخص العام');
    }
}
