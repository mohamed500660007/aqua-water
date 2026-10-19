<?php

namespace App\Http\Controllers\Maintenance;

use App\Domain\Maintenance\Enums\MaintenancePlanStatus;
use App\Domain\Maintenance\Models\MaintenancePlan;
use App\Domain\Maintenance\Services\MaintenancePlanService;
use App\Domain\Products\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Requests\Maintenance\MaintenancePlanRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MaintenancePlanController extends Controller
{
    private const PER_PAGE = 12;

    public function __construct(private readonly MaintenancePlanService $plans) {}

    public function index(Request $request): View
    {
        $plans = MaintenancePlan::query()
            ->withCount('items')
            ->search($request->input('q'))
            ->applyFilters($request->only('status'))
            ->applySort($request->input('sort'), $request->input('dir'))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.maintenance-plans.index', [
            'plans' => $plans,
            'statuses' => MaintenancePlanStatus::options(),
        ]);
    }

    public function create(): View
    {
        return view('pages.maintenance-plans.create', $this->formOptions());
    }

    public function store(MaintenancePlanRequest $request): RedirectResponse
    {
        $plan = $this->plans->create($request->toData());

        return redirect()
            ->route('maintenance-plans.show', $plan)
            ->with('success', "تمت إضافة خطة «{$plan->name}» بنجاح.");
    }

    public function show(MaintenancePlan $plan): View
    {
        return view('pages.maintenance-plans.show', [
            'plan' => $plan->load('items.product'),
        ]);
    }

    public function edit(MaintenancePlan $plan): View
    {
        return view('pages.maintenance-plans.edit', [
            'plan' => $plan->load('items'),
        ] + $this->formOptions());
    }

    public function update(MaintenancePlanRequest $request, MaintenancePlan $plan): RedirectResponse
    {
        $this->plans->update($plan, $request->toData());

        return redirect()
            ->route('maintenance-plans.show', $plan)
            ->with('success', 'تم حفظ تعديلات الخطة بنجاح.');
    }

    public function destroy(MaintenancePlan $plan): RedirectResponse
    {
        $this->plans->delete($plan);

        return redirect()
            ->route('maintenance-plans.index')
            ->with('success', "تم حذف خطة «{$plan->name}».");
    }

    private function formOptions(): array
    {
        return [
            'statuses' => MaintenancePlanStatus::options(),
            'defaultStatus' => MaintenancePlanStatus::Active->value,
            'products' => Product::query()
                ->orderBy('name')
                ->get(['id', 'sku', 'name', 'price', 'status']),
        ];
    }
}
