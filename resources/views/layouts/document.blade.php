<!DOCTYPE html>
<html dir="rtl" lang="ar">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <link href="{{ asset('favicon.svg') }}" rel="icon" type="image/svg+xml" />
    <title>@yield('title') — {{ $settings->get('company_name') }}</title>

    {{-- يُجبر Livewire على تحميل كامل لو وُصل لهذه الصفحة بتنقّل SPA، حتى يعمل Tailwind --}}
    <script data-navigate-track="reload" src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet" />

    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background: #ECECEC;
            color: #1a1a1a;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            background: #fff;
            padding: 14mm;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .12);
        }

        @page {
            size: A4 portrait;
            margin: 0;
        }

        @media print {
            body {
                background: transparent;
            }

            .no-print {
                display: none !important;
            }

            .page {
                box-shadow: none;
                margin: 0 !important;
            }
        }
    </style>
</head>

<body class="py-6 px-3 flex flex-col items-center gap-4">
    <div class="no-print flex items-center gap-2">
        <button class="px-5 py-2.5 rounded-xl bg-[#D4FF5B] text-[13px] font-bold shadow-sm" onclick="window.print()"
            type="button">طباعة</button>
        <a class="px-5 py-2.5 rounded-xl bg-white border border-gray-200 text-[13px] font-bold text-gray-700"
            href="@yield('back')">رجوع</a>
    </div>

    <main class="page">
        @yield('content')
    </main>
</body>

</html>
