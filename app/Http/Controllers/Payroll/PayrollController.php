<?php

namespace App\Http\Controllers\Payroll;

use App\Domain\Payroll\Models\Payroll;
use App\Domain\Payroll\Models\PayrollPayment;
use App\Domain\Payroll\Services\PayrollReport;
use App\Domain\Payroll\Services\PayrollService;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\PayrollPaymentRequest;
use App\Http\Requests\Payroll\PayrollReportRequest;
use App\Http\Requests\Payroll\PayrollRequest;
use App\Support\Lookups;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PayrollController extends Controller
{
    public function __construct(private readonly PayrollService $payrolls) {}

    public function index(Request $request): View
    {
        $month = $this->month($request->input('month'));
        $employees = User::query()
            ->search($request->input('q'))
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->input('role')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('pages.payroll.index', [
            'month' => $month,
            'employees' => $employees,
            'rows' => $this->payrolls->monthlyRows($month, $employees->getCollection()),
            'summary' => $this->paymentSummary($month),
            'roles' => Lookups::roles(),
        ]);
    }

    public function report(PayrollReportRequest $request, PayrollReport $report): View
    {
        $period = $request->period();
        $filters = $request->filters();
        $users = User::query()
            ->search($request->validated('q'))
            ->applyFilters(array_intersect_key($filters, array_flip(PayrollReportRequest::FILTERS)))
            ->orderBy('name')
            ->get();

        $rows = $report->filterAndSort(
            $report->rows($users, $period),
            $filters,
            $request->validated('sort'),
            $request->validated('dir'),
        );

        return view('pages.payroll.report', [
            'period' => $period,
            'content' => $request->content(),
            'rows' => $rows,
            'totals' => $report->totals($rows),
            'byDepartment' => $report->byDepartment($rows),
            'search' => $request->validated('q'),
            'filters' => $filters,
            'backQuery' => $request->backQuery(),
            'contents' => PayrollReportRequest::CONTENTS,
            'sorts' => PayrollReportRequest::SORTS,
            'approvals' => PayrollReportRequest::APPROVALS,
            'payments' => PayrollReport::PAYMENTS,
            'roles' => Lookups::roles(),
            'departments' => Lookups::departments(),
            'branches' => Lookups::branches(),
        ]);
    }

    public function store(PayrollRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $month = $this->month($validated['month']);
        unset($validated['month']);

        try {
            $this->payrolls->save($user, $month, $validated);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('payroll.index', ['month' => $month->format('Y-m')])
            ->with('success', "تم حفظ مرتب «{$user->name}» بنجاح.");
    }

    /** قسيمة المرتب المطبوعة — تُصدر فقط من كشف محفوظ حتى يطابق ما يوقّع عليه الموظف ما سيُصرف له */
    public function payslip(Payroll $payroll): View
    {
        return view('pages.payroll.payslip', [
            'payroll' => $payroll->load(['user', 'payments']),
        ]);
    }

    public function storePayment(PayrollPaymentRequest $request, Payroll $payroll): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $this->payrolls->recordPayment($payroll, (float) $validated['amount'], $validated['paid_at'], $validated['note']);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'تم تسجيل دفعة الصرف بنجاح.');
    }

    public function destroyPayment(PayrollPayment $payment): RedirectResponse
    {
        $this->payrolls->deletePayment($payment);

        return back()->with('success', 'تم حذف دفعة الصرف.');
    }

    /**
     * متابعة صرف مرتبات الشهر لكل الموظفين (لا الصفحة الحالية فقط): كم اعتُمد، وكم صُرف، ومن قبض.
     *
     * @return array<string, float|int>
     */
    private function paymentSummary(CarbonImmutable $month): array
    {
        $payrolls = Payroll::query()
            ->whereDate('month', $month->startOfMonth())
            ->withSum('payments as paid', 'amount')
            ->get(['id', 'total']);

        $due = (float) $payrolls->sum('total');
        $paid = (float) $payrolls->sum('paid');

        return [
            'approved' => $payrolls->count(),
            'due' => $due,
            'paid' => $paid,
            'remaining' => max(0, round($due - $paid, 2)),
            'fully_paid' => $payrolls->filter(fn (Payroll $payroll) => (float) $payroll->paid >= (float) $payroll->total - 0.01)->count(),
        ];
    }

    private function month(?string $value): CarbonImmutable
    {
        try {
            return CarbonImmutable::createFromFormat('!Y-m', $value ?: now()->format('Y-m'))->startOfMonth();
        } catch (\Throwable) {
            return CarbonImmutable::now()->startOfMonth();
        }
    }
}
