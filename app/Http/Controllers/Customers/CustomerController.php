<?php

namespace App\Http\Controllers\Customers;

use App\Domain\Customers\Enums\CustomerStatus;
use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Services\CustomerService;
use App\Http\Controllers\Controller;
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
            ->applyFilters($request->only(['status', 'governorate', 'center', 'village']))
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

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->customers->delete($customer);

        return redirect()
            ->route('customers.index')
            ->with('success', "تم حذف العميل «{$customer->name}».");
    }

    /** أعداد العملاء حسب الحالة في استعلام تجميعي واحد */
    private function stats(): array
    {
        $counts = Customer::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'total' => (int) $counts->sum(),
            'active' => (int) $counts->get(CustomerStatus::Active->value, 0),
            'inactive' => (int) $counts->get(CustomerStatus::Inactive->value, 0),
        ];
    }

    /** القوائم التي تحتاجها الواجهة للفلترة وللنموذج */
    private function lookups(): array
    {
        return [
            'statuses' => CustomerStatus::options(),
            'defaultStatus' => CustomerStatus::Active->value,
            'governorates' => Lookups::governorates(),
            'centers' => Customer::usedValuesOf('center'),
            'villages' => Customer::usedValuesOf('village'),
        ];
    }
}
