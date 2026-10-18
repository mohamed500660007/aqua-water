<?php

namespace App\Http\Controllers\Sales;

use App\Domain\Customers\Models\Customer;
use App\Domain\Sales\Models\SalesContractInstallment;
use App\Domain\Sales\Services\SalesContractService;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\CollectInstallmentRequest;
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
                'contract.customer.primaryPhone',
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
            'stats' => $this->stats(),
            'customers' => Customer::query()->orderBy('name')->pluck('name', 'id')->all(),
            'technicians' => User::query()->whereIn('role', ['محصل', 'فني تركيب'])->orderBy('name')->pluck('name', 'id')->all(),
            'salesReps' => User::query()->whereIn('role', ['مندوب مبيعات', 'مندوبة', 'سكرتارية'])->orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }

    public function collect(CollectInstallmentRequest $request, SalesContractInstallment $installment): RedirectResponse
    {
        $this->contracts->collectInstallment($installment, $request->toData());

        return back()->with('success', "تم تسجيل تحصيل القسط رقم {$installment->number} بنجاح.");
    }

    public function updateNote(Request $request, SalesContractInstallment $installment): RedirectResponse
    {
        $validated = $request->validate(
            ['note' => ['nullable', 'string', 'max:255']],
            ['note.max' => 'الملاحظة يجب ألا تزيد عن 255 حرفًا.'],
        );

        $this->contracts->updateInstallmentNote($installment, trim((string) ($validated['note'] ?? '')) ?: null);

        return back()->with('success', "تم حفظ ملاحظة القسط رقم {$installment->number} — ستظهر في إيصاله.");
    }

    public function receipt(SalesContractInstallment $installment): View|RedirectResponse
    {
        if (! $installment->isPaid()) {
            return redirect()->route('installments.index');
        }

        $installment->load(self::RECEIPT_RELATIONS);

        return view('pages.installments.receipt', ['installment' => $installment]);
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

    /** ملخص الأقساط في استعلام تجميعي واحد */
    private function stats(): array
    {
        $summary = SalesContractInstallment::query()
            ->whereHas('contract')
            ->selectRaw('SUM(CASE WHEN paid_at IS NULL AND due_date < ? THEN 1 ELSE 0 END) as overdue_count', [today()])
            ->selectRaw('SUM(CASE WHEN paid_at IS NULL AND due_date < ? THEN amount ELSE 0 END) as overdue_amount', [today()])
            ->selectRaw(
                'SUM(CASE WHEN paid_at IS NULL AND due_date >= ? AND due_date <= ? THEN amount ELSE 0 END) as due_this_month_amount',
                [today(), today()->endOfMonth()],
            )
            ->selectRaw('SUM(CASE WHEN paid_at IS NULL THEN amount ELSE 0 END) as total_unpaid')
            ->first();

        $collectedThisMonth = SalesContractInstallment::query()
            ->whereHas('contract')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [today()->startOfMonth(), today()->endOfMonth()])
            ->sum('paid_amount');

        return [
            'overdue_count' => (int) ($summary->overdue_count ?? 0),
            'overdue_amount' => (float) ($summary->overdue_amount ?? 0),
            'due_this_month_amount' => (float) ($summary->due_this_month_amount ?? 0),
            'total_unpaid' => (float) ($summary->total_unpaid ?? 0),
            'collected_this_month' => (float) $collectedThisMonth,
        ];
    }
}
