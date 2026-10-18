@extends('layouts.report', [
    'route' => 'inventory.purchase-orders.report',
    'query' => [],
    'back' => route('inventory.purchase-orders.index', $listQuery),
])

@php
    $money = fn ($amount) => number_format((float) $amount, 2);
    $share = fn ($part, $whole) => ($whole > 0 ? round($part * 100 / $whole, 1) : 0) . '%';
    $average = fn ($amount, $count) => $count > 0 ? $money($amount / $count) : '—';
    $visibleItems = 3;

    $scope = collect([
        'بحث' => $search,
        'المخزن' => $warehouses[$filters['warehouse_id'] ?? ''] ?? null,
        'المورد' => $filters['supplier'] ?? null,
        'المنتج' => $productOptions[$filters['product_id'] ?? ''] ?? null,
    ])->filter()->map(fn ($value, $label) => "{$label}: {$value}")->join(' — ');
@endphp

@section('title', 'تقرير المخزن — طلبيات الوارد')
@section('scope', $scope ?: 'جميع طلبيات الوارد خلال الفترة')

@section('filters')
    <label class="col-span-2 flex flex-col gap-1">
        <span class="text-[11px] font-bold text-gray-500">بحث (رقم الطلبية، المورد، المنتج)</span>
        <input class="h-9 rounded border border-gray-300 px-2 text-xs" name="q" type="search" value="{{ $search }}" placeholder="اكتب للبحث..." />
    </label>
    <x-report.filter label="المخزن" name="warehouse_id" :options="$warehouses" :selected="$filters['warehouse_id'] ?? null" placeholder="كل المخازن" />
    <x-report.filter label="المورد" name="supplier" :options="array_combine($suppliers, $suppliers)" :selected="$filters['supplier'] ?? null" placeholder="كل الموردين" />
    <x-report.filter label="المنتج" name="product_id" :options="$productOptions" :selected="$filters['product_id'] ?? null" placeholder="كل المنتجات" />
    <x-report.filter label="محتوى التقرير" name="content" :options="$contents" :selected="$content" :placeholder="null" />
    <x-report.filter label="ترتيب الكشف حسب" name="sort" :options="$sorts" :selected="request('sort', 'date')" :placeholder="null" />
    <x-report.filter label="اتجاه الترتيب" name="dir" :options="['desc' => 'تنازلي', 'asc' => 'تصاعدي']" :selected="request('dir', 'desc')" :placeholder="null" />
@endsection

@section('content')
    @if ($content !== 'list')
        <x-report.section class="avoid-break" no="1" title="الملخص العام">
            <x-report.figures :highlight="3" :items="[
                ['عدد الطلبيات', number_format($summary['orders']), 'طلبية'],
                ['إجمالي القطع الواردة', number_format($summary['pieces']), 'قطعة'],
                ['الأصناف المختلفة', number_format($summary['products']), 'صنف'],
                ['إجمالي مبالغ الطلبيات', $money($summary['amount']), 'ج.م'],
            ]" />
            <x-report.figures :items="[
                ['عدد الموردين', number_format($summary['suppliers']), 'مورد'],
                ['متوسط قيمة الطلبية', $average($summary['amount'], $summary['orders']), 'ج.م'],
                ['متوسط تكلفة القطعة', $average($summary['amount'], $summary['pieces']), 'ج.م', 'إجمالي المبالغ ÷ إجمالي القطع'],
            ]" />
        </x-report.section>

        <div class="grid grid-cols-2 gap-4">
            @foreach ([['المشتريات حسب المخزن', 'المخزن', $byWarehouse], ['المشتريات حسب المورد', 'المورد', $bySupplier]] as [$title, $label, $groups])
                <x-report.section class="avoid-break" :no="$loop->iteration + 1" :title="$title">
                    <table class="rpt-table">
                        <thead>
                            <tr>
                                <th>{{ $label }}</th>
                                <th class="c">الطلبيات</th>
                                <th class="c">القطع</th>
                                <th class="l">المبلغ</th>
                                <th class="c">الحصة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($groups as $group)
                                <tr>
                                    <td class="font-bold">{{ $group['name'] }}</td>
                                    <td class="num c">{{ number_format($group['orders']) }}</td>
                                    <td class="num c">{{ number_format($group['pieces']) }}</td>
                                    <td class="num l font-bold">{{ $money($group['amount']) }}</td>
                                    <td class="num c">{{ $share($group['amount'], $summary['amount']) }}</td>
                                </tr>
                            @empty
                                <tr><td class="is-empty" colspan="5">لا توجد طلبيات في هذه الفترة</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </x-report.section>
            @endforeach
        </div>

        <x-report.section class="avoid-break" no="4" title="الأصناف الواردة">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th class="c w-8">م</th>
                        <th>الكود</th>
                        <th>المنتج</th>
                        <th>النوع</th>
                        <th class="c">عدد الطلبيات</th>
                        <th class="c">الكمية الواردة</th>
                        <th class="c">الحصة من القطع</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td class="num c muted">{{ $loop->iteration }}</td>
                            <td class="num font-bold" dir="ltr">{{ $product->sku }}</td>
                            <td class="font-bold">{{ $product->name }}</td>
                            <td class="text-[10px]">{{ $product->kind?->label() ?? '—' }}</td>
                            <td class="num c">{{ number_format($product->orders) }}</td>
                            <td class="num c font-black">{{ number_format($product->quantity) }}</td>
                            <td class="num c">{{ $share($product->quantity, $summary['pieces']) }}</td>
                        </tr>
                    @empty
                        <tr><td class="is-empty" colspan="7">لا توجد أصناف واردة في هذه الفترة</td></tr>
                    @endforelse
                </tbody>
                @if ($products->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td colspan="5">الإجمالي</td>
                            <td class="num c">{{ number_format($products->sum('quantity')) }}</td>
                            <td class="num c">100%</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </x-report.section>
    @endif

    @if ($content !== 'summary')
        <x-report.section :no="$content === 'list' ? 1 : 5" title="كشف الطلبيات"
            :note="number_format($rows->count()) . ' طلبية' . ($truncated ? ' — الكشف مقتصر على أول ' . number_format($rows->count()) . '، ضيّق الفلاتر لطباعة الباقي' : '')">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th class="c">م</th>
                        <th>رقم الطلبية</th>
                        <th class="c">التاريخ</th>
                        <th>المخزن</th>
                        <th>المورد</th>
                        <th>الأصناف</th>
                        <th class="c">القطع</th>
                        <th class="l">المبلغ (ج.م)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $order)
                        <tr>
                            <td class="num c muted">{{ $loop->iteration }}</td>
                            <td class="num font-bold" dir="ltr">{{ $order->number() }}</td>
                            <td class="num c" dir="ltr">{{ $order->order_date->format('Y-m-d') }}</td>
                            <td class="text-[10px] font-bold">{{ $order->warehouse?->name ?? '—' }}</td>
                            <td class="text-[10px]">{{ $order->supplier ?? '—' }}</td>
                            <td class="text-[9.5px] leading-snug">
                                {{ $order->items->take($visibleItems)->map(fn ($item) => ($item->product?->name ?? 'منتج محذوف') . ' × ' . number_format($item->quantity))->join('، ') }}
                                @if ($order->items->count() > $visibleItems)
                                    <span class="muted">و{{ $order->items->count() - $visibleItems }} أصناف أخرى</span>
                                @endif
                            </td>
                            <td class="num c font-bold">{{ number_format((int) $order->pieces) }}</td>
                            <td class="num l font-black">{{ $money($order->total_amount) }}</td>
                        </tr>
                    @empty
                        <tr><td class="is-empty" colspan="8">لا توجد طلبيات مطابقة لنطاق التقرير</td></tr>
                    @endforelse
                </tbody>
                @if ($rows->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td colspan="6">إجمالي الكشف</td>
                            <td class="num c">{{ number_format($rows->sum('pieces')) }}</td>
                            <td class="num l">{{ $money($rows->sum('total_amount')) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </x-report.section>
    @endif
@endsection
