<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Customers\Models\Customer;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Maintenance\Models\MaintenancePlan;
use App\Domain\Products\Models\Product;
use App\Domain\Sales\Models\SalesContract;
use App\Domain\Settings\Settings;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\SettingsRequest;
use App\Support\Lookups;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * ملف الإعدادات: قسم لكل مجموعة إعدادات قابلة للتعديل، ومرجع للقيم الثابتة (الأدوار وخطة الصيانة)
 * ومعلومات النظام.
 */
class SettingsController extends Controller
{
    public function index(Settings $settings): View
    {
        return view('pages.settings.index', [
            'sections' => collect(Settings::sections())->map(fn (array $section, string $key) => $section + ['values' => $settings->section($key)]),
            'roles' => Lookups::roles(),
            'candles' => collect(MaintenancePlan::CANDLE_INTERVALS)->map(fn (int $months, int $position) => [
                'label' => MaintenancePlan::candleLabel($position),
                'interval' => MaintenancePlan::intervalLabel($months),
            ])->values(),
            'system' => $this->systemInfo(),
        ]);
    }

    public function update(SettingsRequest $request, Settings $settings): RedirectResponse
    {
        $settings->save($request->section(), $request->values());

        return redirect()
            ->route('settings.index', ['tab' => $request->section()])
            ->with('success', 'تم حفظ «'.Settings::sections()[$request->section()]['label'].'».');
    }

    public function reset(string $section, Settings $settings): RedirectResponse
    {
        $settings->reset($section);

        return redirect()
            ->route('settings.index', ['tab' => $section])
            ->with('success', 'تمت استعادة القيم الافتراضية لـ «'.Settings::sections()[$section]['label'].'».');
    }

    /** @return array<string, string> */
    private function systemInfo(): array
    {
        $count = fn (string $model) => number_format($model::query()->count());

        return [
            'إصدار PHP' => PHP_VERSION,
            'إصدار Laravel' => app()->version(),
            'قاعدة البيانات' => DB::connection()->getDriverName().' '.DB::connection()->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION),
            'البيئة' => app()->environment(),
            'المنطقة الزمنية' => config('app.timezone'),
            'رابط النظام' => config('app.url'),
            'العملاء' => $count(Customer::class),
            'العقود' => $count(SalesContract::class),
            'المنتجات' => $count(Product::class),
            'الموظفون' => $count(User::class),
            'المخازن' => $count(Warehouse::class),
        ];
    }
}
