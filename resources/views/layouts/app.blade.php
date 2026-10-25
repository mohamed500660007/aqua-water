<!DOCTYPE html>

<html dir="rtl" lang="ar">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, viewport-fit=cover" name="viewport" />
    <meta content="#FAFAFA" name="theme-color" />

    <link href="{{ asset('favicon.svg') }}" rel="icon" type="image/svg+xml" />

    <title>@yield('title', 'لوحة التحكم') — {{ $settings->get('company_name') }}</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/lucide@0.454.0/dist/umd/lucide.min.js"></script>

    <style data-purpose="custom-styles">
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap');

        body {
            font-family: 'Cairo', sans-serif;
            background-color: #B494F7;
            -webkit-tap-highlight-color: transparent;
        }

        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }

        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .brand-green {
            background-color: #D4FF5B;
            color: #1A1A1A;
        }

        /* الانتقائية مرفوعة عمدًا (صنفان) حتى تتغلّب دائمًا على أدوات Tailwind
           مثل text-gray-400 — فورقة أنماط Tailwind تُحقن وقت التشغيل بعد هذه،
           وبانتقائية متساوية كان اللون الرمادي يفوز فيفقد العنصر النشط لونه. */
        .nav-item.nav-item-active,
        a.nav-item-active,
        button.nav-item-active {
            background-color: #D4FF5B;
            color: #1A1A1A;
        }

        .nav-item:hover:not(.nav-item-active) {
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* ===== تناسق التبويب مع فتح الصفحة =====
           لحظة الضغط: التبويب الجديد يُعلَّم «قيد الفتح» بلون خافت ينبض، والمحتوى الحالي
           يبهت. عند وصول الصفحة: يكتمل لون التبويب وتظهر الصفحة بنفس المدة والمنحنى —
           فيبدو الاثنان حركة واحدة بدل تبويب يسبق صفحة متأخرة. */
        .nav-item-active {
            transition: background-color .3s cubic-bezier(.4, 0, .2, 1), color .3s cubic-bezier(.4, 0, .2, 1);
        }

        html.is-navigating .nav-item-active {
            background-color: rgba(212, 255, 91, .35);
            animation: nav-pending 1.1s ease-in-out infinite;
        }

        #appContent {
            animation: page-enter .3s cubic-bezier(.4, 0, .2, 1) backwards;
        }

        html.is-navigating #appContent {
            opacity: .35;
            transform: translateY(4px);
            pointer-events: none;
            transition: opacity .2s ease, transform .2s ease;
        }

        @keyframes nav-pending {
            50% { background-color: rgba(212, 255, 91, .18); }
        }

        /* تبدأ الصفحة الجديدة من نفس درجة البهتان التي انتهت عندها القديمة — بلا ومضة فراغ */
        @keyframes page-enter {
            from { opacity: .35; transform: translateY(4px); }
            to { opacity: 1; transform: none; }
        }

        /* ===== مؤشر التحميل: قطرة المياه =====
           يغطي منطقة المحتوى فقط (لا شريط التنقل)، ويظهر بعد 250ms من بدء التنقّل
           حتى لا يومض في الصفحات التي تفتح فورًا. */
        .page-loader {
            position: fixed;
            inset: 0;
            z-index: 25;
            display: grid;
            place-items: center;
            pointer-events: none;
            opacity: 0;
            visibility: hidden;
            transition: opacity .2s ease, visibility 0s linear .2s;
        }

        /* على سطح المكتب يبدأ أسفل الصف العلوي (سطران تحت 1280px: الشريط ثم العنوان والأزرار) */
        @media (min-width: 1024px) {
            .page-loader {
                top: 146px;
            }
        }

        @media (min-width: 1280px) {
            .page-loader {
                top: 76px;
            }
        }

        html.is-navigating .page-loader {
            opacity: 1;
            visibility: visible;
            transition: opacity .3s ease .25s, visibility 0s linear .25s;
        }

        .page-loader-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .75rem;
            padding: 1.5rem 2rem 1.25rem;
            border-radius: 1.75rem;
            background: rgba(255, 255, 255, .92);
            box-shadow: 0 20px 45px -12px rgba(2, 132, 199, .28), 0 0 0 1px rgba(2, 132, 199, .06);
            backdrop-filter: blur(6px);
            transform: translateY(6px) scale(.96);
            transition: transform .3s cubic-bezier(.4, 0, .2, 1);
        }

        html.is-navigating .page-loader-card {
            transform: none;
            transition-delay: .25s;
        }

        .page-loader-drop {
            position: relative;
            display: grid;
            place-items: center;
            animation: drop-float 2.4s ease-in-out infinite;
        }

        .page-loader-level {
            animation: drop-fill 2.4s cubic-bezier(.45, 0, .55, 1) infinite alternate;
        }

        .page-loader-wave {
            animation: drop-wave 1.2s linear infinite;
        }

        .page-loader-wave--back {
            animation-duration: 1.8s;
            animation-direction: reverse;
        }

        .page-loader-ripple {
            position: absolute;
            bottom: -10px;
            width: 44px;
            height: 10px;
            border-radius: 50%;
            border: 2px solid rgba(2, 132, 199, .45);
            opacity: 0;
            animation: drop-ripple 2.4s ease-out infinite;
        }

        .page-loader-ripple--late {
            animation-delay: 1.2s;
        }

        .page-loader-text {
            font-size: 12px;
            font-weight: 700;
            color: #0369A1;
            letter-spacing: .02em;
        }

        @keyframes drop-fill {
            from { transform: translateY(74px); }
            to { transform: translateY(12px); }
        }

        /* طول موجة واحدة 32 وحدة — الإزاحة بمقدارها تجعل الحركة متصلة بلا قفزة */
        @keyframes drop-wave {
            from { transform: translateX(0); }
            to { transform: translateX(32px); }
        }

        @keyframes drop-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }

        @keyframes drop-ripple {
            0% { opacity: .9; transform: scale(.4); }
            100% { opacity: 0; transform: scale(1.6); }
        }

        @media (prefers-reduced-motion: reduce) {
            .page-loader-drop, .page-loader-wave, .page-loader-ripple { animation: none; }
            .page-loader-level { animation: none; transform: translateY(40px); }
            #appContent, html.is-navigating .nav-item-active { animation: none; }
            html.is-navigating #appContent { transform: none; }
        }

        .circular-chart {
            display: block;
            margin: 0 auto;
            max-width: 80%;
            max-height: 250px;
        }

        .circle-bg {
            fill: none;
            stroke: #eee;
            stroke-width: 3.8;
        }

        .circle {
            fill: none;
            stroke-width: 3.8;
            stroke-linecap: round;
            animation: progress 1s ease-out forwards;
        }

        @keyframes progress {
            0% {
                stroke-dasharray: 0 100;
            }
        }

        /* --- سلوك تطبيق الهاتف --- */
        .bottom-nav-safe {
            bottom: calc(0.75rem + env(safe-area-inset-bottom, 0px));
        }

        .pb-safe-sheet {
            padding-bottom: calc(0.75rem + env(safe-area-inset-bottom, 0px));
        }

        .pb-app {
            padding-bottom: calc(7rem + env(safe-area-inset-bottom, 0px));
        }

        @media (min-width: 1024px) {
            .pb-app {
                padding-bottom: 2rem;
            }
        }

        .snap-row {
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
        }

        .snap-row>* {
            scroll-snap-align: start;
        }

        /* هيكل التحميل (skeleton) */
        @keyframes shimmer {
            100% {
                transform: translateX(-100%);
            }
        }

        .skeleton {
            position: relative;
            overflow: hidden;
            background-color: #F1F3F5;
        }

        .skeleton::after {
            content: '';
            position: absolute;
            inset: 0;
            transform: translateX(100%);
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .65), transparent);
            animation: shimmer 1.4s infinite;
        }

        /* إخفاء عناصر Alpine قبل التهيئة */
        [x-cloak] {
            display: none !important;
        }

        /* منع أي انزلاق أفقي غير مقصود على مستوى التطبيق بالكامل */
        html, body {
            overflow-x: hidden;
            max-width: 100%;
        }

        /* عناصر @@persist شفافة تمامًا للتخطيط حتى لا تكسر flex/grid المحيط بها */
        [x-persist] {
            display: contents;
        }

        /* حالة تحميل زر الإرسال أثناء تنفيذ عملية CRUD */
        button[data-submitting] {
            pointer-events: none;
        }

        .spin {
            animation: spin 0.7s linear infinite;
        }

        /* ظهور متتابع (staggered) لبطاقات قائمة «المزيد» على الهاتف —
           كل بطاقة تنطّ بعد التي قبلها بفارق بسيط فيبدو الفتح حيًّا لا دفعة واحدة.
           backwards تُبقيها مخفية أثناء فترة التأخير قبل بدء الحركة. */

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }


        /* ===== فلاتر القوائم =====
           سكربت initializeMobileFilters يعيد ترتيب نموذج الفلترة على كل الشاشات (زر «فلترة» + لوحة).
           حتى 1023px (الهاتف والتابلت — بلا شريط جانبي) تظهر الفلاتر في لوحة منبثقة من الأسفل،
           ومن 1024px تعود صفًا واحدًا كما كانت: اللوحة وجسمها display: contents فتصير
           عناصرها أبناء مباشرين لصف النموذج، ويختفي الزر والعنوان والخلفية. */
        @media (max-width: 1023px) {
            /* البحث يبقى ظاهرًا، وباقي الفلاتر داخل bottom sheet بيد واحدة. */
            .mobile-filter-form {
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) auto;
                align-items: center !important;
                gap: .65rem !important;
                padding: .75rem !important;
            }

            .mobile-filter-trigger {
                display: inline-flex;
                min-width: 48px;
                height: 48px;
                align-items: center;
                justify-content: center;
                gap: .4rem;
                padding-inline: .8rem;
                border: 1px solid #e5e7eb;
                border-radius: .8rem;
                background: #fff;
                color: #374151;
                font-size: 13px;
                font-weight: 700;
            }

            .mobile-filter-count {
                display: grid;
                min-width: 20px;
                height: 20px;
                place-items: center;
                border-radius: 999px;
                background: #d4ff5b;
                color: #1a1a1a;
                font-size: 11px;
            }

            .mobile-filter-backdrop {
                position: fixed;
                inset: 0;
                z-index: 54;
                display: none;
                width: 100%;
                height: 100%;
                border: 0;
                background: rgba(15, 23, 42, .48);
                backdrop-filter: blur(2px);
            }

            .mobile-filter-sheet {
                position: fixed;
                inset-inline: 0;
                bottom: 0;
                z-index: 55;
                display: none;
                max-height: min(82dvh, 680px);
                padding: 1rem 1rem calc(1rem + env(safe-area-inset-bottom, 0px));
                overflow-y: auto;
                border-radius: 1.5rem 1.5rem 0 0;
                background: #fff;
                box-shadow: 0 -20px 50px -24px rgba(15, 23, 42, .45);
            }

            .mobile-filter-form.is-open .mobile-filter-backdrop,
            .mobile-filter-form.is-open .mobile-filter-sheet {
                display: block;
            }

            .mobile-filter-sheet-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: .9rem;
            }

            .mobile-filter-sheet-body {
                display: grid;
                gap: .75rem;
            }

            .mobile-filter-sheet-body > * {
                width: 100% !important;
            }

        }

        @media (min-width: 1024px) {
            .mobile-filter-trigger,
            .mobile-filter-backdrop,
            .mobile-filter-sheet-head {
                display: none !important;
            }

            .mobile-filter-sheet,
            .mobile-filter-sheet-body {
                display: contents !important;
            }

            /* بجوار الشريط الجانبي (≈700px عند 1024) لا يتسع صف واحد لكل الفلاتر:
               يلتف الصف، ويأخذ البحث سطرًا كاملًا إن لزم بدل أن يُدفع خارج الشاشة. */
            .mobile-filter-form {
                flex-wrap: wrap !important;
            }

            .mobile-filter-form > .relative:has([name="q"]) {
                flex: 1 1 280px;
                min-width: 0;
            }
        }

        /* الأكواد والأرقام والتواريخ (dir=ltr) داخل الجداول لا تُكسر على أكثر من سطر في الأعمدة الضيقة */
        #appContent td[dir="ltr"],
        #appContent td [dir="ltr"] {
            white-space: nowrap;
        }

        /* ===== تجربة الهاتف الموحدة ===== */
        @media (max-width: 767px) {
            .pb-app {
                padding-bottom: calc(6.5rem + env(safe-area-inset-bottom, 0px));
            }

            .bottom-nav-safe {
                bottom: calc(.375rem + env(safe-area-inset-bottom, 0px));
            }

            #appMain {
                scroll-padding-bottom: calc(7rem + env(safe-area-inset-bottom, 0px));
            }

            /* حد قراءة عملي لكل النصوص الوظيفية الصغيرة. */
            #appMain [class*="text-[9.5px]"],
            #appMain [class*="text-[10px]"],
            #appMain [class*="text-[10.5px]"],
            #appMain [class*="text-[11px]"],
            #bottomNav [class*="text-[9.5px]"],
            #bottomNav [class*="text-[10px]"] {
                font-size: 12px !important;
                line-height: 1.45 !important;
            }

            #appMain input:not([type="hidden"]),
            #appMain textarea,
            #appMain select {
                font-size: 16px !important;
            }

            #appMain .text-gray-400,
            #bottomNav .text-gray-400 {
                color: #667085 !important;
            }

            #appMain :focus-visible,
            #bottomNav :focus-visible {
                outline: 3px solid #84cc16;
                outline-offset: 2px;
            }

            #appMain button,
            #appMain [role="button"],
            #appMain a.inline-flex {
                min-height: 44px;
            }

            /* تتحول الجداول التشغيلية إلى بطاقات كاملة، بلا تمرير أفقي مخفي. */
            .mobile-table-shell {
                overflow: visible !important;
                padding: .75rem;
                background: #f8fafc;
            }

            .mobile-card-table {
                display: block;
                width: 100% !important;
                min-width: 0 !important;
            }

            .mobile-card-table thead {
                position: absolute;
                width: 1px;
                height: 1px;
                padding: 0;
                margin: -1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                white-space: nowrap;
                border: 0;
            }

            .mobile-card-table tbody {
                display: grid;
                gap: .75rem;
            }

            .mobile-card-table tbody > tr {
                display: grid;
                width: 100%;
                overflow: hidden;
                border: 1px solid #e5e7eb;
                border-radius: 1rem;
                background: #fff;
                box-shadow: 0 4px 14px -10px rgba(15, 23, 42, .28);
            }

            .mobile-card-table tbody > tr > td {
                display: grid;
                grid-template-columns: minmax(88px, 34%) minmax(0, 1fr);
                align-items: center;
                gap: .75rem;
                min-height: 48px;
                padding: .65rem .8rem !important;
                border-bottom: 1px solid #f1f5f9;
                text-align: start !important;
                white-space: normal !important;
            }

            .mobile-card-table tbody > tr > td:last-child {
                border-bottom: 0;
            }

            .mobile-card-table tbody > tr > td::before {
                content: attr(data-label);
                align-self: start;
                padding-top: .15rem;
                color: #64748b;
                font-size: 12px;
                font-weight: 700;
                line-height: 1.4;
            }

            .mobile-card-table tbody > tr > td:first-child {
                background: #fbfdf5;
            }

            .mobile-card-table tbody > tr > td[colspan] {
                display: block;
                text-align: center !important;
            }

            .mobile-card-table tbody > tr > td[colspan]::before {
                content: none;
            }

            .mobile-card-table td > .flex,
            .mobile-card-table td > form,
            .mobile-card-table td a,
            .mobile-card-table td button {
                min-width: 0;
            }

            .mobile-card-table td a,
            .mobile-card-table td button {
                min-height: 44px;
            }

            .mobile-card-table td:last-child > .flex {
                justify-content: flex-start !important;
                gap: .5rem;
            }

            /* مؤشرات الأداء تظهر كسطر سحب مضغوط بدل دفع النتائج لأسفل. */
            .mobile-kpi-row {
                display: flex !important;
                gap: .65rem !important;
                overflow-x: auto;
                padding-bottom: .35rem;
                scroll-snap-type: x mandatory;
                scrollbar-width: none;
            }

            .mobile-kpi-row::-webkit-scrollbar { display: none; }
            .mobile-kpi-row > * {
                flex: 0 0 min(72vw, 250px);
                scroll-snap-align: start;
            }

            /* إجراء أساسي واضح، والإجراءات الثانوية داخل قائمة المزيد. */
            .mobile-page-actions {
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: .65rem !important;
            }

            .mobile-page-actions > :not(.mobile-actions-more) {
                width: 100%;
                min-height: 48px;
            }

            .mobile-actions-more { position: relative; }
            .mobile-actions-more > summary {
                display: flex;
                width: 48px;
                height: 48px;
                align-items: center;
                justify-content: center;
                border: 1px solid #e5e7eb;
                border-radius: .8rem;
                background: #fff;
                cursor: pointer;
                list-style: none;
                font-size: 22px;
                font-weight: 700;
            }
            .mobile-actions-more > summary::-webkit-details-marker { display: none; }
            .mobile-actions-menu {
                position: absolute;
                z-index: 35;
                top: calc(100% + .5rem);
                inset-inline-end: 0;
                display: grid;
                width: min(78vw, 290px);
                gap: .5rem;
                padding: .6rem;
                border: 1px solid #e5e7eb;
                border-radius: 1rem;
                background: #fff;
                box-shadow: 0 20px 40px -18px rgba(15, 23, 42, .4);
            }
            .mobile-actions-menu > * { width: 100%; min-height: 48px; }

            /* شريط حفظ النماذج الطويلة يثبت فوق شريط التطبيق ومساحة الأمان. */
            .mobile-sticky-submit {
                position: sticky;
                z-index: 20;
                bottom: calc(5.55rem + env(safe-area-inset-bottom, 0px));
                margin-inline: -.25rem;
                padding: .65rem;
                border: 1px solid rgba(229, 231, 235, .9);
                border-radius: 1rem;
                background: rgba(255, 255, 255, .94);
                box-shadow: 0 12px 30px -18px rgba(15, 23, 42, .55);
                backdrop-filter: blur(8px);
            }

            .mobile-sticky-submit button[type="submit"] {
                min-height: 48px;
            }
        }
    </style>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            green: '#D4FF5B',
                            dark: '#1A1A1A',
                            light: '#F8F9FA',
                            purple: '#B494F7'
                        }
                    },
                    fontFamily: {
                        sans: ['Cairo', 'sans-serif']
                    }
                }
            }
        }
    </script>

    <script data-purpose="alpine-helpers">
        // ===== مخازن الحالة العامة (Alpine stores) =====
        document.addEventListener('alpine:init', function () {

            // رسائل التنبيه المنبثقة
            Alpine.store('toast', {
                items: [],
                push(message, type) {
                    const id = Date.now() + Math.random();
                    this.items.push({id: id, message: message, type: type || 'success'});
                    setTimeout(() => this.remove(id), 3800);
                },
                success(m) {this.push(m, 'success');},
                error(m) {this.push(m, 'error');},
                info(m) {this.push(m, 'info');},
                remove(id) {this.items = this.items.filter(t => t.id !== id);}
            });

            // النوافذ المنبثقة
            Alpine.store('modal', {
                open: null,
                show(name) {this.open = name;},
                hide() {this.open = null;},
                is(name) {return this.open === name;}
            });

            // نموذج الإضافة/التعديل
            Alpine.store('editor', {
                open: false,
                mode: 'create',
                data: {},
                create(defaults) {
                    this.mode = 'create';
                    this.data = Object.assign({}, defaults || {});
                    this.open = true;
                },
                edit(row) {
                    this.mode = 'edit';
                    this.data = Object.assign({}, row || {});
                    this.open = true;
                },
                close() {this.open = false;}
            });

            // نافذة عرض التفاصيل
            Alpine.store('viewer', {
                open: false,
                data: {},
                show(row) {
                    this.data = Object.assign({}, row || {});
                    this.open = true;
                },
                close() {this.open = false;}
            });

            // نافذة تأكيد الحذف
            Alpine.store('confirm', {
                open: false,
                title: '',
                message: '',
                label: 'تأكيد',
                onOk: null,
                ask(options) {
                    this.title = options.title || 'تأكيد الإجراء';
                    this.message = options.message || 'هل أنت متأكد من تنفيذ هذا الإجراء؟';
                    this.label = options.label || 'تأكيد';
                    this.onOk = options.onOk || null;
                    this.open = true;
                },
                accept() {
                    const fn = this.onOk;
                    this.open = false;
                    this.onOk = null;
                    if (typeof fn === 'function') fn();
                },
                close() {
                    this.open = false;
                    this.onOk = null;
                }
            });

            // تتبّع المسار الحالي على العميل — يحدّد العنصر النشط في كل عناصر التنقل
            // بدل الاعتماد على إعادة رسم الخادم، حتى تبقى هذه العناصر محفوظة (persist)
            // بين صفحة وأخرى بلا وميض عند التنقل.
            //
            // القاعدة: العنصر النشط هو صاحب "أطول مسار مطابق" — فصفحة
            // /dashboard/contracts/create تُفعّل «عقد جديد» وحدها لا «عقود البيع» معها.
            Alpine.store('nav', {
                path: '/',
                hrefs: [],

                init() {
                    this.refresh();
                },

                normalize(href) {
                    try {
                        return new URL(href, window.location.origin).pathname.replace(/\/+$/, '') || '/';
                    } catch (e) {
                        return String(href || '/');
                    }
                },

                refresh() {
                    this.path = window.location.pathname.replace(/\/+$/, '') || '/';
                    // تُجمع المسارات من عناصر التنقل الموجودة فعلًا في الصفحة
                    this.hrefs = Array.from(document.querySelectorAll('[data-nav-href]'))
                        .map(el => this.normalize(el.getAttribute('data-nav-href')));
                },

                get bestMatch() {
                    let best = '';
                    this.hrefs.forEach(href => {
                        const matches = this.path === href || this.path.startsWith(href + '/');
                        if (matches && href.length > best.length) best = href;
                    });
                    return best;
                },

                isActive(href) {
                    return this.normalize(href) === this.bestMatch;
                },

                isActiveAny(hrefs) {
                    return (hrefs || []).some(href => this.isActive(href));
                }
            });

            // اللوحة الجانبية (x-ui.drawer): تجلب HTML من الخادم عند الفتح.
            // url يميّز الطلب الأحدث حتى لا يكتب ردّ طلب قديم فوق لوحة فُتحت بعده.
            Alpine.store('drawer', {
                open: false,
                loading: false,
                failed: false,
                html: '',
                url: null,
                async show(url) {
                    this.url = url;
                    this.open = true;
                    this.loading = true;
                    this.failed = false;
                    this.html = '';

                    try {
                        const response = await fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html'}});
                        if (!response.ok) throw new Error(response.status);
                        const html = await response.text();
                        if (this.url === url) this.html = html;
                    } catch (e) {
                        if (this.url === url) this.failed = true;
                    } finally {
                        if (this.url === url) this.loading = false;
                    }
                },
                reload() {
                    if (this.url) this.show(this.url);
                },
                close() {
                    this.open = false;
                    this.url = null;
                }
            });

            // لوحة «المزيد» على الهاتف — تنبثق من شريط التنقل السفلي نفسه.
            Alpine.store('more', {
                open: false,
                show() { this.open = true; },
                close() { this.open = false; },
                toggle() { this.open ? this.close() : this.show(); }
            });
        });

        // تحديث العنصر النشط في التنقل + إعادة رسم الأيقونات والرسوم البيانية
        // بعد كل انتقال SPA عبر Livewire (لا يحدث DOMContentLoaded من جديد)
        document.addEventListener('livewire:navigated', function () {
            document.documentElement.classList.remove('is-navigating');
            if (window.Alpine && Alpine.store('nav')) Alpine.store('nav').refresh();

            if (window.Alpine && Alpine.store('more')) Alpine.store('more').close();
            if (window.Alpine && Alpine.store('drawer')) Alpine.store('drawer').close();

            renderIcons();
            initCharts();
            initializeMobileExperience(document);
        });

        // ===== الانتقال بين الصفحات بلا إعادة تحميل كاملة (نماذج البحث والفلاتر) =====
        function navigateForm(form) {
            if (!form) return;
            const params = new URLSearchParams(new FormData(form));
            // إزالة الحقول الفارغة حتى يبقى رابط الصفحة نظيفًا وقابلًا للمشاركة
            Array.from(params.keys()).forEach(key => {
                if (!params.get(key)) params.delete(key);
            });
            const query = params.toString();
            const url = form.getAttribute('action') + (query ? '?' + query : '');
            Alpine.navigate(url);
        }
        window.navigateForm = navigateForm;

        // ===== حالة تحميل موحّدة لأي نموذج CRUD عند الإرسال =====
        // تُطبَّق تلقائيًا على كل النماذج دون الحاجة لتعديل كل صفحة على حدة.
        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!(form instanceof HTMLFormElement)) return;
            // لا شيء لعمله إذا منع التحقق الفوري (Alpine) الإرسال فعليًا
            if (e.defaultPrevented) return;
            if (form.hasAttribute('data-no-loading')) return;

            const button = form.querySelector('button[type="submit"]');
            if (!button || button.hasAttribute('data-submitting')) return;

            button.setAttribute('data-submitting', '1');
            button.disabled = true;
            button.dataset.originalHtml = button.innerHTML;
            button.innerHTML = `
                <svg class="w-[18px] h-[18px] spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                    <path class="opacity-90" d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-linecap="round" stroke-width="3"></path>
                </svg>
                <span>جارٍ التنفيذ...</span>
            `;
        });

        // ===== جدول بيانات: بحث + فلاتر + ترتيب + ترقيم صفحات =====
        function dataTable(config) {
            config = config || {};
            return {
                q: '',
                filters: config.filters || {},
                sortKey: config.sortKey || null,
                sortDir: config.sortDir || 'asc',
                page: 1,
                perPage: config.perPage || 8,
                rows: [],
                total: 0,
                pages: 1,
                from: 0,
                to: 0,
                loading: false,

                init() {
                    this.syncRows();
                    this.$watch('q', () => {this.page = 1; this.apply();});
                },

                syncRows() {
                    const body = this.$refs.body;
                    if (!body) return;
                    this.rows = Array.from(body.querySelectorAll('[data-row]')).map(el => ({
                        el: el,
                        text: (el.dataset.search || el.textContent || '').toLowerCase()
                    }));
                    this.apply();
                },

                // إظهار حالة التحميل قبل تطبيق الفلاتر
                refresh() {
                    this.page = 1;
                    this.loading = true;
                    setTimeout(() => {
                        this.apply();
                        this.loading = false;
                    }, 320);
                },

                sortBy(key) {
                    if (this.sortKey === key) {
                        this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
                    } else {
                        this.sortKey = key;
                        this.sortDir = 'asc';
                    }
                    this.apply();
                },

                sortIcon(key) {
                    if (this.sortKey !== key) return 'opacity-0 group-hover:opacity-40';
                    return this.sortDir === 'asc' ? 'opacity-100 rotate-180' : 'opacity-100';
                },

                resetFilters() {
                    this.q = '';
                    for (const k in this.filters) this.filters[k] = '';
                    this.refresh();
                },

                get hasFilters() {
                    if (this.q) return true;
                    for (const k in this.filters) if (this.filters[k]) return true;
                    return false;
                },

                matches(row) {
                    const q = this.q.trim().toLowerCase();
                    if (q && row.text.indexOf(q) === -1) return false;
                    for (const key in this.filters) {
                        const val = this.filters[key];
                        if (val && row.el.dataset[key] !== val) return false;
                    }
                    return true;
                },

                apply() {
                    const list = this.rows.filter(r => this.matches(r));

                    if (this.sortKey) {
                        const dir = this.sortDir === 'asc' ? 1 : -1;
                        const key = this.sortKey;
                        list.sort((a, b) => {
                            const av = a.el.dataset[key] || '';
                            const bv = b.el.dataset[key] || '';
                            const an = parseFloat(av), bn = parseFloat(bv);
                            if (!isNaN(an) && !isNaN(bn) && av !== '' && bv !== '') return (an - bn) * dir;
                            return String(av).localeCompare(String(bv), 'ar') * dir;
                        });
                    }

                    this.total = list.length;
                    this.pages = Math.max(1, Math.ceil(this.total / this.perPage));
                    if (this.page > this.pages) this.page = this.pages;

                    const start = (this.page - 1) * this.perPage;
                    const slice = list.slice(start, start + this.perPage);

                    this.rows.forEach(r => r.el.classList.add('hidden'));
                    slice.forEach(r => r.el.classList.remove('hidden'));

                    // إعادة الترتيب داخل الجدول
                    const body = this.$refs.body;
                    if (body) list.forEach(r => body.appendChild(r.el));

                    this.from = this.total ? start + 1 : 0;
                    this.to = start + slice.length;
                },

                go(p) {
                    if (p < 1 || p > this.pages) return;
                    this.page = p;
                    this.apply();
                },

                get pageList() {
                    const out = [];
                    for (let i = 1; i <= this.pages; i++) out.push(i);
                    return out;
                },

                // حذف صف بصريًا (بدون خادم)
                removeRow(el, message) {
                    if (el) el.remove();
                    this.syncRows();
                    Alpine.store('toast').success(message || 'تم الحذف بنجاح');
                }
            };
        }

        // ===== قائمة منسدلة منسّقة تعمل فوق قائمة <select> أصلية مخفية =====
        function styledSelect() {
            return {
                open: false,
                value: '',
                label: '',
                options: [],

                init() {
                    const native = this.$refs.native;
                    this.options = Array.from(native.options).map(o => ({value: o.value, label: o.text}));
                    this.sync();

                    // Alpine (x-model) يغيّر قيمة القائمة الأصلية برمجيًا بدون إطلاق حدث،
                    // لذلك نعترض الـ setter حتى يبقى النص الظاهر مطابقًا للقيمة المحفوظة.
                    const descriptor = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
                    Object.defineProperty(native, 'value', {
                        get: () => descriptor.get.call(native),
                        set: v => {descriptor.set.call(native, v); this.sync();}
                    });

                    native.addEventListener('change', () => this.sync());
                },

                sync() {
                    const selected = this.$refs.native.selectedOptions[0];
                    this.value = this.$refs.native.value;
                    this.label = selected ? selected.text : '';
                },

                choose(value) {
                    const native = this.$refs.native;
                    native.value = value;
                    native.dispatchEvent(new Event('input', {bubbles: true}));
                    native.dispatchEvent(new Event('change', {bubbles: true}));
                    this.open = false;
                }
            };
        }

        // ===== قراءة بيانات الصف/البطاقة من سمة data-json =====
        function rowData(el) {
            const host = el.closest('[data-json]');
            if (!host) return {};
            try { return JSON.parse(host.dataset.json); } catch (e) { return {}; }
        }
        window.rowData = rowData;

        // ===== رسم المخططات من وسم JSON =====
        function initCharts() {
            if (!window.Chart) return;
            document.querySelectorAll('canvas[data-chart]').forEach(function (canvas) {
                if (canvas.dataset.rendered) return;
                const src = document.getElementById(canvas.dataset.chart);
                if (!src) return;
                const cfg = JSON.parse(src.textContent);

                if (cfg.options && cfg.options.plugins && cfg.options.plugins.tooltip) {
                    cfg.options.plugins.tooltip.rtl = true;
                    cfg.options.plugins.tooltip.textDirection = 'rtl';
                }
                new Chart(canvas.getContext('2d'), cfg);
                canvas.dataset.rendered = '1';
            });
        }

        // ===== نظام الأيقونات الموحّد =====
        function renderIcons() {
            if (window.lucide) {
                lucide.createIcons({
                    attrs: {'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round'}
                });
            }
        }
        window.renderIcons = renderIcons;

        // ===== تحسينات الهاتف العامة: بطاقات الجداول، فلاتر، إجراءات وتواريخ عربية =====
        function initializeMobileTables(root) {
            (root || document).querySelectorAll('#appContent table:not(.rpt-table):not(.rpt-figures)').forEach(table => {
                if (table.dataset.mobileReady === '1') return;
                const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.innerText.replace(/\s+/g, ' ').trim());
                if (!headers.length) return;
                table.classList.add('mobile-card-table');
                table.parentElement?.classList.add('mobile-table-shell');
                table.querySelectorAll('tbody tr').forEach(row => {
                    Array.from(row.children).forEach((cell, index) => {
                        if (cell.tagName === 'TD' && !cell.hasAttribute('colspan')) cell.dataset.label = headers[index] || 'بيان';
                    });
                });
                table.dataset.mobileReady = '1';
            });
        }

        function initializeMobileFilters(root) {
            (root || document).querySelectorAll('#appContent form[method="GET"], #appContent form[method="get"]').forEach(form => {
                if (form.dataset.mobileFilters === '1' || !form.querySelector('[name="q"]') || !form.querySelector('select')) return;
                const search = form.querySelector('[name="q"]')?.closest('.relative');
                if (!search || !form.contains(search)) return;
                form.classList.add('mobile-filter-form');
                const extras = Array.from(form.children).filter(child => child !== search && !(child instanceof HTMLInputElement && child.type === 'hidden'));
                if (!extras.length) return;
                const activeCount = Array.from(form.querySelectorAll('select, input:not([type="hidden"]):not([name="q"])')).filter(control => control.value).length;
                const trigger = document.createElement('button');
                trigger.type = 'button';
                trigger.className = 'mobile-filter-trigger';
                trigger.setAttribute('aria-label', 'فتح كل الفلاتر');
                trigger.setAttribute('aria-expanded', 'false');
                trigger.innerHTML = `<span>فلترة</span>${activeCount ? `<span class="mobile-filter-count">${activeCount}</span>` : ''}`;
                const backdrop = document.createElement('button');
                backdrop.type = 'button';
                backdrop.className = 'mobile-filter-backdrop';
                backdrop.setAttribute('aria-label', 'إغلاق الفلاتر');
                const sheet = document.createElement('section');
                sheet.className = 'mobile-filter-sheet';
                sheet.setAttribute('role', 'dialog');
                sheet.setAttribute('aria-modal', 'true');
                sheet.setAttribute('aria-label', 'كل الفلاتر');
                sheet.innerHTML = `<div class="mobile-filter-sheet-head"><strong>كل الفلاتر</strong><button type="button" class="w-11 h-11 rounded-xl bg-gray-50 font-bold" aria-label="إغلاق الفلاتر">×</button></div><div class="mobile-filter-sheet-body"></div>`;
                const body = sheet.querySelector('.mobile-filter-sheet-body');
                extras.forEach(element => body.appendChild(element));
                const close = () => {
                    form.classList.remove('is-open');
                    trigger.setAttribute('aria-expanded', 'false');
                    document.body.style.overflow = '';
                };
                const open = () => {
                    form.classList.add('is-open');
                    trigger.setAttribute('aria-expanded', 'true');
                    document.body.style.overflow = 'hidden';
                    sheet.querySelector('button')?.focus();
                };
                trigger.addEventListener('click', open);
                backdrop.addEventListener('click', close);
                sheet.querySelector('.mobile-filter-sheet-head button').addEventListener('click', close);
                form.addEventListener('keydown', event => { if (event.key === 'Escape') close(); });
                form.addEventListener('submit', close);
                search.after(trigger);
                form.append(backdrop, sheet);
                form.dataset.mobileFilters = '1';
            });
        }

        function initializeMobilePageActions(root) {
            (root || document).querySelectorAll('.mobile-page-actions').forEach(container => {
                if (container.dataset.mobileActions === '1') return;
                const actions = Array.from(container.children);
                if (actions.length < 2) return;
                const primary = actions.pop();
                const details = document.createElement('details');
                details.className = 'mobile-actions-more';
                details.innerHTML = '<summary aria-label="المزيد من الإجراءات">•••</summary><div class="mobile-actions-menu"></div>';
                const menu = details.querySelector('.mobile-actions-menu');
                actions.forEach(action => menu.appendChild(action));
                container.replaceChildren(primary, details);
                container.dataset.mobileActions = '1';
            });
        }

        function initializeMobileKpis(root) {
            (root || document).querySelectorAll('#appContent > .grid, #appContent > div > .grid').forEach(grid => {
                const children = Array.from(grid.children);
                if (children.length > 1 && children.every(child => child.classList.contains('bg-white') && child.classList.contains('rounded-2xl'))) {
                    grid.classList.add('mobile-kpi-row');
                }
            });
        }

        function initializeStickyFormActions(root) {
            (root || document).querySelectorAll('#appContent form').forEach(form => {
                if ((form.getAttribute('method') || 'GET').toUpperCase() === 'GET') return;
                const submit = form.querySelector('button[type="submit"]');
                if (submit?.parentElement && submit.parentElement !== form) submit.parentElement.classList.add('mobile-sticky-submit');
            });
        }

        function initializeMobileExperience(root) {
            initializeMobileTables(root);
            initializeMobileFilters(root);
            initializeMobilePageActions(root);
            initializeMobileKpis(root);
            initializeStickyFormActions(root);
        }
        window.initializeMobileExperience = initializeMobileExperience;

        document.addEventListener('livewire:navigate', function (event) {
            // التبويب يتحوّل لحظة الضغط بحالة «قيد الفتح» حتى تصل الصفحة. البحث والفلاتر
            // تبقى على نفس المسار، فلا يبهت المحتوى أثناء الكتابة.
            const url = event.detail?.url;
            if (!url || !window.Alpine || !Alpine.store('nav')) return;

            const target = Alpine.store('nav').normalize(url);
            if (target === Alpine.store('nav').normalize(window.location.href)) return;

            Alpine.store('nav').path = target;
            document.documentElement.classList.add('is-navigating');
        });

        document.addEventListener('DOMContentLoaded', function () {
            renderIcons();
            initCharts();
            initializeMobileExperience(document);
        });
    </script>

    @include('partials.shell.date-picker')
    @include('partials.shell.topnav-assets')
    @include('partials.shell.mobile-nav-assets')
    @include('partials.shell.page-chrome-assets')
    @include('partials.shell.components-assets')

    {{-- Livewire يوفّر Alpine.js نفسه (مضمَّن داخل حزمته) ويحقن سكربتاته تلقائيًا،
         فلا حاجة لتحميل Alpine من CDN بشكل منفصل — تحميله مرتين يكسر التطبيق. --}}
    {{-- طباعة التقارير: تُخفى عناصر الهيكل ويُفرد المحتوى بطول الصفحة بدل منطقة التمرير --}}
    <style data-purpose="print-styles">
        @media print {
            .app-topnav,
            #appMain > header,
            #bottomNav,
            .no-print {
                display: none !important;
            }

            body,
            body > div,
            #appMain {
                height: auto !important;
                overflow: visible !important;
                background: #fff !important;
            }
        }
    </style>
    @stack('styles')
</head>

<body class="min-h-[100dvh]">
    <div class="bg-[#FAFAFA] w-full h-[100dvh] flex flex-col overflow-hidden relative">
        @persist('topnav')
            @include('partials.shell.topnav')
        @endpersist

        {{-- min-w-0 ضروري: بدونه يرفض عنصر flex الانكماش تحت عرض محتواه،
             فتدفع الجداول العريضة الصفحة كلها وتُحدث انزلاقًا أفقيًا على الهاتف --}}
        <main class="flex-1 min-w-0 min-h-0 bg-[#FAFAFA] flex flex-col overflow-y-auto overflow-x-hidden overscroll-contain"
            id="appMain">
            <x-ui.page-loader />
            @include('partials.shell.header-mobile')
            @include('partials.shell.header-desktop')

            <div class="flex-1 min-w-0 px-4 pt-3 pb-app sm:px-5 lg:px-8 lg:pt-6 lg:pb-8">
                <div class="max-w-[1600px] min-w-0 mx-auto" id="appContent">
                    @yield('content')
                </div>
            </div>
        </main>

        @persist('bottom-nav')
            @include('partials.shell.bottom-nav')
        @endpersist
    </div>

    {{-- عناصر عامة: تنبيهات + تأكيد الحذف + اللوحة الجانبية --}}
    <x-ui.toaster />
    <x-ui.confirm-dialog />
    <x-ui.drawer />

    @if (session('success') || session('error'))
        <div x-cloak x-data
            x-init="$store.toast.{{ session('error') ? 'error' : 'success' }}(@js(session('error') ?? session('success')))"></div>
    @endif


    @stack('scripts')
</body>

</html>
