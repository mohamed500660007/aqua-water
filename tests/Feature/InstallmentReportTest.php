<?php

namespace Tests\Feature;

use App\Domain\Customers\Models\Customer;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Sales\Enums\ContractKind;
use App\Domain\Sales\Enums\ContractType;
use App\Domain\Sales\Enums\StockSource;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * تقرير الأقساط: التحصيل حسب المنطقة والمحصّل، والمتأخرون، وأول قسط مع المقدم، ومن أنهوا أقساطهم.
 */
class InstallmentReportTest extends TestCase
{
    use RefreshDatabase;

    private const OCTOBER = ['from' => '2026-10-01', 'to' => '2026-10-31'];

    private Warehouse $warehouse;

    private User $ahmed;

    private User $mahmoud;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-15');

        $this->warehouse = Warehouse::factory()->create();
        $this->ahmed = User::factory()->create(['name' => 'أحمد المحصل', 'role' => 'محصل']);
        $this->mahmoud = User::factory()->create(['name' => 'محمود الفني', 'role' => 'فني تركيب']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_reports_collections_by_area_and_collector(): void
    {
        $this->scenario();

        $page = $this->get(route('installments.report', self::OCTOBER))
            ->assertOk()
            ->assertSee('تقرير الأقساط')
            ->assertSee('القسط الأول')
            ->assertSee('أحمد المحصل');

        $summary = $page->viewData('summary');
        $this->assertSame(1000.0, $summary['collected_amount']);
        $this->assertSame(2, $summary['collected_customers']);
        // مستحق أكتوبر: قسط العميل الجديد (محصّل) + القسط الثالث للمتأخر + قسط القانونية + القسط الأخير للمنتهي
        $this->assertSame(2000.0, $summary['due_amount']);
        $this->assertSame(1000.0, $summary['due_paid']);

        $areas = $page->viewData('areas');
        $this->assertCount(1, $areas);
        $this->assertSame('المنصورة', $areas[0]['area']);
        $this->assertSame(2, $areas[0]['customers']);
        $this->assertSame(['أحمد المحصل', 'محمود الفني'], $areas[0]['collectors']->pluck('name')->sort()->values()->all());
        $this->assertCount(2, $page->viewData('collections'));
    }

    public function test_overdue_lists_each_late_installment_and_excludes_legal_contracts(): void
    {
        $this->scenario();

        $page = $this->get(route('installments.report', self::OCTOBER))->assertOk();

        $overdue = $page->viewData('overdue');
        $this->assertCount(1, $overdue);
        $this->assertSame('عميل متأخر', $overdue[0]['contract']->customer->name);
        $this->assertSame([2, 3], $overdue[0]['installments']->pluck('number')->all());
        $this->assertSame(1000.0, $overdue[0]['amount']);
        $this->assertSame('2026-09-05', $overdue[0]['oldest']->toDateString());
        $this->assertSame(1000.0, $page->viewData('summary')['overdue_amount']);
        $page->assertSee('القسط الثالث')->assertDontSee('عميل قانونية');
    }

    public function test_it_lists_first_payments_with_down_payment_and_finished_contracts(): void
    {
        $this->scenario();

        $page = $this->get(route('installments.report', self::OCTOBER))->assertOk();

        $first = $page->viewData('firstPayments');
        $this->assertCount(1, $first);
        $this->assertSame('عميل جديد', $first[0]->contract->customer->name);
        $this->assertSame(2000.0, $first[0]->down_payment);

        $finished = $page->viewData('finished');
        $this->assertCount(1, $finished);
        $this->assertSame('عميل منتهي', $finished[0]['contract']->customer->name);
        $this->assertSame('2026-10-10', $finished[0]['last_paid_at']->toDateString());
    }

    public function test_area_collector_and_content_filters_narrow_the_report(): void
    {
        $this->scenario();

        $talkha = $this->get(route('installments.report', self::OCTOBER + ['center' => 'طلخا']))->assertOk();
        $this->assertSame(0.0, $talkha->viewData('summary')['collected_amount']);
        $this->assertCount(1, $talkha->viewData('overdue'));

        $byCollector = $this->get(route('installments.report', self::OCTOBER + ['collected_by_id' => $this->mahmoud->id]))->assertOk();
        $this->assertSame(['عميل منتهي'], $byCollector->viewData('collections')->map(fn ($row) => $row->contract->customer->name)->all());

        $byGovernorate = $this->get(route('installments.report', self::OCTOBER + ['area_level' => 'governorate']))->assertOk();
        $this->assertSame('الدقهلية', $byGovernorate->viewData('areas')[0]['area']);

        $overdueOnly = $this->get(route('installments.report', self::OCTOBER + ['content' => 'overdue']))->assertOk();
        $this->assertTrue($overdueOnly->viewData('collections')->isEmpty());
        $this->assertTrue($overdueOnly->viewData('finished')->isEmpty());
        $this->assertCount(1, $overdueOnly->viewData('overdue'));

        // انتهاء الفترة قبل اليوم يحسب المتأخرات حتى نهايتها فقط
        $september = $this->get(route('installments.report', ['from' => '2026-09-01', 'to' => '2026-09-30']))->assertOk();
        $this->assertSame([2], $september->viewData('overdue')[0]['installments']->pluck('number')->all());
    }

    public function test_report_query_count_does_not_grow_with_installments(): void
    {
        $addContracts = fn (int $count) => collect(range(1, $count))->each(fn (int $index) => $this->contract("عميل {$index}", 'المنصورة', [
            ['due' => '2026-09-05', 'paid' => '2026-10-02', 'by' => $this->ahmed],
            ['due' => '2026-10-01'],
        ]));

        $addContracts(1);
        $few = $this->countQueries(fn () => $this->get(route('installments.report', self::OCTOBER))->assertOk());

        $addContracts(15);
        $many = $this->countQueries(fn () => $this->get(route('installments.report', self::OCTOBER))->assertOk());

        $this->assertSame($few, $many);
    }

    public function test_installments_page_links_to_the_report(): void
    {
        $this->get(route('installments.index'))->assertOk()->assertSee(route('installments.report'));
    }

    private function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $callback();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    }

    private function scenario(): void
    {
        $this->contract('عميل جديد', 'المنصورة', [
            ['due' => '2026-10-05', 'paid' => '2026-10-06', 'by' => $this->ahmed],
            ['due' => '2026-11-05'],
        ], total: 3000);

        $this->contract('عميل متأخر', 'طلخا', [
            ['due' => '2026-08-05', 'paid' => '2026-08-05', 'by' => $this->ahmed],
            ['due' => '2026-09-05'],
            ['due' => '2026-10-05'],
        ]);

        $this->contract('عميل منتهي', 'المنصورة', [
            ['due' => '2026-09-05', 'paid' => '2026-09-05', 'by' => $this->ahmed],
            ['due' => '2026-10-05', 'paid' => '2026-10-10', 'by' => $this->mahmoud],
        ]);

        $this->contract('عميل قانونية', 'طلخا', [['due' => '2026-10-01']], legal: true);
    }

    /** @param  list<array{due: string, paid?: string, by?: User}>  $installments */
    private function contract(string $customerName, string $center, array $installments, float $total = 1500, bool $legal = false): SalesContract
    {
        $customer = Customer::factory()->create(['name' => $customerName, 'governorate' => 'الدقهلية', 'center' => $center]);
        $paid = $total - 500 * count($installments) + 500 * collect($installments)->whereNotNull('paid')->count();

        $contract = SalesContract::query()->create([
            'code' => 'AQW-'.fake()->unique()->numerify('####'),
            'kind' => ContractKind::Sale, 'type' => ContractType::Installment,
            'customer_id' => $customer->id, 'warehouse_id' => $this->warehouse->id,
            'stock_source' => StockSource::Warehouse, 'contract_date' => '2026-08-01',
            'subtotal' => $total, 'discount' => 0, 'total' => $total, 'paid' => $paid, 'remaining' => $total - $paid,
            'installments_count' => count($installments), 'installment_amount' => 500,
            'legal_referred_at' => $legal ? '2026-10-01' : null,
        ]);

        foreach ($installments as $index => $installment) {
            $contract->installments()->create([
                'number' => $index + 1,
                'due_date' => $installment['due'],
                'amount' => 500,
                'paid_at' => $installment['paid'] ?? null,
                'paid_amount' => isset($installment['paid']) ? 500 : null,
                'collected_by_id' => ($installment['by'] ?? null)?->id,
            ]);
        }

        return $contract;
    }
}
