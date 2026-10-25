@extends('layouts.report', [
    'route' => 'contracts.report',
    'query' => [],
    'back' => route('contracts.index', $listQuery),
])

@use('App\Domain\Sales\Enums\ContractKind')
@use('App\Domain\Sales\Enums\ContractType')

@php
    $totals = $summary['totals'];
    $money = fn ($amount) => number_format((float) $amount, 2);
    $share = fn ($part, $whole) => ($whole > 0 ? round($part * 100 / $whole, 1) : 0) . '%';
    $byType = fn (ContractType $type) => $summary['groups']->where('type', $type);

    $scope = collect([
        'بحث' => $search,
        'نوع العقد' => isset($filters['kind']) ? ContractKind::from($filters['kind'])->label() : null,
        'طريقة الدفع' => isset($filters['type']) ? ContractType::from($filters['type'])->label() : null,
        'العميل' => $customers[$filters['customer_id'] ?? ''] ?? null,
        'المندوب' => $salesReps[$filters['sales_rep_id'] ?? ''] ?? null,
        'فني التركيب' => $installers[$filters['installer_id'] ?? ''] ?? null,
        'السداد' => ['open' => 'عليها مبالغ متبقية', 'settled' => 'مسدّدة بالكامل'][$filters['payment'] ?? ''] ?? null,
        'الحالة' => isset($filters['legal']) ? 'محوّل للشؤون القانونية' : null,
    ])->filter()->map(fn ($value, $label) => "{$label}: {$value}")->join(' — ');
@endphp

@section('title', 'تقرير عقود البيع')
@section('scope', $scope ?: 'جميع العقود المبرمة خلال الفترة')

@section('filters')
    <label class="col-span-2 flex flex-col gap-1">
        <span class="text-[11px] font-bold text-gray-500">بحث (رقم العقد، اسم أو كود العميل)</span>
        <input class="h-9 rounded border border-gray-300 px-2 text-xs" name="q" type="search" value="{{ $search }}" placeholder="اكتب للبحث..." />
    </label>
    <x-report.filter label="نوع العقد" name="kind" :options="$kinds" :selected="$filters['kind'] ?? null" />
    <x-report.filter label="طريقة الدفع" name="type" :options="$types" :selected="$filters['type'] ?? null" />
    <x-report.filter label="العميل" name="customer_id" :options="$customers" :selected="$filters['customer_id'] ?? null" />
    <x-report.filter label="مندوب المبيعات" name="sales_rep_id" :options="$salesReps" :selected="$filters['sales_rep_id'] ?? null" />
    <x-report.filter label="فني التركيب" name="installer_id" :options="$installers" :selected="$filters['installer_id'] ?? null" />
    <x-report.filter label="حالة السداد" name="payment" :options="['open' => 'عليها مبالغ متبقية', 'settled' => 'مسدّدة بالكامل']" :selected="$filters['payment'] ?? null" />
    <x-report.filter label="الشؤون القانونية" name="legal" :options="['1' => 'المحوّلة للقانونية فقط']" :selected="$filters['legal'] ?? null" />
    <x-report.filter label="محتوى التقرير" name="content" :options="$contents" :selected="$content" :placeholder="null" />
    <x-report.filter label="ترتيب الكشف حسب" name="sort" :options="$sorts" :selected="request('sort', 'contract_date')" :placeholder="null" />
    <x-report.filter label="اتجاه الترتيب" name="dir" :options="['desc' => 'تنازلي', 'asc' => 'تصاعدي']" :selected="request('dir', 'desc')" :placeholder="null" />
@endsection

@section('content')
    @if ($content !== 'list')
    <x-report.section class="avoid-break" no="1" title="الملخص العام">
        <x-report.figures :highlight="3" :items="[
            ['عدد العقود', number_format($totals['contracts']), 'عقد'],
            ['إجمالي التعاقدات', $money($totals['total']), 'ج.م', 'بعد خصم ' . $money($totals['discount']) . ' وفائدة ' . $money($totals['interest'])],
            ['المحصّل', $money($totals['paid']), 'ج.م', 'نسبة التحصيل ' . $share($totals['paid'], $totals['total'])],
            ['المتبقي للتحصيل', $money($totals['remaining']), 'ج.م'],
        ]" />
        <x-report.figures :items="[
            ['عقود نقدية', number_format($byType(ContractType::Cash)->sum('contracts')), 'عقد', 'بقيمة ' . $money($byType(ContractType::Cash)->sum('total'))],
            ['عقود تقسيط', number_format($byType(ContractType::Installment)->sum('contracts')), 'عقد', 'بقيمة ' . $money($byType(ContractType::Installment)->sum('total'))],
            ['عقود مسدّدة بالكامل', number_format($totals['settled']), 'عقد'],
            ['محوّلة للشؤون القانونية', number_format($totals['legal']), 'عقد', 'متبقٍّ عليها ' . $money($totals['legal_remaining'])],
        ]" />
    </x-report.section>

    <x-report.section class="avoid-break" no="2" title="التوزيع حسب نوع العقد وطريقة الدفع">
        <table class="rpt-table">
            <thead>
                <tr>
                    <th>نوع العقد</th>
                    <th>طريقة الدفع</th>
                    <th class="c">العقود</th>
                    <th class="l">قبل الخصم</th>
                    <th class="l">الخصم</th>
                    <th class="l">الفائدة</th>
                    <th class="l">الإجمالي</th>
                    <th class="l">المحصّل</th>
                    <th class="l">المتبقي</th>
                    <th class="c">الحصة</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($summary['groups'] as $group)
                    <tr>
                        <td class="font-bold">{{ $group['kind']->label() }}</td>
                        <td>{{ $group['type']->label() }}</td>
                        <td class="num c">{{ number_format($group['contracts']) }}</td>
                        <td class="num l">{{ $money($group['subtotal']) }}</td>
                        <td class="num l">{{ $money($group['discount']) }}</td>
                        <td class="num l">{{ $money($group['interest']) }}</td>
                        <td class="num l font-bold">{{ $money($group['total']) }}</td>
                        <td class="num l">{{ $money($group['paid']) }}</td>
                        <td class="num l font-bold">{{ $money($group['remaining']) }}</td>
                        <td class="num c">{{ $share($group['total'], $totals['total']) }}</td>
                    </tr>
                @empty
                    <tr><td class="is-empty" colspan="10">لا توجد عقود في هذه الفترة</td></tr>
                @endforelse
            </tbody>
            @if ($summary['groups']->isNotEmpty())
                <tfoot>
                    <tr>
                        <td colspan="2">الإجمالي</td>
                        <td class="num c">{{ number_format($totals['contracts']) }}</td>
                        <td class="num l">{{ $money($totals['subtotal']) }}</td>
                        <td class="num l">{{ $money($totals['discount']) }}</td>
                        <td class="num l">{{ $money($totals['interest']) }}</td>
                        <td class="num l">{{ $money($totals['total']) }}</td>
                        <td class="num l">{{ $money($totals['paid']) }}</td>
                        <td class="num l">{{ $money($totals['remaining']) }}</td>
                        <td class="num c">100%</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </x-report.section>

    <div class="grid grid-cols-2 gap-4">
        <x-report.section class="avoid-break" no="3" title="أداء مندوبي المبيعات">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>المندوب</th>
                        <th class="c">العقود</th>
                        <th class="l">الإجمالي</th>
                        <th class="l">المتبقي</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($salesRepsReport as $rep)
                        <tr>
                            <td class="font-bold">{{ $rep['name'] }}</td>
                            <td class="num c">{{ number_format($rep['contracts']) }}</td>
                            <td class="num l font-bold">{{ $money($rep['total']) }}</td>
                            <td class="num l">{{ $money($rep['remaining']) }}</td>
                        </tr>
                    @empty
                        <tr><td class="is-empty" colspan="4">لا توجد بيانات</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-report.section>

        <x-report.section class="avoid-break" no="4" title="المنتجات المباعة">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>المنتج</th>
                        <th class="c">الكمية</th>
                        <th class="l">القيمة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td class="font-bold">{{ $product->product_name }}</td>
                            <td class="num c">{{ number_format($product->quantity) }}</td>
                            <td class="num l">{{ $money($product->total) }}</td>
                        </tr>
                    @empty
                        <tr><td class="is-empty" colspan="3">لا توجد منتجات مباعة</td></tr>
                    @endforelse
                </tbody>
                @if ($products->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td>الإجمالي</td>
                            <td class="num c">{{ number_format($products->sum('quantity')) }}</td>
                            <td class="num l">{{ $money($products->sum('total')) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </x-report.section>
    </div>

    @endif

    @if ($content !== 'summary')
    <x-report.section :no="$content === 'list' ? 1 : 5" title="كشف العقود"
        :note="number_format($rows->count()) . ' عقد' . ($truncated ? ' — الكشف مقتصر على أول ' . number_format($rows->count()) . '، ضيّق الفلاتر لطباعة الباقي' : '')">
        <table class="rpt-table">
            <thead>
                <tr>
                    <th class="c">م</th>
                    <th>رقم العقد</th>
                    <th class="c">التاريخ</th>
                    <th>العميل</th>
                    <th>النوع</th>
                    <th>المندوب</th>
                    <th class="l">الإجمالي</th>
                    <th class="l">المحصّل</th>
                    <th class="l">المتبقي</th>
                    <th class="c">الحالة</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $contract)
                    <tr>
                        <td class="num c muted">{{ $loop->iteration }}</td>
                        <td class="num font-bold" dir="ltr">{{ $contract->code }}</td>
                        <td class="num c" dir="ltr">{{ $contract->contract_date?->format('Y-m-d') }}</td>
                        <td>
                            <p class="font-bold leading-tight">{{ $contract->customer?->name ?? '—' }}</p>
                            <p class="num text-[9.5px] muted text-right" dir="ltr">{{ $contract->customer?->code }}</p>
                        </td>
                        <td class="whitespace-nowrap text-[10px]">{{ $contract->kind->label() }}<br><span class="muted">{{ $contract->type->label() }}</span></td>
                        <td class="text-[10px]">{{ $contract->salesRep?->name ?? '—' }}</td>
                        <td class="num l">{{ $money($contract->total) }}</td>
                        <td class="num l">{{ $money($contract->paid) }}</td>
                        <td class="num l {{ $contract->remaining > 0 ? 'font-black' : 'muted' }}">{{ $money($contract->remaining) }}</td>
                        <td class="c whitespace-nowrap">
                            @if ($contract->isReferredToLegal())
                                <span class="tag is-strong">قانونية</span>
                            @elseif ($contract->remaining <= 0)
                                <span class="tag">مسدّد</span>
                            @else
                                جاري
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td class="is-empty" colspan="10">لا توجد عقود مطابقة لنطاق التقرير</td></tr>
                @endforelse
            </tbody>
            @if ($rows->isNotEmpty())
                <tfoot>
                    <tr>
                        <td colspan="6">إجمالي الكشف</td>
                        <td class="num l">{{ $money($rows->sum('total')) }}</td>
                        <td class="num l">{{ $money($rows->sum('paid')) }}</td>
                        <td class="num l">{{ $money($rows->sum('remaining')) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </x-report.section>
    @endif
@endsection
