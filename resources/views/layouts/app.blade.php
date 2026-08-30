<!DOCTYPE html>

<html dir="rtl" lang="ar">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, viewport-fit=cover" name="viewport" />
    <meta content="#FAFAFA" name="theme-color" />
    <title>@yield('title', 'لوحة التحكم') — أكوا ووتر</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/lucide@0.454.0/dist/umd/lucide.min.js"></script>

    <style data-purpose="custom-styles">
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap');

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

        .nav-item-active {
            background-color: #D4FF5B;
            color: #1A1A1A;
        }

        .nav-item:hover:not(.nav-item-active) {
            background-color: rgba(255, 255, 255, 0.1);
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

        // ===== منتقي التاريخ =====
        function datePicker(initial) {
            return {
                open: false,
                value: initial || '',
                view: new Date(),
                months: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'],
                days: ['ح', 'ن', 'ث', 'ر', 'خ', 'ج', 'س'],

                init() {
                    if (this.value) {
                        const d = new Date(this.value);
                        if (!isNaN(d)) this.view = d;
                    }
                },
                get label() {
                    return this.months[this.view.getMonth()] + '، ' + this.view.getFullYear();
                },
                get cells() {
                    const y = this.view.getFullYear(), m = this.view.getMonth();
                    const first = new Date(y, m, 1).getDay();
                    const count = new Date(y, m + 1, 0).getDate();
                    const out = [];
                    for (let i = 0; i < first; i++) out.push({day: '', iso: ''});
                    for (let d = 1; d <= count; d++) {
                        const iso = y + '-' + String(m + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
                        out.push({day: d, iso: iso});
                    }
                    return out;
                },
                move(step) {
                    this.view = new Date(this.view.getFullYear(), this.view.getMonth() + step, 1);
                },
                pick(iso) {
                    if (!iso) return;
                    this.value = iso;
                    this.open = false;
                },
                clear() {
                    this.value = '';
                    this.open = false;
                }
            };
        }

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

        document.addEventListener('DOMContentLoaded', function () {
            renderIcons();
            initCharts();
        });
    </script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    @stack('styles')
</head>

<body class="min-h-[100dvh]">
    <div class="bg-[#FAFAFA] lg:bg-white w-full h-[100dvh] flex overflow-hidden relative">
        @include('partials.shell.sidebar')

        <main class="flex-1 bg-[#FAFAFA] flex flex-col overflow-y-auto overscroll-contain" id="appMain">
            @include('partials.shell.header-mobile')
            @include('partials.shell.header-desktop')

            <div class="flex-1 px-5 pt-1 pb-app lg:px-8 lg:pt-2 lg:pb-8">
                <div class="max-w-[1600px] mx-auto">
                    @yield('content')
                </div>
            </div>
        </main>

        @include('partials.shell.bottom-nav')
        @include('partials.shell.more-sheet')
    </div>

    {{-- عناصر عامة: تنبيهات + تأكيد الحذف --}}
    <x-ui.toaster />
    <x-ui.confirm-dialog />

    @if (session('success') || session('error'))
        <div x-cloak x-data
            x-init="$store.toast.{{ session('error') ? 'error' : 'success' }}(@js(session('error') ?? session('success')))"></div>
    @endif

    <script data-purpose="more-sheet">
        (function () {
            const sheet = document.getElementById('moreSheet');
            const handle = document.getElementById('moreSheetHandle');
            const backdrop = document.getElementById('sheetBackdrop');
            const openBtn = document.getElementById('openMoreSheet');
            const closeBtn = document.getElementById('closeMoreSheet');
            const nav = document.getElementById('bottomNav');
            const main = document.getElementById('appMain');

            if (!sheet || !backdrop) return;
            let isOpen = false;

            function openSheet() {
                isOpen = true;
                sheet.style.transform = '';
                sheet.classList.remove('translate-y-full');
                backdrop.classList.remove('opacity-0', 'pointer-events-none');
                if (nav) nav.classList.add('opacity-0', 'pointer-events-none', 'translate-y-8');
                if (main) main.classList.add('overflow-hidden');
                if (openBtn) openBtn.setAttribute('aria-expanded', 'true');
            }

            function closeSheet() {
                isOpen = false;
                sheet.style.transform = '';
                sheet.classList.add('translate-y-full');
                backdrop.classList.add('opacity-0', 'pointer-events-none');
                if (nav) nav.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-8');
                if (main) main.classList.remove('overflow-hidden');
                if (openBtn) openBtn.setAttribute('aria-expanded', 'false');
            }

            if (openBtn) openBtn.addEventListener('click', openSheet);
            if (closeBtn) closeBtn.addEventListener('click', closeSheet);
            backdrop.addEventListener('click', closeSheet);

            document.addEventListener('keydown', e => {
                if (e.key === 'Escape' && isOpen) closeSheet();
            });

            if (handle) {
                let startY = 0, deltaY = 0, dragging = false;
                handle.addEventListener('touchstart', e => {
                    dragging = true; deltaY = 0; startY = e.touches[0].clientY;
                    sheet.style.transition = 'none';
                }, {passive: true});
                handle.addEventListener('touchmove', e => {
                    if (!dragging) return;
                    deltaY = e.touches[0].clientY - startY;
                    if (deltaY > 0) sheet.style.transform = 'translateY(' + deltaY + 'px)';
                }, {passive: true});
                handle.addEventListener('touchend', () => {
                    if (!dragging) return;
                    dragging = false;
                    sheet.style.transition = '';
                    if (deltaY > 80) closeSheet(); else sheet.style.transform = '';
                    deltaY = 0;
                });
            }

            window.addEventListener('resize', () => {
                if (isOpen && window.innerWidth >= 1024) closeSheet();
            });
        })();
    </script>

    @stack('scripts')
</body>

</html>
