<?php

namespace App\Http\Controllers\Maintenance;

use App\Domain\Maintenance\Models\MaintenancePlan;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * عرض خطة الصيانة الوحيدة: سبع شمعات بمواعيد ثابتة — لا شيء فيها يُعدَّل.
 */
class MaintenancePlanController extends Controller
{
    public function show(): View
    {
        return view('pages.maintenance.plan', ['intervals' => MaintenancePlan::CANDLE_INTERVALS]);
    }
}
