<?php

namespace App\Http\Controllers\Sales;

use App\Domain\Customers\Models\Customer;
use App\Domain\Sales\Models\SalesContractInstallment;
use App\Domain\Sales\Services\SalesContractService;
use App\Domain\Settings\Models\Setting;
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
            'customers' => Customer::query()->orderBy('name')->pluck('name', 'id')->all(),
            'technicians' => User::query()->whereIn('role', ['محصل', 'فني تركيب'])->orderBy('name')->pluck('name', 'id')->all(),
            'salesReps' => User::query()->whereIn('role', ['مندوب مبيعات', 'مندوبة', 'سكرتارية'])->orderBy('name')->pluck('name', 'id')->all(),
            'receiptNote' => Setting::get(Setting::RECEIPT_NOTE),
        ]);
    }

    public function collect(CollectInstallmentRequest $request, SalesContractInstallment $installment): RedirectResponse
    {
        $this->contracts->collectInstallment($installment, $request->toData());

        return back()->with('success', "تم تسجيل تحصيل القسط رقم {$installment->number} بنجاح.");
    }

    /** ملاحظة ثابتة تُطبع على كل الإيصالات — تعديلها يسري على كل العملاء دفعة واحدة */
    public function updateReceiptNote(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            ['receipt_note' => ['nullable', 'string', 'max:255']],
            ['receipt_note.max' => 'الملاحظة يجب ألا تزيد عن 255 حرفًا.'],
        );

        Setting::put(Setting::RECEIPT_NOTE, trim((string) ($validated['receipt_note'] ?? '')) ?: null);

        return back()->with('success', 'تم حفظ ملاحظة الإيصالات — ستظهر على إيصالات كل العملاء.');
    }

    public function receipt(SalesContractInstallment $installment): View|RedirectResponse
    {
        if (! $installment->isPaid()) {
            return redirect()->route('installments.index');
        }

        $installment->load(self::RECEIPT_RELATIONS);

        return view('pages.installments.receipt', [
            'installment' => $installment,
            'receiptNote' => Setting::get(Setting::RECEIPT_NOTE),
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
            'receiptNote' => Setting::get(Setting::RECEIPT_NOTE),
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
}
