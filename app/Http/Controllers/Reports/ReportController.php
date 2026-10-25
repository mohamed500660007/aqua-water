<?php

namespace App\Http\Controllers\Reports;

use App\Domain\Inventory\Services\CustodyReport;
use App\Domain\Maintenance\Services\MaintenanceReport;
use App\Domain\Reports\Services\AfterSalesReport;
use App\Domain\Reports\Services\ExecutiveReport;
use App\Domain\Reports\Services\FinanceReport;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\ReportRequest;
use App\Support\ReportCatalog;
use Illuminate\Contracts\View\View;

/**
 * ملف التقارير: صفحة تجمع كل تقارير الشركة، وتقارير الإدارة العابرة للوحدات
 * (الملخص التنفيذي، المالية، العهد، الصيانة، ما بعد البيع).
 */
class ReportController extends Controller
{
    public function index(): View
    {
        return view('pages.reports.index', ['groups' => ReportCatalog::groups()]);
    }

    public function executive(ReportRequest $request, ExecutiveReport $report): View
    {
        $period = $request->period();
        $previous = $report->previousPeriod($period);

        return view('pages.reports.executive', [
            'period' => $period,
            'previous' => $previous,
            'current' => $report->kpis($period),
            'before' => $report->kpis($previous),
            'leaders' => $report->leaders($period),
            'alerts' => $report->alerts(),
        ]);
    }

    public function finance(ReportRequest $request, FinanceReport $report): View
    {
        $period = $request->period();

        return view('pages.reports.finance', [
            'period' => $period,
            'totals' => $report->totals($period),
            'monthly' => $report->monthly($period),
            'outstanding' => $report->outstanding($period),
        ]);
    }

    public function custody(ReportRequest $request, CustodyReport $report): View
    {
        $period = $request->period();
        $userId = $request->userId();

        return view('pages.reports.custody', [
            'period' => $period,
            'userId' => $userId,
            'employees' => $report->byEmployee($period, $userId),
            'products' => $report->byProduct($period, $userId),
            'settlements' => $report->settlements($period, $userId),
            'people' => User::query()->custodians()->orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }

    public function maintenance(ReportRequest $request, MaintenanceReport $report): View
    {
        $period = $request->period();
        $userId = $request->userId();
        $done = $report->done($period, $userId);

        return view('pages.reports.maintenance', [
            'period' => $period,
            'userId' => $userId,
            'summary' => $report->summary($period, $userId),
            'technicians' => $report->byTechnician($period, $userId),
            'candles' => $report->byCandle($period, $userId),
            'done' => $done->take(MaintenanceReport::MAX_ROWS),
            'truncated' => $done->count() > MaintenanceReport::MAX_ROWS,
            'late' => $report->pendingByContract($period, $userId, late: true),
            'due' => $report->pendingByContract($period, $userId, late: false),
            'people' => $this->technicians(),
        ]);
    }

    public function afterSales(ReportRequest $request, AfterSalesReport $report): View
    {
        $period = $request->period();
        $userId = $request->userId();
        $complaints = $report->complaintRows($period, $userId);
        $gifts = $report->giftRows($period, $userId);

        return view('pages.reports.after-sales', [
            'period' => $period,
            'userId' => $userId,
            'summary' => $report->summary($period, $userId),
            'technicians' => $report->byTechnician($period, $userId),
            'parts' => $report->partsUsed($period, $userId),
            'complaints' => $complaints->take(AfterSalesReport::MAX_ROWS),
            'gifts' => $gifts->take(AfterSalesReport::MAX_ROWS),
            'truncated' => $complaints->count() > AfterSalesReport::MAX_ROWS || $gifts->count() > AfterSalesReport::MAX_ROWS,
            'people' => $this->technicians(),
        ]);
    }

    /** @return array<int, string> */
    private function technicians(): array
    {
        return User::query()->where('role', User::INSTALLER_ROLE)->orderBy('name')->pluck('name', 'id')->all();
    }
}
