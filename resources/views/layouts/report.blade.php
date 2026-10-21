{{-- مستند تقرير مطبوع (A4 طولي، أبيض وأسود): شريط أدوات لا يُطبع + ترويسة رسمية + المحتوى + التوقيعات.
     يحتاج من الصفحة: $period و$route (مسار التقرير) و$back (رابط الرجوع)، و$query اختياريًا
     (معاملات ثابتة تُحفظ عند تحديث التقرير). يمكن للصفحة تعريف @section('subtitle') و@section('scope')
     و@section('filters') لحقول فلترة تظهر في شريط الأدوات فقط. --}}
@php
    $query = $query ?? [];
    $issuedAt = now();
    $today = today();
    $presets = [
        'هذا الشهر' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
        'الشهر الماضي' => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
        'هذه السنة' => [$today->copy()->startOfYear(), $today->copy()->endOfYear()],
    ];
    // معاملات ثابتة تُرسل مع كل تحديث (مثل نوع التقرير)؛ الفلاتر الظاهرة في الشريط تُرسل من حقولها
    $printQuery = $query + ['print' => 1];
@endphp
<!DOCTYPE html>
<html dir="rtl" lang="ar">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <link href="{{ asset('favicon.svg') }}" rel="icon" type="image/svg+xml" />
    <title>@yield('title') — أكوا ووتر</title>

    {{-- يُجبر Livewire على تحميل كامل لو وُصل لهذه الصفحة بتنقّل SPA، حتى يعمل Tailwind --}}
    <script data-navigate-track="reload" src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />

    <style>
        :root {
            --ink: #000;
            --ink-soft: #444;
            --ink-muted: #777;
            --line: #000;
            --line-soft: #bdbdbd;
            --fill: #ececec;
            --fill-soft: #f7f7f7;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background: #d9d9d9;
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* الورقة على الشاشة بمقاس A4 الفعلي حتى يطابق العرض ما سيُطبع */
        .sheet {
            width: 210mm;
            min-height: 297mm;
            background: #fff;
            padding: 12mm 12mm 14mm;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .18);
        }

        .num {
            font-family: 'Tajawal', ui-sans-serif, system-ui, sans-serif;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        /* ===== الترويسة ===== */
        .rpt-header {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 12px;
        }

        .rpt-company {
            font-family: 'Cairo', sans-serif;
            font-size: 22px;
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: -.3px;
        }

        .rpt-tagline {
            font-size: 10px;
            font-weight: 700;
            color: var(--ink-soft);
            margin-top: 3px;
        }

        .rpt-title-box {
            border: 2px solid var(--line);
            outline: 1px solid var(--line);
            outline-offset: 2px;
            padding: 6px 22px;
            text-align: center;
        }

        .rpt-title-box h1 {
            font-family: 'Cairo', sans-serif;
            font-size: 18px;
            font-weight: 900;
            line-height: 1.3;
            white-space: nowrap;
        }

        .rpt-title-box p {
            font-size: 11px;
            font-weight: 800;
            color: var(--ink-soft);
        }

        .rpt-meta {
            justify-self: end;
            border-collapse: collapse;
            font-size: 10px;
        }

        .rpt-meta th,
        .rpt-meta td {
            border: 1px solid var(--line);
            padding: 2px 7px;
            white-space: nowrap;
        }

        .rpt-meta th {
            background: var(--fill);
            font-weight: 800;
            text-align: right;
        }

        .rpt-meta td {
            font-weight: 700;
            text-align: center;
        }

        .rpt-rule {
            margin: 10px 0 8px;
            border-top: 3px solid var(--line);
            border-bottom: 1px solid var(--line);
            height: 5px;
        }

        .rpt-scope {
            font-size: 10.5px;
            margin-bottom: 10px;
            padding: 4px 8px;
            border: 1px dashed var(--ink-muted);
        }

        /* ===== الأقسام ===== */
        .rpt-section {
            margin-top: 14px;
        }

        .rpt-section-head {
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1.5px solid var(--line);
            padding-bottom: 3px;
            margin-bottom: 6px;
            break-after: avoid;
            page-break-after: avoid;
        }

        .rpt-section-no {
            width: 20px;
            height: 20px;
            background: var(--ink);
            color: #fff;
            font-weight: 800;
            font-size: 11px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .rpt-section-title {
            font-family: 'Cairo', sans-serif;
            font-size: 13px;
            font-weight: 900;
        }

        .rpt-section-note {
            margin-right: auto;
            font-size: 9.5px;
            font-weight: 700;
            color: var(--ink-soft);
        }

        /* ===== الأرقام الرئيسية ===== */
        .rpt-figures {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .rpt-figures td {
            border: 1px solid var(--line);
            padding: 5px 8px 6px;
            vertical-align: top;
        }

        .rpt-figures td.is-highlight {
            background: var(--fill);
            border-width: 2px;
        }

        .rpt-figure-label {
            display: block;
            font-size: 9.5px;
            font-weight: 800;
            color: var(--ink-soft);
        }

        .rpt-figure-value {
            display: block;
            font-family: 'Cairo', sans-serif;
            font-size: 16px;
            font-weight: 900;
            line-height: 1.35;
            white-space: nowrap;
        }

        .rpt-figure-value small {
            font-size: 9.5px;
            font-weight: 700;
            color: var(--ink-soft);
        }

        .rpt-figure-hint {
            display: block;
            font-size: 9px;
            color: var(--ink-muted);
        }

        .rpt-figures + .rpt-figures {
            margin-top: -1px;
        }

        /* ===== الجداول ===== */
        .rpt-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }

        .rpt-table th,
        .rpt-table td {
            border: 1px solid var(--line-soft);
            padding: 4px 6px;
            vertical-align: middle;
        }

        .rpt-table thead th {
            background: var(--fill);
            border: 1px solid var(--line);
            border-bottom-width: 2px;
            font-weight: 800;
            text-align: right;
            white-space: nowrap;
        }

        .rpt-table tbody tr:nth-child(even) td {
            background: var(--fill-soft);
        }

        .rpt-table tbody td:first-child,
        .rpt-table thead th:first-child {
            border-right-color: var(--line);
        }

        .rpt-table tbody td:last-child,
        .rpt-table thead th:last-child {
            border-left-color: var(--line);
        }

        .rpt-table tbody tr:last-child td {
            border-bottom-color: var(--line);
        }

        .rpt-table tfoot td {
            border: 1px solid var(--line);
            border-top: 3px double var(--line);
            font-weight: 900;
            background: #fff;
        }

        .rpt-table .group-row td {
            background: var(--fill) !important;
            border-color: var(--line);
            font-weight: 900;
        }

        .rpt-table .is-empty {
            text-align: center;
            color: var(--ink-muted);
            padding: 14px;
        }

        .c {
            text-align: center !important;
        }

        .l {
            text-align: left !important;
        }

        .muted {
            color: var(--ink-muted);
        }

        .tag {
            display: inline-block;
            border: 1px solid var(--line);
            padding: 0 6px;
            font-size: 9.5px;
            font-weight: 800;
            white-space: nowrap;
        }

        .tag.is-strong {
            background: var(--ink);
            color: #fff;
        }

        /* ===== الخاتمة ===== */
        .rpt-signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-top: 26px;
        }

        .rpt-signatures div {
            border: 1px solid var(--line);
            padding: 6px 10px 8px;
            font-size: 10.5px;
        }

        .rpt-signatures p:first-child {
            font-weight: 900;
            border-bottom: 1px solid var(--line-soft);
            padding-bottom: 3px;
            margin-bottom: 20px;
        }

        .rpt-signatures p + p {
            color: var(--ink-muted);
            font-size: 9.5px;
            margin-top: 6px;
        }

        .rpt-end {
            text-align: center;
            font-size: 9.5px;
            font-weight: 700;
            color: var(--ink-muted);
            margin-top: 14px;
        }

        .avoid-break {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        /* ===== الشاشات الصغيرة (عرض فقط) =====
           الورقة على الشاشة بعرض A4 الثابت (210mm) تتجاوز عرض الهاتف فيُقص جانبها الأيمن.
           على الشاشة الضيقة تتمدد الورقة لعرض الشاشة، وتُرص الأقسام تحت بعضها، وتُمرَّر
           الجداول العريضة أفقيًا داخل نفسها. الطباعة لا تتأثر: كله داخل media screen. */
        @media screen and (max-width: 820px) {
            body {
                padding: 10px 8px !important;
                gap: 12px !important;
            }

            .sheet {
                width: 100%;
                min-height: 0;
                padding: 16px 12px 18px;
            }

            .rpt-header {
                grid-template-columns: 1fr;
                justify-items: center;
                text-align: center;
                gap: 10px;
            }

            .rpt-title-box h1 {
                white-space: normal;
                font-size: 17px;
            }

            .rpt-meta {
                justify-self: center;
            }

            .rpt-section-head {
                flex-wrap: wrap;
            }

            .rpt-section-note {
                margin-right: 0;
                width: 100%;
            }

            /* الأقسام المتجاورة (grid-cols-2 داخل الورقة) تُرص عموديًا */
            .sheet .grid {
                grid-template-columns: minmax(0, 1fr) !important;
            }

            /* الجداول العريضة: تمرير أفقي داخل الجدول بدل قص الصفحة */
            .rpt-table {
                display: block;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                max-width: 100%;
            }

            .rpt-table th,
            .rpt-table td {
                white-space: nowrap;
            }

            /* صفوف الأرقام الرئيسية: خليتان في كل سطر بدل صف واحد طويل */
            .rpt-figures,
            .rpt-figures tbody {
                display: block;
            }

            .rpt-figures tr {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .rpt-figures td {
                display: block;
                margin: 0 0 -1px -1px;
            }

            .rpt-figure-value {
                font-size: 15px;
                white-space: normal;
            }

            .rpt-signatures {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .rpt-signatures p:first-child {
                margin-bottom: 10px;
            }
        }

        .rpt-scroll-hint {
            font-size: 10px;
            font-weight: 700;
            color: var(--ink-muted);
            margin-bottom: 4px;
        }

        @page {
            size: A4 portrait;
            margin: 10mm 10mm 14mm;

            @bottom-right {
                content: "أكوا ووتر — @yield('title')";
                font-family: 'Tajawal', sans-serif;
                font-size: 8.5px;
                color: #555;
            }

            @bottom-left {
                content: "صفحة " counter(page) " من " counter(pages);
                font-family: 'Tajawal', sans-serif;
                font-size: 8.5px;
                color: #555;
            }
        }

        @media print {
            body {
                background: #fff;
                padding: 0 !important;
                display: block !important;
            }

            .no-print {
                display: none !important;
            }

            .sheet {
                width: auto;
                min-height: 0;
                padding: 0;
                box-shadow: none;
            }

            /* رأس الجدول يتكرر أعلى كل صفحة، والإجمالي يظهر مرة واحدة في النهاية فقط */
            .rpt-table thead {
                display: table-header-group;
            }

            .rpt-table tfoot {
                display: table-row-group;
            }

            .rpt-table tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body class="py-6 px-3 flex flex-col items-center gap-5">
    <header class="no-print w-full max-w-[210mm] bg-white rounded-lg shadow border border-gray-300">
        {{-- كل التغييرات هنا تُعيد بناء التقرير بنفس الرابط، فيبقى الرابط قابلًا للحفظ والمشاركة --}}
        <form action="{{ route($route) }}" method="GET"
            onsubmit="this.querySelectorAll('[name]').forEach(field => field.disabled = field.value === '')">
            @foreach ($printQuery as $key => $value)
                <input name="{{ $key }}" type="hidden" value="{{ $value }}" />
            @endforeach

            @hasSection('filters')
                <div class="p-3 border-b border-gray-200">
                    <p class="text-[11px] font-black text-gray-800 mb-2"><i class="fa-solid fa-sliders"></i> فلاتر التقرير</p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                        @yield('filters')
                    </div>
                </div>
            @endif

            <div class="p-3 flex flex-wrap items-end gap-2">
                <label class="flex flex-col gap-1">
                    <span class="text-[11px] font-bold text-gray-500">من</span>
                    <input class="h-9 rounded border border-gray-300 px-2 text-xs" name="from" type="date" value="{{ $period->fromDate() }}" />
                </label>
                <label class="flex flex-col gap-1">
                    <span class="text-[11px] font-bold text-gray-500">إلى</span>
                    <input class="h-9 rounded border border-gray-300 px-2 text-xs" name="to" type="date" value="{{ $period->toDate() }}" />
                </label>

                @foreach ($presets as $label => [$from, $to])
                    <a class="h-9 px-2.5 text-xs font-semibold text-gray-700 border border-gray-300 hover:bg-gray-100 rounded flex items-center"
                        href="{{ request()->fullUrlWithQuery(['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">{{ $label }}</a>
                @endforeach

                <span class="flex-1"></span>

                <button class="h-9 px-3 text-xs font-bold text-gray-800 border border-gray-400 hover:bg-gray-100 rounded flex items-center gap-1.5" type="submit">
                    <i class="fa-solid fa-rotate"></i> تحديث التقرير
                </button>
                @hasSection('filters')
                    <a class="h-9 px-3 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded flex items-center gap-1.5"
                        href="{{ route($route, $printQuery) }}">
                        <i class="fa-solid fa-eraser"></i> مسح الفلاتر
                    </a>
                @endif
                <a class="h-9 px-3 text-xs font-bold text-gray-800 border border-gray-400 hover:bg-gray-100 rounded flex items-center gap-1.5" href="{{ $back }}">
                    <i class="fa-solid fa-arrow-right"></i> رجوع
                </a>
                <button class="h-9 px-4 text-xs font-bold text-white bg-black hover:bg-gray-800 rounded flex items-center gap-2"
                    onclick="window.print()" type="button">
                    <i class="fa-solid fa-print"></i> طباعة
                </button>
            </div>
        </form>
    </header>

    <main class="sheet">
        <header class="rpt-header">
            <div>
                <p class="rpt-company">أكوا ووتر</p>
                <p class="rpt-tagline">لأنظمة تنقية المياه</p>
            </div>

            <div class="rpt-title-box">
                <h1>@yield('title')</h1>
                @hasSection('subtitle')
                    <p>@yield('subtitle')</p>
                @endif
            </div>

            <table class="rpt-meta">
                <tr>
                    <th>رقم التقرير</th>
                    <td class="num" dir="ltr">RPT-{{ $issuedAt->format('Ymd-Hi') }}</td>
                </tr>
                <tr>
                    <th>الفترة</th>
                    <td class="num" dir="ltr">{{ $period->fromDate() }} → {{ $period->toDate() }}</td>
                </tr>
                <tr>
                    <th>تاريخ الإصدار</th>
                    <td class="num" dir="ltr">{{ $issuedAt->format('Y-m-d H:i') }}</td>
                </tr>
            </table>
        </header>

        <div class="rpt-rule"></div>

        @hasSection('scope')
            <p class="rpt-scope"><b>نطاق التقرير:</b> @yield('scope')</p>
        @endif

        @yield('content')

        <footer class="rpt-signatures avoid-break">
            @foreach (['أعدّه', 'راجعه', 'اعتماد المدير'] as $role)
                <div>
                    <p>{{ $role }}</p>
                    <p>الاسم: ............................................</p>
                    <p>التوقيع: .........................................</p>
                </div>
            @endforeach
        </footer>

        <p class="rpt-end">— نهاية التقرير —</p>
    </main>

    {{-- تلميح «اسحب» فوق الجداول التي لها أعمدة مخفية فعلًا على الشاشة الضيقة فقط (لا يُطبع) --}}
    <script>
        (function () {
            function markScrollableTables() {
                document.querySelectorAll('.rpt-scroll-hint').forEach(hint => hint.remove());
                document.querySelectorAll('.rpt-table').forEach(table => {
                    if (table.scrollWidth - table.clientWidth > 4) {
                        const hint = document.createElement('p');
                        hint.className = 'rpt-scroll-hint no-print';
                        hint.textContent = '⇠ اسحب الجدول لعرض باقي الأعمدة';
                        table.before(hint);
                    }
                });
            }

            window.addEventListener('load', markScrollableTables);
            window.addEventListener('resize', markScrollableTables);
        })();
    </script>
</body>

</html>
