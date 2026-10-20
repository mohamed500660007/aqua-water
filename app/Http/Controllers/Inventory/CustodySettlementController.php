<?php

namespace App\Http\Controllers\Inventory;

use App\Domain\Inventory\Enums\CustodySettlementStatus;
use App\Domain\Inventory\Enums\CustodySettlementType;
use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Exceptions\SettlementAlreadyPostedException;
use App\Domain\Inventory\Models\CustodySettlement;
use App\Domain\Inventory\Services\CustodySettlementService;
use App\Domain\Inventory\Services\EmployeeStockService;
use App\Domain\Products\Models\Product;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\CustodySettlementRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustodySettlementController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(private readonly CustodySettlementService $settlements) {}

    public function index(Request $request): View
    {
        $settlements = CustodySettlement::query()
            ->applyFilters($request->only(['user_id', 'type', 'status']))
            ->with('user:id,name')
            ->withSum('items as pieces', 'quantity')
            ->orderByDesc('settlement_date')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.inventory.custody-settlements.index', [
            'settlements' => $settlements,
            'employees' => $this->employees(),
            'types' => CustodySettlementType::options(),
            'statuses' => CustodySettlementStatus::options(),
        ]);
    }

    /** لوحة معاينة جانبية للتسوية — تُطلب عند الفتح فقط */
    public function panel(CustodySettlement $settlement): View
    {
        return view('pages.inventory.custody-settlements.panel', [
            'settlement' => $settlement->load(['user:id,name,role', 'items.product:id,sku,name']),
        ]);
    }

    public function create(Request $request, EmployeeStockService $employeeStock): View
    {
        return view('pages.inventory.custody-settlements.create', $this->formData($employeeStock) + [
            'selectedUserId' => $request->integer('user_id') ?: null,
        ]);
    }

    public function store(CustodySettlementRequest $request): RedirectResponse
    {
        try {
            $settlement = $this->settlements->create($request->toData(), $request->shouldPost());
        } catch (InsufficientStockException $exception) {
            return back()->withInput()->withErrors(['items' => $exception->getMessage()]);
        }

        return $this->redirectAfterSave($settlement);
    }

    public function edit(CustodySettlement $settlement, EmployeeStockService $employeeStock): View|RedirectResponse
    {
        if (! $settlement->isDraft()) {
            return redirect()->route('inventory.custody-settlements.index')
                ->with('error', SettlementAlreadyPostedException::for($settlement->number())->getMessage());
        }

        return view('pages.inventory.custody-settlements.edit', $this->formData($employeeStock) + [
            'settlement' => $settlement->load('items:id,custody_settlement_id,product_id,quantity'),
        ]);
    }

    public function update(CustodySettlementRequest $request, CustodySettlement $settlement): RedirectResponse
    {
        try {
            $settlement = $this->settlements->update($settlement, $request->toData(), $request->shouldPost());
        } catch (InsufficientStockException $exception) {
            return back()->withInput()->withErrors(['items' => $exception->getMessage()]);
        } catch (SettlementAlreadyPostedException $exception) {
            return redirect()->route('inventory.custody-settlements.index')->with('error', $exception->getMessage());
        }

        return $this->redirectAfterSave($settlement);
    }

    public function post(CustodySettlement $settlement): RedirectResponse
    {
        try {
            $this->settlements->post($settlement);
        } catch (InsufficientStockException|SettlementAlreadyPostedException $exception) {
            return back()->with('error', "تعذّر ترحيل التسوية {$settlement->number()}. {$exception->getMessage()}");
        }

        return back()->with('success', "تم ترحيل التسوية {$settlement->number()} وتحديث عهدة الموظف.");
    }

    public function destroy(CustodySettlement $settlement): RedirectResponse
    {
        try {
            $this->settlements->delete($settlement);
        } catch (SettlementAlreadyPostedException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "تم حذف مسودة التسوية {$settlement->number()}.");
    }

    private function redirectAfterSave(CustodySettlement $settlement): RedirectResponse
    {
        $message = $settlement->isDraft()
            ? "تم حفظ التسوية {$settlement->number()} كمسودة — لن تتغير العهدة قبل ترحيلها."
            : "تم ترحيل التسوية {$settlement->number()} وتحديث عهدة الموظف.";

        return redirect()
            ->route('inventory.employee-stocks.show', $settlement->user_id)
            ->with('success', $message);
    }

    /** @return array<string, mixed> */
    private function formData(EmployeeStockService $employeeStock): array
    {
        return [
            'employees' => $this->employees(),
            'products' => Product::query()->orderBy('name')->pluck('name', 'id')->all(),
            'types' => CustodySettlementType::options(),
            'availability' => $employeeStock->availabilityMatrix(),
        ];
    }

    /** @return array<int, string> */
    private function employees(): array
    {
        return User::query()->custodians()->orderBy('name')->pluck('name', 'id')->all();
    }
}
