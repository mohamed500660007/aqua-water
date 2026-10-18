@extends('layouts.report', [
    'route' => 'customers.report',
    'query' => [],
    'back' => route('customers.index', $listQuery),
])

@use('App\Domain\Customers\Enums\CustomerOrigin')
@use('App\Domain\Customers\Enums\CustomerStatus')

@php
    $total = $summary['total'];
    $percent = fn (int $count) => ($total ? round($count * 100 / $total, 1) : 0) . '%';
    $money = fn (float $amount) => number_format($amount, 2);

    $scope = collect([
        'بحث' => $search,
        'الحالة' => isset($filters['status']) ? CustomerStatus::from($filters['status'])->label() : null,
        'النوع' => isset($filters['origin']) ? CustomerOrigin::from($filters['origin'])->label() : null,
        'المحافظة' => $filters['governorate'] ?? null,
        'المركز' => $filters['center'] ?? null,
        'القرية' => $filters['village'] ?? null,
        'الرصيد' => ['due' => 'عليهم مبالغ متبقية', 'clear' => 'بلا مبالغ متبقية'][$filters['balance'] ?? ''] ?? null,
    ])->filter()->map(fn ($value, $label) => "{$label}: {$value}")->join(' — ');

    // أكبر 6 محافظات، والباقي يُجمع في سطر واحد حتى يبقى الجدول قصيرًا
    $topGovernorates = $governorates->take(6);
    $otherGovernorates = $governorates->skip(6)->sum();
@endphp

@section('title', 'تقرير العملاء')
@section('scope', $scope ?: 'جميع العملاء المسجلين')

@section('filters')
    <label class="col-span-2 flex flex-col gap-1">
        <span class="text-[11px] font-bold text-gray-500">بحث (الاسم، الكود، الرقم القومي، الهاتف)</span>
        <input class="h-9 rounded border border-gray-300 px-2 text-xs" name="q" type="search" value="{{ $search }}" placeholder="اكتب للبحث..." />
    </label>
    <x-report.filter label="الحالة" name="status" :options="$statuses" :selected="$filters['status'] ?? null" />
    <x-report.filter label="نوع العميل" name="origin" :options="$origins" :selected="$filters['origin'] ?? null" />
    <x-report.filter label="المحافظة" name="governorate" :options="array_combine($governoratesList, $governoratesList)" :selected="$filters['governorate'] ?? null" />
    <x-report.filter label="المركز" name="center" :options="array_combine($centers, $centers)" :selected="$filters['center'] ?? null" />
    <x-report.filter label="القرية" name="village" :options="array_combine($villages, $villages)" :selected="$filters['village'] ?? null" />
    <x-report.filter label="الرصيد" name="balance" :options="['due' => 'عليهم مبالغ متبقية', 'clear' => 'بلا مبالغ متبقية']" :selected="$filters['balance'] ?? null" />
    <x-report.filter label="محتوى التقرير" name="content" :options="$contents" :selected="$content" :placeholder="null" />
    <x-report.filter label="ترتيب الكشف حسب" name="sort" :options="$sorts" :selected="request('sort', 'registered_at')" :placeholder="null" />
    <x-report.filter label="اتجاه الترتيب" name="dir" :options="['desc' => 'تنازلي', 'asc' => 'تصاعدي']" :selected="request('dir', 'desc')" :placeholder="null" />
@endsection

@section('content')
    @if ($content !== 'list')
    <x-report.section class="avoid-break" no="1" title="الملخص العام">
        <x-report.figures :highlight="2" :items="[
            ['إجمالي العملاء', number_format($total), 'عميل'],
            ['العملاء الجدد خلال الفترة', number_format($summary['new']), 'عميل'],
            ['إجمالي المتبقي على العملاء', $money($activity['remaining']), 'ج.م', 'من كل العقود حتى تاريخه'],
        ]" />
        <x-report.figures :items="collect(CustomerStatus::cases())
            ->map(fn ($status) => [$status->label(), number_format($summary['statuses'][$status->value]), 'عميل', $percent($summary['statuses'][$status->value]) . ' من الإجمالي'])
            ->all()" />
    </x-report.section>

    <div class="grid grid-cols-2 gap-4 avoid-break">
        <x-report.section no="2" title="التوزيع حسب الحالة والنوع">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>الحالة</th>
                        @foreach (CustomerOrigin::cases() as $origin)
                            <th class="c">{{ $origin->label() }}</th>
                        @endforeach
                        <th class="c">الإجمالي</th>
                        <th class="c">النسبة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (CustomerStatus::cases() as $status)
                        <tr>
                            <td class="font-bold">{{ $status->label() }}</td>
                            @foreach (CustomerOrigin::cases() as $origin)
                                <td class="num c">{{ number_format($summary['matrix'][$status->value][$origin->value]) }}</td>
                            @endforeach
                            <td class="num c font-bold">{{ number_format($summary['statuses'][$status->value]) }}</td>
                            <td class="num c">{{ $percent($summary['statuses'][$status->value]) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>الإجمالي</td>
                        @foreach (CustomerOrigin::cases() as $origin)
                            <td class="num c">{{ number_format($summary['origins'][$origin->value]) }}</td>
                        @endforeach
                        <td class="num c">{{ number_format($total) }}</td>
                        <td class="num c">100%</td>
                    </tr>
                </tfoot>
            </table>
        </x-report.section>

        <x-report.section no="3" title="التوزيع الجغرافي">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>المحافظة</th>
                        <th class="c">عدد العملاء</th>
                        <th class="c">النسبة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($topGovernorates as $governorate => $count)
                        <tr>
                            <td class="font-bold">{{ $governorate }}</td>
                            <td class="num c">{{ number_format($count) }}</td>
                            <td class="num c">{{ $percent($count) }}</td>
                        </tr>
                    @empty
                        <tr><td class="is-empty" colspan="3">لا توجد بيانات</td></tr>
                    @endforelse
                    @if ($otherGovernorates)
                        <tr>
                            <td class="font-bold">محافظات أخرى</td>
                            <td class="num c">{{ number_format($otherGovernorates) }}</td>
                            <td class="num c">{{ $percent($otherGovernorates) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </x-report.section>
    </div>

    <x-report.section class="avoid-break" no="4" title="نشاط العملاء خلال الفترة">
        <x-report.figures :items="[
            ['العقود الجديدة', number_format($activity['contracts']), 'عقد'],
            ['قيمة العقود الجديدة', $money($activity['contracts_total']), 'ج.م'],
            ['الشكاوى', number_format($activity['complaints']), 'شكوى'],
        ]" />
    </x-report.section>

    @endif

    @if ($content !== 'summary')
    <x-report.section :no="$content === 'list' ? 1 : 5" title="كشف العملاء"
        :note="number_format($rows->count()) . ' عميل' . ($truncated ? ' — الكشف مقتصر على أول ' . number_format($rows->count()) . '، ضيّق الفلاتر لطباعة الباقي' : '')">
        <table class="rpt-table">
            <thead>
                <tr>
                    <th class="c">م</th>
                    <th>الكود</th>
                    <th>اسم العميل / الهاتف</th>
                    <th>العنوان</th>
                    <th class="c">النوع</th>
                    <th class="c">الحالة</th>
                    <th class="c">العقود</th>
                    <th class="l">المتبقي (ج.م)</th>
                    <th class="c">شكاوى<br>الفترة</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $customer)
                    <tr>
                        <td class="num c muted">{{ $loop->iteration }}</td>
                        <td class="num font-bold" dir="ltr">{{ $customer->code }}</td>
                        <td>
                            <p class="font-bold leading-tight">{{ $customer->name }}</p>
                            <p class="num text-[9.5px] muted text-right" dir="ltr">{{ $customer->primaryPhone?->phone }}</p>
                        </td>
                        <td class="text-[10px]">{{ collect([$customer->governorate, $customer->center, $customer->village])->filter()->join(' — ') ?: '—' }}</td>
                        <td class="c whitespace-nowrap">{{ $customer->origin->label() }}</td>
                        {{-- الحالات التي تحتاج متابعة تُميَّز بإطار، والقانونية بخلفية سوداء --}}
                        <td class="c whitespace-nowrap">
                            @if (in_array($customer->status, [CustomerStatus::Legal, CustomerStatus::Returned], true))
                                <span @class(['tag', 'is-strong' => $customer->status === CustomerStatus::Legal])>{{ $customer->status->label() }}</span>
                            @else
                                {{ $customer->status->label() }}
                            @endif
                        </td>
                        <td class="num c">{{ $customer->contracts_count }}</td>
                        <td class="num l {{ $customer->remaining_total > 0 ? 'font-black' : 'muted' }}">{{ $money((float) $customer->remaining_total) }}</td>
                        <td class="num c">{{ $customer->period_complaints_count ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td class="is-empty" colspan="9">لا يوجد عملاء مطابقون لنطاق التقرير</td></tr>
                @endforelse
            </tbody>
            @if ($rows->isNotEmpty())
                <tfoot>
                    <tr>
                        <td colspan="6">إجمالي الكشف</td>
                        <td class="num c">{{ number_format($rows->sum('contracts_count')) }}</td>
                        <td class="num l">{{ $money((float) $rows->sum('remaining_total')) }}</td>
                        <td class="num c">{{ number_format($rows->sum('period_complaints_count')) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </x-report.section>
    @endif
@endsection
