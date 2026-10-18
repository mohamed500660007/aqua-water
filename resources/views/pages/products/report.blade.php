@extends('layouts.report', [
    'route' => 'products.report',
    'query' => [],
    'back' => route('products.index', $listQuery),
])

@use('App\Domain\Products\Enums\ProductKind')
@use('App\Domain\Products\Enums\ProductStatus')

@php
    $money = fn ($amount) => number_format((float) $amount, 2);
    $share = fn ($part, $whole) => ($whole > 0 ? round($part * 100 / $whole, 1) : 0) . '%';
    $warehouseName = $warehouses[$filters['warehouse_id'] ?? ''] ?? null;
    $stockScope = $warehouseName ? "في {$warehouseName}" : 'في كل المخازن';

    $stockPieces = $byKind->sum('pieces');
    $stockValue = $byKind->sum('value');
    $stockCost = $byKind->sum('cost');

    $scope = collect([
        'بحث' => $search,
        'التصنيف' => $filters['category'] ?? null,
        'النوع' => isset($filters['kind']) ? ProductKind::from($filters['kind'])->label() : null,
        'الحالة' => isset($filters['status']) ? ProductStatus::from($filters['status'])->label() : null,
        'المخزن' => $warehouseName,
        'الرصيد' => ['in' => 'متوفر في المخزن', 'out' => 'نفد من المخزن'][$filters['stock'] ?? ''] ?? null,
    ])->filter()->map(fn ($value, $label) => "{$label}: {$value}")->join(' — ');
@endphp

@section('title', 'تقرير المنتجات والمخزون')
@section('scope', $scope ?: 'جميع المنتجات — الأرصدة حتى تاريخ الإصدار، والحركات والمبيعات خلال الفترة')

@section('filters')
    <label class="col-span-2 flex flex-col gap-1">
        <span class="text-[11px] font-bold text-gray-500">بحث (اسم المنتج أو الكود)</span>
        <input class="h-9 rounded border border-gray-300 px-2 text-xs" name="q" type="search" value="{{ $search }}" placeholder="اكتب للبحث..." />
    </label>
    <x-report.filter label="التصنيف" name="category" :options="array_combine($categories, $categories)" :selected="$filters['category'] ?? null" />
    <x-report.filter label="نوع المنتج" name="kind" :options="$kinds" :selected="$filters['kind'] ?? null" />
    <x-report.filter label="الحالة" name="status" :options="$statuses" :selected="$filters['status'] ?? null" />
    <x-report.filter label="المخزن" name="warehouse_id" :options="$warehouses" :selected="$filters['warehouse_id'] ?? null" placeholder="كل المخازن" />
    <x-report.filter label="الرصيد" name="stock" :options="['in' => 'متوفر في المخزن', 'out' => 'نفد من المخزن']" :selected="$filters['stock'] ?? null" />
    <x-report.filter label="محتوى التقرير" name="content" :options="$contents" :selected="$content" :placeholder="null" />
    <x-report.filter label="ترتيب الكشف حسب" name="sort" :options="$sorts" :selected="request('sort', 'name')" :placeholder="null" />
    <x-report.filter label="اتجاه الترتيب" name="dir" :options="['asc' => 'تصاعدي', 'desc' => 'تنازلي']" :selected="request('dir', 'asc')" :placeholder="null" />
@endsection

@section('content')
    @if ($content !== 'list')
        <x-report.section class="avoid-break" no="1" title="الملخص العام">
            <x-report.figures :highlight="3" :items="[
                ['عدد المنتجات', number_format($counts['products']), 'منتج', 'متاح ' . $counts['active'] . ' — موقوف ' . $counts['inactive']],
                ['إجمالي القطع ' . $stockScope, number_format($stockPieces), 'قطعة'],
                ['منتجات نفدت ' . $stockScope, number_format($counts['out_of_stock']), 'منتج'],
                ['قيمة المخزون بسعر البيع', $money($stockValue), 'ج.م', 'بالتكلفة ' . $money($stockCost)],
            ]" />
            <x-report.figures :items="[
                ['عهدة مع الموظفين', number_format($counts['employee_pieces']), 'قطعة'],
                ['المباع خلال الفترة', number_format($activity['sold_quantity']), 'قطعة', 'في ' . $activity['sold_contracts'] . ' عقد'],
                ['قيمة المبيعات', $money($activity['sold_value']), 'ج.م'],
                ['هامش الربح المتوقع للمخزون', $money($stockValue - $stockCost), 'ج.م', 'سعر البيع − التكلفة'],
            ]" />
        </x-report.section>

        <x-report.section class="avoid-break" no="2" title="المخزون حسب نوع المنتج">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>نوع المنتج</th>
                        <th class="c">المنتجات</th>
                        <th class="c">القطع</th>
                        <th class="l">القيمة بسعر البيع</th>
                        <th class="l">القيمة بالتكلفة</th>
                        <th class="c">الحصة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($byKind as $row)
                        <tr>
                            <td class="font-bold">{{ $row['kind']->label() }}</td>
                            <td class="num c">{{ number_format($row['products']) }}</td>
                            <td class="num c font-bold">{{ number_format($row['pieces']) }}</td>
                            <td class="num l">{{ $money($row['value']) }}</td>
                            <td class="num l">{{ $money($row['cost']) }}</td>
                            <td class="num c">{{ $share($row['value'], $stockValue) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>الإجمالي</td>
                        <td class="num c">{{ number_format($byKind->sum('products')) }}</td>
                        <td class="num c">{{ number_format($stockPieces) }}</td>
                        <td class="num l">{{ $money($stockValue) }}</td>
                        <td class="num l">{{ $money($stockCost) }}</td>
                        <td class="num c">100%</td>
                    </tr>
                </tfoot>
            </table>
        </x-report.section>

        <div class="grid grid-cols-2 gap-4">
            <x-report.section class="avoid-break" no="3" title="المخزون حسب المخزن">
                <table class="rpt-table">
                    <thead>
                        <tr>
                            <th>المخزن</th>
                            <th class="c">القطع</th>
                            <th class="l">القيمة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($byWarehouse as $row)
                            <tr>
                                <td class="font-bold">{{ $row->warehouse }}</td>
                                <td class="num c">{{ number_format($row->pieces) }}</td>
                                <td class="num l">{{ $money($row->value) }}</td>
                            </tr>
                        @empty
                            <tr><td class="is-empty" colspan="3">لا توجد أرصدة</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-report.section>

            <x-report.section class="avoid-break" no="4" title="حركة المخزن خلال الفترة">
                <table class="rpt-table">
                    <thead>
                        <tr>
                            <th>الحركة</th>
                            <th class="c">الكمية</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td class="font-bold">وارد</td><td class="num c">{{ number_format($activity['in']) }}</td></tr>
                        <tr><td class="font-bold">منصرف</td><td class="num c">{{ number_format($activity['out']) }}</td></tr>
                        <tr><td class="font-bold">تسويات جرد</td><td class="num c">{{ number_format($activity['adjustments']) }}</td></tr>
                        <tr><td class="font-bold">مباع في عقود</td><td class="num c">{{ number_format($activity['sold_quantity']) }}</td></tr>
                    </tbody>
                </table>
            </x-report.section>
        </div>
    @endif

    @if ($content !== 'summary')
        <x-report.section :no="$content === 'list' ? 1 : 5" title="كشف المنتجات"
            :note="number_format($rows->count()) . ' منتج' . ($truncated ? ' — الكشف مقتصر على أول ' . number_format($rows->count()) . '، ضيّق الفلاتر لطباعة الباقي' : '')">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th class="c">م</th>
                        <th>الكود</th>
                        <th>المنتج / التصنيف</th>
                        <th>النوع</th>
                        <th class="l">السعر</th>
                        <th class="l">التكلفة</th>
                        <th class="c">الرصيد</th>
                        <th class="c">عهدة</th>
                        <th class="c">وارد<br>الفترة</th>
                        <th class="c">منصرف<br>الفترة</th>
                        <th class="c">مباع<br>الفترة</th>
                        <th class="l">قيمة الرصيد</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $product)
                        @php($stock = (int) $product->stock_quantity)
                        <tr>
                            <td class="num c muted">{{ $loop->iteration }}</td>
                            <td class="num font-bold" dir="ltr">{{ $product->sku }}</td>
                            <td>
                                <p class="font-bold leading-tight">{{ $product->name }}
                                    @if ($product->status === ProductStatus::Inactive)
                                        <span class="tag">موقوف</span>
                                    @endif
                                </p>
                                <p class="text-[9.5px] muted">{{ $product->category ?? '—' }}</p>
                            </td>
                            <td class="whitespace-nowrap text-[10px]">{{ $product->kind?->label() }}</td>
                            <td class="num l">{{ $money($product->price) }}</td>
                            <td class="num l muted">{{ $product->cost !== null ? $money($product->cost) : '—' }}</td>
                            <td class="num c font-black">
                                @if ($stock <= 0)
                                    <span class="tag is-strong">نفد</span>
                                @else
                                    {{ number_format($stock) }}
                                @endif
                            </td>
                            <td class="num c">{{ (int) $product->employee_quantity ?: '—' }}</td>
                            <td class="num c">{{ (int) $product->period_in ?: '—' }}</td>
                            <td class="num c">{{ (int) $product->period_out ?: '—' }}</td>
                            <td class="num c">{{ (int) $product->period_sold ?: '—' }}</td>
                            <td class="num l font-bold">{{ $money(max(0, $stock) * (float) $product->price) }}</td>
                        </tr>
                    @empty
                        <tr><td class="is-empty" colspan="12">لا توجد منتجات مطابقة لنطاق التقرير</td></tr>
                    @endforelse
                </tbody>
                @if ($rows->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td colspan="6">إجمالي الكشف</td>
                            <td class="num c">{{ number_format($rows->sum('stock_quantity')) }}</td>
                            <td class="num c">{{ number_format($rows->sum('employee_quantity')) }}</td>
                            <td class="num c">{{ number_format($rows->sum('period_in')) }}</td>
                            <td class="num c">{{ number_format($rows->sum('period_out')) }}</td>
                            <td class="num c">{{ number_format($rows->sum('period_sold')) }}</td>
                            <td class="num l">{{ $money($rows->sum(fn ($product) => max(0, (int) $product->stock_quantity) * (float) $product->price)) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </x-report.section>
    @endif
@endsection
