<?php

namespace App\Http\Controllers\Customers;

use App\Domain\Customers\Enums\CustomerOrigin;
use App\Domain\Customers\Enums\CustomerStatus;
use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Services\CustomerReport;
use App\Domain\Customers\Services\CustomerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customers\CustomerReportRequest;
use App\Http\Requests\Customers\CustomerRequest;
use App\Support\Lookups;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    private const PER_PAGE = 12;

    public function __construct(private readonly CustomerService $customers) {}

    public function index(Request $request): View
    {
        $customers = Customer::query()
            ->with('primaryPhone')
            ->withCount('phones')
            ->search($request->input('q'))
            ->applyFilters($request->only(['status', 'origin', 'governorate', 'center', 'village']))
            ->applySort($request->input('sort'), $request->input('dir'))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.customers.index', [
            'customers' => $customers,
            'stats' => $this->stats(),
        ] + $this->lookups());
    }

    public function create(): View
    {
        return view('pages.customers.create', $this->lookups());
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        $customer = $this->customers->create($request->toData());

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', "تمت إضافة العميل «{$customer->name}» بنجاح.");
    }

    public function show(Customer $customer): View
    {
        return view('pages.customers.show', [
            'customer' => $customer->load('phones'),
        ]);
    }

    /** لوحة جانبية بكل تفاصيل العميل — تُطلب عند الفتح فقط حتى لا تُحمَّل مع كل صف في القائمة */
    public function panel(Customer $customer): View
    {
        return view('pages.customers.panel', ['customer' => $customer->loadProfile()]);
    }

    public function edit(Customer $customer): View
    {
        return view('pages.customers.edit', [
            'customer' => $customer->load('phones'),
        ] + $this->lookups());
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->customers->update($customer, $request->toData());

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'تم حفظ تعديلات العميل بنجاح.');
    }

    public function report(CustomerReportRequest $request, CustomerReport $report): View
    {
        $period = $request->period();
        $content = $request->content();
        $customers = Customer::query()
            ->search($request->validated('q'))
            ->applyFilters($request->filters());

        // الأجزاء غير المطلوبة للطباعة لا تُحسب أصلًا
        $withSummary = $content !== 'list';
        $rows = $content !== 'summary'
            ? $report->rows($customers, $period, $request->validated('sort'), $request->validated('dir'))
            : collect();

        return view('pages.customers.report', [
            'period' => $period,
            'content' => $content,
            'summary' => $report->summary($customers, $period),
            'activity' => $withSummary ? $report->activity($customers, $period) : null,
            'governorates' => $withSummary ? $report->byGovernorate($customers) : collect(),
            'rows' => $rows->take(CustomerReport::MAX_ROWS),
            'truncated' => $rows->count() > CustomerReport::MAX_ROWS,
            'search' => $request->validated('q'),
            'filters' => $request->filters(),
            'listQuery' => $request->listQuery(),
            'contents' => CustomerReportRequest::CONTENTS,
            'sorts' => CustomerReportRequest::SORTS,
            // «governorates» في التقرير هو توزيع العملاء، فقائمة المحافظات للفلتر باسم مستقل
            'governoratesList' => collect(Lookups::governorates())->merge(Customer::usedValuesOf('governorate'))->unique()->values()->all(),
        ] + $this->lookups());
    }

    /** أعداد العملاء حسب الحالة في استعلام تجميعي واحد */
    private function stats(): array
    {
        $counts = Customer::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return ['total' => (int) $counts->sum()] + collect(CustomerStatus::cases())
            ->mapWithKeys(fn (CustomerStatus $status) => [$status->value => (int) $counts->get($status->value, 0)])
            ->all();
    }

    /** القوائم التي تحتاجها الواجهة للفلترة وللنموذج */
    private function lookups(): array
    {
        return [
            'statuses' => CustomerStatus::options(),
            'origins' => CustomerOrigin::options(),
            'defaultStatus' => CustomerStatus::Active->value,
            'governorates' => Lookups::governorates(),
            'centers' => Customer::usedValuesOf('center'),
            'villages' => Customer::usedValuesOf('village'),
        ];
    }
}
