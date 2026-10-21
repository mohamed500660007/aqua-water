<!DOCTYPE html>
<html dir="rtl" lang="ar">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <link href="{{ asset('favicon.svg') }}" rel="icon" type="image/svg+xml" />
    <title>@yield('title') — أكوا ووتر</title>

    {{-- data-navigate-track: لو وصل المستخدم لهذه الصفحة بتنقّل Livewire (wire:navigate) من اللوحة،
         يُجبر Livewire على تحميل كامل — وإلا لا يُعاد تشغيل Tailwind فيظهر الإيصال بلا تنسيق. --}}
    <script data-navigate-track="reload" src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />

    <style>
        :root {
            --ink: #000;
            --ink-soft: #444;
            --ink-muted: #777;
            --line: #000;
            --line-soft: #c4c4c4;
            --fill: #ececec;
            --fill-soft: #f6f6f6;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background: #d9d9d9;
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .num {
            font-family: 'Tajawal', ui-sans-serif, system-ui, sans-serif;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        /* ===== الإيصال: مصمَّم على 1024×460px ويُصغَّر عند الطباعة ليصفّ ثلاثة في ورقة A4 ===== */
        .sheet {
            width: 1024px;
            height: 460px;
            background: #fff;
            display: flex;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .18);
        }

        .rc-main {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            padding: 18px 24px 14px;
        }

        .rc-header {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 12px;
        }

        .rc-company {
            font-family: 'Cairo', sans-serif;
            font-size: 26px;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -.3px;
        }

        .rc-tagline {
            font-size: 10.5px;
            font-weight: 700;
            color: var(--ink-soft);
            margin-top: 4px;
        }

        .rc-title {
            border: 2px solid var(--line);
            outline: 1px solid var(--line);
            outline-offset: 2px;
            padding: 4px 22px;
            font-family: 'Cairo', sans-serif;
            font-size: 18px;
            font-weight: 900;
            white-space: nowrap;
        }

        .rc-meta {
            justify-self: end;
            border-collapse: collapse;
            font-size: 11px;
        }

        .rc-meta th,
        .rc-meta td {
            border: 1px solid var(--line);
            padding: 3px 9px;
            white-space: nowrap;
        }

        .rc-meta th {
            background: var(--fill);
            font-weight: 800;
            text-align: right;
        }

        .rc-meta td {
            font-weight: 800;
            text-align: center;
        }

        .rc-rule {
            margin: 10px 0 10px;
            border-top: 3px solid var(--line);
            border-bottom: 1px solid var(--line);
            height: 5px;
        }

        /* جدول البيانات: خلية عنوان رمادية + خلية قيمة، بحدود واضحة كنموذج رسمي */
        .rc-fields {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .rc-fields th,
        .rc-fields td {
            border: 1px solid var(--line);
            height: 33px;
            padding: 0 8px;
            vertical-align: middle;
        }

        .rc-fields th {
            width: 96px;
            background: var(--fill);
            font-size: 11px;
            font-weight: 800;
            text-align: right;
            white-space: nowrap;
        }

        .rc-fields td {
            font-size: 13px;
            font-weight: 700;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }


        .rc-bottom {
            display: grid;
            grid-template-columns: 330px 1fr;
            gap: 18px;
            margin-top: 14px;
            align-items: stretch;
        }

        .rc-amount {
            border: 2px solid var(--line);
            display: flex;
            align-items: stretch;
            min-height: 68px;
        }

        .rc-amount-label {
            background: var(--ink);
            color: #fff;
            font-weight: 800;
            font-size: 12px;
            display: flex;
            align-items: center;
            padding: 0 12px;
            white-space: nowrap;
        }

        .rc-amount-value {
            flex: 1;
            display: flex;
            align-items: baseline;
            justify-content: center;
            align-self: center;
            gap: 6px;
            padding: 6px 10px;
            white-space: nowrap;
            font-family: 'Cairo', sans-serif;
            font-size: 26px;
            font-weight: 900;
        }

        .rc-amount-value small {
            font-family: 'Tajawal', sans-serif;
            font-size: 11px;
            font-weight: 800;
            color: var(--ink-soft);
        }

        .rc-signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .rc-signature {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            font-size: 11px;
            font-weight: 800;
            min-height: 68px;
        }

        .rc-signature span:last-child {
            border-top: 1.5px dotted var(--ink-soft);
            padding-top: 2px;
            font-size: 9.5px;
            font-weight: 600;
            color: var(--ink-muted);
            min-height: 14px;
        }

        .rc-terms {
            margin-top: 6px;
            padding: 5px 8px;
            border: 1px dashed var(--line);
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 800;
            text-align: center;
            white-space: nowrap;
        }

        .rc-footnote {
            margin-top: auto;
            padding-top: 8px;
            display: flex;
            justify-content: space-between;
            font-size: 9.5px;
            color: var(--ink-muted);
            font-weight: 600;
        }

        /* ===== خط القص بين الإيصال والكعب ===== */
        .rc-cut {
            width: 0;
            border-right: 2px dashed #8a8a8a;
            position: relative;
            margin: 12px 0;
        }

        .rc-cut span {
            position: absolute;
            top: 50%;
            right: 0;
            transform: translate(50%, -50%) rotate(-90deg);
            background: #fff;
            padding: 0 8px;
            font-size: 10.5px;
            color: #6b6b6b;
            white-space: nowrap;
        }

        /* ===== الكعب: نسخة الإدارة ===== */
        .rc-stub {
            width: 220px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            padding: 16px 14px 14px;
            background: var(--fill-soft);
        }

        .rc-stub-title {
            background: var(--ink);
            color: #fff;
            text-align: center;
            font-size: 12px;
            font-weight: 900;
            padding: 4px 0;
        }

        .rc-stub-no {
            border: 1px solid var(--line);
            border-top: 0;
            background: #fff;
            text-align: center;
            font-size: 12px;
            font-weight: 800;
            padding: 3px 0;
            margin-bottom: 8px;
        }

        .rc-stub dl {
            font-size: 11px;
        }

        .rc-stub dl div {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            border-bottom: 1px solid var(--line-soft);
            padding: 6px 0;
        }

        .rc-stub dt {
            color: var(--ink-soft);
            font-weight: 700;
            white-space: nowrap;
        }

        .rc-stub dd {
            font-weight: 800;
            text-align: left;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .rc-stub-amount {
            margin-top: 8px;
            border: 2px solid var(--line);
            background: #fff;
            text-align: center;
            padding: 4px 0;
        }

        .rc-stub-amount span {
            display: block;
            font-size: 9.5px;
            font-weight: 800;
            color: var(--ink-soft);
        }

        .rc-stub-amount b {
            font-family: 'Cairo', sans-serif;
            font-size: 17px;
            font-weight: 900;
        }

        .checkbox {
            display: inline-block;
            width: 13px;
            height: 13px;
            border: 1.5px solid var(--ink);
            vertical-align: middle;
            position: relative;
        }

        .checkbox.is-checked::after {
            content: "✓";
            position: absolute;
            inset: -4px 0 0 1px;
            font-size: 13px;
            font-weight: 900;
            line-height: 1;
        }

        /* فاصل قصّ أفقي بين إيصالات الورقة الواحدة — يظهر عند الطباعة فقط */
        .hcut {
            display: none;
        }
    </style>

    <style data-purpose="print-styles">
        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        @media print {
            html,
            body {
                width: 190mm;
                height: auto !important;
                min-height: 0 !important;
                margin: 0;
                padding: 0 !important;
                background: transparent !important;
                /* !important: صنف flex من Tailwind يُحقن بعد هذه الأنماط وكان يُوسِّط المحتوى خارج حدود الصفحة */
                display: block !important;
            }

            .no-print {
                display: none !important;
            }

            .sheets {
                display: block !important;
                gap: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: none !important;
            }

            /* مجموعة كل ثلاثة إيصالات = ورقة A4 واحدة: 1024px × 0.70 = 717px ≈ 190mm عرض المحتوى،
               و(3×460 + 2×34) × 0.70 = 1014px ≈ 268mm من 277mm المتاحة طولًا */
            .page {
                display: block !important;
                zoom: 0.70;
                break-after: page;
                page-break-after: always;
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .page:last-child {
                break-after: auto;
                page-break-after: auto;
            }

            .sheet {
                box-shadow: none !important;
                margin: 0 !important;
                overflow: hidden;
                border: 1.5px solid #000;
            }

            .hcut {
                display: block;
                height: 34px;
                position: relative;
            }

            .hcut::before {
                content: "";
                position: absolute;
                top: 50%;
                left: 0;
                right: 0;
                border-top: 2px dashed #8a8a8a;
            }

            .hcut::after {
                content: "✂ يُقص هنا";
                position: absolute;
                top: 50%;
                right: 40px;
                transform: translateY(-50%);
                background: #fff;
                padding: 0 8px;
                font-size: 11px;
                color: #6b6b6b;
            }
        }
    </style>
</head>

<body class="min-h-screen py-6 px-3 flex flex-col items-center justify-start">
    @yield('toolbar')

    <main class="sheets w-full flex flex-col items-center gap-6">
        @yield('sheets')
    </main>
</body>

</html>
