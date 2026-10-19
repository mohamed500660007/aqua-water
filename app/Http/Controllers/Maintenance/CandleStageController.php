<?php

namespace App\Http\Controllers\Maintenance;

use App\Domain\Maintenance\Models\CandleStage;
use App\Domain\Maintenance\Services\CandleStageService;
use App\Domain\Products\Enums\ProductKind;
use App\Domain\Products\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Requests\Maintenance\CandleStageRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/** إعداد مراحل الفلتر السبع — أي شمعة في كل مرحلة وكل كام شهر تتغير */
class CandleStageController extends Controller
{
    public function edit(): View
    {
        return view('pages.maintenance.stages', [
            'stages' => CandleStage::configured(),
            'positions' => CandleStage::POSITIONS,
            'products' => Product::query()
                ->orderByRaw('CASE WHEN kind = ? THEN 0 ELSE 1 END', [ProductKind::Cartridge->value])
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all(),
        ]);
    }

    public function update(CandleStageRequest $request, CandleStageService $stages): RedirectResponse
    {
        $stages->save($request->stages());

        return redirect()
            ->route('maintenance.file')
            ->with('success', 'تم حفظ مراحل الشمع وإعادة حساب مواعيد كل الأجهزة.');
    }
}
