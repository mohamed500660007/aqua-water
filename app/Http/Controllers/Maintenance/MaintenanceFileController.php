<?php

namespace App\Http\Controllers\Maintenance;

use App\Domain\Maintenance\Models\CandleStage;
use App\Domain\Sales\Models\SalesContract;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * ملف الصيانة: الأجهزة المستحقة لتغيير شمعة أو أكثر خلال الفترة المختارة، مع المراحل المستحقة لكل جهاز.
 * الجهاز يخرج من القائمة تلقائيًا بعد تسجيل صيانته لأن موعده القادم يتجدد.
 */
class MaintenanceFileController extends Controller
{
    private const PER_PAGE = 15;

    /** نافذة الاستحقاق => [العنوان، عدد الأيام من اليوم] */
    public const WINDOWS = [
        'overdue' => ['متأخر فقط', -1],
        'today' => ['مستحق اليوم', 0],
        'week' => ['خلال 7 أيام', 7],
        'month' => ['خلال 30 يومًا', 30],
    ];

    public function index(Request $request): View
    {
        $window = array_key_exists($request->input('window'), self::WINDOWS) ? $request->input('window') : 'week';
        $until = today()->addDays(self::WINDOWS[$window][1])->toDateString();
        $due = fn ($query) => $query->reorder()->whereDate('next_due_at', '<=', $until);

        $devices = SalesContract::query()
            ->installedDevices()
            ->search($request->input('q'))
            ->whereHas('candleSchedules', $due)
            ->with([
                'customer:id,name,code',
                'customer.primaryPhone',
                'candleSchedules' => fn ($query) => $due($query)->orderBy('position'),
                'activeMaintenancePackage:id,sales_contract_id,candles,gift_candles,used_candles,status',
            ])
            ->withMin(['candleSchedules as next_due_at' => $due], 'next_due_at')
            ->withMax('maintenanceOperations as last_serviced_at', 'serviced_at')
            ->orderBy('next_due_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.maintenance.file', [
            'devices' => $devices,
            'stages' => CandleStage::configured(),
            'window' => $window,
            'windows' => array_map(fn (array $config) => $config[0], self::WINDOWS),
        ]);
    }
}
