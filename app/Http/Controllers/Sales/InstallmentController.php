<?php

namespace App\Http\Controllers\Sales;

use App\Domain\Customers\Models\Customer;
use App\Domain\Sales\Models\SalesContractInstallment;
use App\Domain\Sales\Services\InstallmentReport;
use App\Domain\Sales\Services\SalesContractService;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\CollectInstallmentRequest;
use App\Http\Requests\Sales\InstallmentReportRequest;
use App\Support\Lookups;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class InstallmentController extends Controller
{
    /** كل ما يطبعه الإيصال — مشترك بين الإيصال الواحد ودفتر إيصالات الفترة */
    private const RECEIPT_RELATIONS = [
        'contract:id,code,customer_id,installments_count',
        'contract.customer:id,code,name,governorate,center,village,detailed_address',
        'contract.customer.phones',
        'contract.items:id,sales_contract_id,product_name',
        'collectedBy:id,name',
    ];

    private const PER_PAGE = 20;

    public function __construct(private readonly SalesContractService $contracts) {}

    public function index(Request $request): View
    {
        $installments = SalesContractInstallment::query()
            // العقد قد يكون محذوفًا (حذف ناعم) بينما تبقى أقساطه غير مرتبطة بحذف تلقائي
            ->whereHas('contract')
            ->with([
                'contract:id,code,customer_id,remaining,installer_id,sales_rep_id,installation_date',
                'contract.customer:id,name,code',
                'contract.installer:id,name',
                'contract.salesRep:id,name',
                'contract.items:id,sales_contract_id,product_name',
            ])
            ->search($request->input('q'))
            ->applyFilters($request->only(['status', 'customer_id', 'from', 'to']))
            ->applySort($request->input('sort'), $request->input('dir'))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.installments.index', [
            'installments' => $installments,
            'customers' => Customer::query()->orderBy('name')->pluck('name', 'id')->all(),
            'technicians' => $this->collectors(),
            'salesReps' => User::query()->whereIn('role', ['مندوب مبيعات', 'مندوبة', 'سكرتارية'])->orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }

    public function report(InstallmentReportRequest $request, InstallmentReport $report): View
    {
        $period = $request->period();
        $filters = $request->filters();
        $level = $request->areaLevel();

        // الأجزاء غير المطلوبة للطباعة لا تُحسب أصلًا
        $collections = $request->includes('collections') ? $report->collections($period, $filters, $level) : collect();
        $overdue = $request->includes('overdue') ? $report->overdue($period, $filters) : collect();
        $firstPayments = $request->includes('milestones') ? $report->firstPayments($period, $filters) : collect();
        $finished = $request->includes('milestones') ? $report->finished($period, $filters) : collect();

        return view('pages.installments.report', [
            'period' => $period,
            'content' => $request->content(),
            'filters' => $filters,
            'areaLevel' => $level,
            'overdueCutoff' => $report->overdueCutoff($period),
            'summary' => $report->summary($period, $filters),
            'areas' => $request->includes('collections') ? $report->collectionsByArea($period, $filters, $level) : collect(),
            'collections' => $collections->take(InstallmentReport::MAX_ROWS),
            'overdue' => $overdue->take(InstallmentReport::MAX_ROWS),
            'firstPayments' => $firstPayments->take(InstallmentReport::MAX_ROWS),
            'finished' => $finished->take(InstallmentReport::MAX_ROWS),
            'truncated' => collect([$collections, $overdue, $firstPayments, $finished])
                ->contains(fn ($rows) => $rows->count() > InstallmentReport::MAX_ROWS),
            'contents' => InstallmentReportRequest::CONTENTS,
            'areaLevels' => InstallmentReport::AREA_LEVELS,
            'collectors' => $this->collectors(),
            'governorates' => collect(Lookups::governorates())->merge(Customer::usedValuesOf('governorate'))->unique()->values()->all(),
            'centers' => Customer::usedValuesOf('center'),
            'villages' => Customer::usedValuesOf('village'),
        ]);
    }

    public function collect(CollectInstallmentRequest $request, SalesContractInstallment $installment): RedirectResponse
    {
        $this->contracts->collectInstallment($installment, $request->toData());

        return back()->with('success', "تم تسجيل تحصيل القسط رقم {$installment->number} بنجاح.");
    }

    public function receipt(SalesContractInstallment $installment): View|RedirectResponse
    {
        if (! $installment->isPaid()) {
            return redirect()->route('installments.index');
        }

        $installment->load(self::RECEIPT_RELATIONS);

        return view('pages.installments.receipt', [
            'installment' => $installment,
        ]);
    }

    /** دفتر إيصالات فترة: كل قسط غير محصَّل يستحق بين تاريخين، لطباعتها دفعة واحدة قبل جولة التحصيل */
    public function printBatch(Request $request): View
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = isset($validated['from']) ? Carbon::parse($validated['from'])->startOfDay() : today()->startOfMonth();
        $to = isset($validated['to']) ? Carbon::parse($validated['to'])->endOfDay() : today()->endOfMonth();

        $installments = SalesContractInstallment::query()
            ->whereHas('contract')
            ->unpaid()
            ->whereBetween('due_date', [$from, $to])
            ->with(self::RECEIPT_RELATIONS)
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        return view('pages.installments.print', [
            'installments' => $installments,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function uncollect(SalesContractInstallment $installment): RedirectResponse
    {
        if (! $installment->isPaid()) {
            return back();
        }

        $this->contracts->uncollectInstallment($installment);

        return back()->with('success', "تم التراجع عن تحصيل القسط رقم {$installment->number}.");
    }

    /** @return array<int, string> */
    private function collectors(): array
    {
        return User::query()->whereIn('role', ['محصل', 'فني تركيب'])->orderBy('name')->pluck('name', 'id')->all();
    }
}
