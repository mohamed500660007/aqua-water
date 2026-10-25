{{-- منتقي التاريخ الموحّد: يُحسّن كل input[type=date|month] في التطبيق تلقائيًا.
     الحقل الأصلي يبقى هو حامل القيمة الفعلي (name / x-model / x-on:change / min / max / required)
     ويُخفى بصريًا، ويظهر بدلًا منه زر بنفس تنسيقه يفتح تقويمًا عربيًا موحّدًا.
     لاستخدام منتقي المتصفح الأصلي في حقل بعينه: أضف السمة data-native-date. --}}
<style data-purpose="date-picker">
    .dp-native {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        padding: 0 !important;
        border: 0 !important;
        opacity: 0 !important;
        pointer-events: none !important;
    }

    .dp-trigger {
        display: flex !important;
        align-items: center;
        gap: .55rem;
        text-align: start;
        cursor: pointer;
        user-select: none;
    }

    .dp-trigger:disabled { cursor: not-allowed; opacity: .6; }
    .dp-trigger.is-open { border-color: #1A1A1A !important; box-shadow: 0 0 0 3px rgba(212, 255, 91, .55); }
    .dp-trigger.dp-invalid { border-color: #fca5a5 !important; box-shadow: 0 0 0 3px rgba(254, 202, 202, .7); }
    .dp-trigger svg { width: 17px; height: 17px; flex-shrink: 0; color: #9ca3af; }
    .dp-trigger.is-open .dp-icon, .dp-trigger:not(.is-empty) .dp-icon { color: #1A1A1A; }
    .dp-trigger .dp-chevron { width: 15px; height: 15px; margin-inline-start: auto; transition: transform .15s; }
    .dp-trigger.is-open .dp-chevron { transform: rotate(180deg); }
    .dp-text { min-width: 0; flex: 1; display: flex; align-items: baseline; gap: .4rem; overflow: hidden; white-space: nowrap; }
    .dp-text-main { font-weight: 600; color: #1f2937; overflow: hidden; text-overflow: ellipsis; }
    .dp-text-sub { font-size: 11px; font-weight: 600; color: #9ca3af; }
    .dp-trigger.is-empty .dp-text-main { font-weight: 500; color: #c0c4cc; }

    .dp-backdrop {
        position: fixed;
        inset: 0;
        z-index: 9998;
        background: rgba(26, 26, 26, .35);
        animation: dp-fade .15s ease-out;
    }

    .dp-pop {
        position: fixed;
        z-index: 9999;
        width: 308px;
        padding: 14px;
        background: #fff;
        border: 1px solid #f0f0f0;
        border-radius: 22px;
        box-shadow: 0 28px 60px -24px rgba(26, 26, 26, .45), 0 4px 14px -6px rgba(26, 26, 26, .12);
        font-family: 'Cairo', sans-serif;
        color: #1f2937;
        outline: none;
        animation: dp-pop .16s ease-out;
    }

    .dp-sheet-head { display: none; }

    .dp-head { display: flex; align-items: center; justify-content: space-between; gap: 6px; margin-bottom: 10px; }
    .dp-nav {
        width: 36px; height: 36px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 12px; color: #6b7280;
        transition: background-color .15s, color .15s;
    }
    .dp-nav:hover { background: #f3f4f6; color: #1A1A1A; }
    .dp-nav svg { width: 17px; height: 17px; }
    .dp-title {
        display: inline-flex; align-items: center; gap: 6px;
        height: 36px; padding: 0 14px;
        border-radius: 999px;
        font-size: 14px; font-weight: 800; color: #1A1A1A;
        transition: background-color .15s;
    }
    .dp-title:hover:not(:disabled) { background: #f3f4f6; }
    .dp-title:disabled { cursor: default; }
    .dp-title svg { width: 14px; height: 14px; color: #9ca3af; }

    .dp-weekdays, .dp-days { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 3px; }
    .dp-weekdays span { padding: 4px 0 6px; text-align: center; font-size: 11px; font-weight: 700; color: #9ca3af; }

    .dp-day, .dp-cell {
        position: relative;
        display: flex; align-items: center; justify-content: center;
        font-weight: 600; color: #374151;
        transition: background-color .12s, color .12s;
    }
    .dp-day { height: 38px; border-radius: 12px; font-size: 13px; }
    .dp-day:hover:not(:disabled), .dp-cell:hover:not(:disabled) { background: #f3f4f6; }
    .dp-day.is-outside { color: #c9cdd3; }
    .dp-day.is-today::after, .dp-cell.is-current::after {
        content: '';
        position: absolute;
        bottom: 5px;
        width: 4px; height: 4px;
        border-radius: 999px;
        background: #B494F7;
    }
    .dp-day.is-selected, .dp-cell.is-selected {
        background: #D4FF5B !important;
        color: #1A1A1A;
        font-weight: 800;
    }
    .dp-day.is-selected::after, .dp-cell.is-selected::after { background: #1A1A1A; }
    .dp-day.is-focus { box-shadow: inset 0 0 0 2px #1A1A1A; }
    .dp-day:disabled, .dp-cell:disabled { color: #d9dce1; cursor: not-allowed; text-decoration: line-through; }

    .dp-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 6px; padding-top: 2px; }
    .dp-cell { height: 50px; border-radius: 14px; font-size: 13px; }

    .dp-foot {
        display: flex; align-items: center; justify-content: space-between; gap: 8px;
        margin-top: 12px; padding-top: 12px;
        border-top: 1px solid #f3f4f6;
    }
    .dp-today {
        height: 34px; padding: 0 16px;
        border-radius: 999px;
        background: #1A1A1A; color: #fff;
        font-size: 12px; font-weight: 700;
        transition: background-color .15s;
    }
    .dp-today:hover:not(:disabled) { background: #000; }
    .dp-today:disabled { opacity: .35; cursor: not-allowed; }
    .dp-clear { height: 34px; padding: 0 10px; border-radius: 10px; font-size: 12px; font-weight: 700; color: #9ca3af; }
    .dp-clear:hover { color: #dc2626; background: #fef2f2; }

    /* الهاتف: التقويم يظهر كلوحة من الأسفل بأزرار أكبر للمس */
    .dp-pop.is-sheet {
        top: auto !important;
        left: 0 !important;
        right: 0;
        bottom: 0;
        width: auto;
        padding: 10px 18px calc(18px + env(safe-area-inset-bottom));
        border-radius: 26px 26px 0 0;
        animation: dp-sheet .22s cubic-bezier(.2, .8, .2, 1);
    }
    .dp-pop.is-sheet .dp-sheet-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
    .dp-pop.is-sheet .dp-sheet-head::before {
        content: '';
        position: absolute;
        top: 8px; left: 50%;
        width: 38px; height: 4px;
        border-radius: 999px;
        background: #e5e7eb;
        transform: translateX(-50%);
    }
    .dp-sheet-title { padding-top: 10px; font-size: 14px; font-weight: 800; color: #1A1A1A; }
    .dp-sheet-close { width: 40px; height: 40px; margin-top: 6px; border-radius: 12px; background: #f9fafb; font-size: 18px; font-weight: 700; color: #6b7280; }
    .dp-pop.is-sheet .dp-day { height: 46px; font-size: 14px; }
    .dp-pop.is-sheet .dp-cell { height: 56px; }
    .dp-pop.is-sheet .dp-today, .dp-pop.is-sheet .dp-clear { height: 44px; }

    @keyframes dp-pop { from { opacity: 0; transform: translateY(-4px) scale(.98); } to { opacity: 1; transform: none; } }
    @keyframes dp-sheet { from { transform: translateY(100%); } to { transform: none; } }
    @keyframes dp-fade { from { opacity: 0; } to { opacity: 1; } }
</style>

<script data-purpose="date-picker">
    (function () {
        if (window.AquaDatePicker) return;

        const MONTHS = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
        // الأسبوع في مصر يبدأ بالسبت
        const WEEKDAYS = ['السبت', 'الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'];
        const WEEKDAYS_SHORT = ['س', 'ح', 'ن', 'ث', 'ر', 'خ', 'ج'];
        const SELECTOR = 'input[type="date"]:not([data-native-date]), input[type="month"]:not([data-native-date])';

        const svg = path => `<svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24">${path}</svg>`;
        const ICONS = {
            calendar: svg('<rect height="18" rx="2" width="18" x="3" y="4"/><path d="M16 2v4M8 2v4M3 10h18"/>'),
            chevronDown: svg('<path d="m6 9 6 6 6-6"/>'),
            previous: svg('<path d="m9 18 6-6-6-6"/>'),
            next: svg('<path d="m15 18-6-6 6-6"/>'),
        };

        const nativeValue = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
        const pad = number => String(number).padStart(2, '0');
        const isoDate = date => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
        const isoMonth = (year, month) => `${year}-${pad(month + 1)}`;

        function parse(value) {
            const match = /^(\d{4})-(\d{2})(?:-(\d{2}))?$/.exec(value || '');
            return match ? new Date(+match[1], +match[2] - 1, match[3] ? +match[3] : 1) : null;
        }

        const isMonthMode = input => input.type === 'month';
        const placeholderOf = input => input.getAttribute('placeholder') || (isMonthMode(input) ? 'اختر الشهر' : 'اختر التاريخ');

        function fieldLabel(input) {
            const label = (input.id && document.querySelector(`label[for="${CSS.escape(input.id)}"]`)) || input.closest('label')
                || input.parentElement?.closest('div')?.querySelector('label');

            return label ? label.textContent.replace('*', '').trim() : placeholderOf(input);
        }

        // ===== الحقل المُحسَّن =====
        const triggers = new WeakMap();

        function enhance(input) {
            if (input.dataset.datePicker === '1') return;
            input.dataset.datePicker = '1';

            const trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.setAttribute('aria-haspopup', 'dialog');
            trigger.setAttribute('aria-expanded', 'false');
            input.after(trigger);
            triggers.set(input, trigger);

            input.classList.add('dp-native');
            input.tabIndex = -1;
            input.setAttribute('aria-hidden', 'true');

            // Alpine (x-model) وأي كود آخر يغيّر القيمة برمجيًا بلا أحداث — نعترض الإسناد لتحديث النص الظاهر
            Object.defineProperty(input, 'value', {
                configurable: true,
                get() { return nativeValue.get.call(this); },
                set(value) { nativeValue.set.call(this, value); render(input); },
            });

            new MutationObserver(() => { syncAttributes(input); render(input); })
                .observe(input, {attributes: true, attributeFilter: ['class', 'disabled', 'style', 'min', 'max', 'placeholder']});

            trigger.addEventListener('click', () => toggle(input));
            input.addEventListener('invalid', () => trigger.classList.add('dp-invalid'));

            const label = input.id ? document.querySelector(`label[for="${CSS.escape(input.id)}"]`) : input.closest('label');
            label?.addEventListener('click', event => {
                if (trigger.contains(event.target)) return;
                event.preventDefault();
                trigger.focus();
            });

            syncAttributes(input);
            render(input);
        }

        function syncAttributes(input) {
            const trigger = triggers.get(input);
            const classes = Array.from(input.classList).filter(name => name !== 'dp-native');
            const keep = ['is-open', 'is-empty', 'dp-invalid'].filter(name => trigger.classList.contains(name));

            trigger.className = [...classes, 'dp-trigger', ...keep].join(' ');
            trigger.disabled = input.disabled;
            trigger.style.display = input.style.display === 'none' ? 'none' : '';
        }

        function render(input) {
            const trigger = triggers.get(input);
            if (!trigger) return;

            const date = parse(input.value);
            trigger.classList.toggle('is-empty', !date);
            if (date) trigger.classList.remove('dp-invalid');

            let main = placeholderOf(input);
            let sub = '';

            if (date && isMonthMode(input)) {
                main = `${MONTHS[date.getMonth()]} ${date.getFullYear()}`;
            } else if (date) {
                main = `${date.getDate()} ${MONTHS[date.getMonth()]} ${date.getFullYear()}`;
                sub = WEEKDAYS[(date.getDay() + 1) % 7];
            }

            trigger.innerHTML = `<span class="dp-icon">${ICONS.calendar}</span>`
                + `<span class="dp-text"><span class="dp-text-main"></span><span class="dp-text-sub"></span></span>`
                + `<span class="dp-chevron">${ICONS.chevronDown}</span>`;
            trigger.querySelector('.dp-text-main').textContent = main;
            trigger.querySelector('.dp-text-sub').textContent = sub;
            trigger.setAttribute('aria-label', `${fieldLabel(input)}: ${main}`);
        }

        // ===== التقويم المنبثق (عنصر واحد مشترك لكل الحقول) =====
        let pop = null;
        let backdrop = null;
        let state = null;

        function ensurePop() {
            if (pop?.isConnected) return;

            pop = document.createElement('div');
            pop.className = 'dp-pop';
            pop.tabIndex = -1;
            pop.setAttribute('role', 'dialog');
            pop.addEventListener('click', onPopClick);
            pop.addEventListener('keydown', onKeydown);

            backdrop = document.createElement('div');
            backdrop.className = 'dp-backdrop';
            backdrop.addEventListener('click', () => close(true));
        }

        function toggle(input) {
            if (state?.input === input) return close(true);
            open(input);
        }

        function open(input) {
            if (input.disabled) return;
            close(false);
            ensurePop();

            const selected = parse(input.value);
            const focus = clamp(input, selected || new Date());

            state = {
                input,
                trigger: triggers.get(input),
                view: isMonthMode(input) ? 'months' : 'days',
                year: focus.getFullYear(),
                month: focus.getMonth(),
                focus,
            };

            state.trigger.classList.add('is-open');
            state.trigger.setAttribute('aria-expanded', 'true');
            pop.setAttribute('aria-label', fieldLabel(input));

            const sheet = window.matchMedia('(max-width: 639px)').matches;
            pop.classList.toggle('is-sheet', sheet);
            if (sheet) document.body.append(backdrop);
            document.body.append(pop);

            draw();
            place();
            pop.focus({preventScroll: true});

            document.addEventListener('pointerdown', onOutside, true);
            window.addEventListener('scroll', onScroll, true);
            window.addEventListener('resize', place);
        }

        function close(returnFocus) {
            if (!state) return;

            state.trigger.classList.remove('is-open');
            state.trigger.setAttribute('aria-expanded', 'false');
            if (returnFocus) state.trigger.focus({preventScroll: true});

            pop?.remove();
            backdrop?.remove();
            state = null;

            document.removeEventListener('pointerdown', onOutside, true);
            window.removeEventListener('scroll', onScroll, true);
            window.removeEventListener('resize', place);
        }

        function place() {
            if (!state || pop.classList.contains('is-sheet')) return;

            const rect = state.trigger.getBoundingClientRect();
            const margin = 8;
            const width = pop.offsetWidth;
            const height = pop.offsetHeight;

            let top = rect.bottom + margin;
            if (top + height > window.innerHeight - margin && rect.top - margin - height >= margin) {
                top = rect.top - margin - height;
            }

            // الواجهة من اليمين لليسار: حافة التقويم اليمنى تحاذي حافة الحقل اليمنى
            const left = Math.min(Math.max(margin, rect.right - width), window.innerWidth - width - margin);

            pop.style.top = `${Math.max(margin, top)}px`;
            pop.style.left = `${left}px`;
        }

        function onScroll(event) {
            if (!state || pop.contains(event.target)) return;

            const rect = state.trigger.getBoundingClientRect();
            if (rect.bottom < 0 || rect.top > window.innerHeight) return close(false);
            place();
        }

        function onOutside(event) {
            if (state && !pop.contains(event.target) && !state.trigger.contains(event.target)) close(false);
        }

        // ===== حدود min / max =====
        function bounds(input) {
            const monthMode = isMonthMode(input);
            return {
                min: input.min ? (monthMode ? input.min.slice(0, 7) : input.min) : null,
                max: input.max ? (monthMode ? input.max.slice(0, 7) : input.max) : null,
            };
        }

        // مقارنة نصوص ISO تكفي لأن الصيغة YYYY-MM-DD مرتبة زمنيًا
        function allowed(input, iso) {
            const {min, max} = bounds(input);
            return !(min && iso < min) && !(max && iso > max);
        }

        function clamp(input, date) {
            const {min, max} = bounds(input);
            const iso = isMonthMode(input) ? isoMonth(date.getFullYear(), date.getMonth()) : isoDate(date);

            if (min && iso < min) return parse(min);
            if (max && iso > max) return parse(max);
            return date;
        }

        // ===== الرسم =====
        function draw() {
            const {input, view, year, month} = state;
            const titles = {
                days: `${MONTHS[month]} ${year}`,
                months: `${year}`,
                years: `${yearPageStart()} – ${yearPageStart() + 11}`,
            };

            pop.innerHTML = `
                <div class="dp-sheet-head"><span class="dp-sheet-title"></span>
                    <button class="dp-sheet-close" type="button" data-action="close" aria-label="إغلاق">×</button></div>
                <div class="dp-head">
                    <button class="dp-nav" type="button" data-action="previous" aria-label="السابق">${ICONS.previous}</button>
                    <button class="dp-title" type="button" data-action="zoom-out" ${view === 'years' ? 'disabled' : ''}>
                        <span>${titles[view]}</span>${view === 'years' ? '' : ICONS.chevronDown}</button>
                    <button class="dp-nav" type="button" data-action="next" aria-label="التالي">${ICONS.next}</button>
                </div>
                ${view === 'days' ? daysView() : view === 'months' ? monthsView() : yearsView()}
                <div class="dp-foot">
                    <button class="dp-today" type="button" data-action="today" ${todayAllowed() ? '' : 'disabled'}>
                        ${isMonthMode(input) ? 'الشهر الحالي' : 'اليوم'}</button>
                    ${input.required ? '' : '<button class="dp-clear" type="button" data-action="clear">مسح</button>'}
                </div>`;

            pop.querySelector('.dp-sheet-title').textContent = fieldLabel(input);

            // الزر المضغوط يُستبدل مع إعادة الرسم — نُبقي التركيز داخل التقويم لتعمل لوحة المفاتيح
            if (!pop.contains(document.activeElement)) pop.focus({preventScroll: true});
            place();
        }

        function daysView() {
            const {input, year, month, focus} = state;
            const selected = input.value;
            const today = isoDate(new Date());
            const offset = (new Date(year, month, 1).getDay() + 1) % 7;
            const focusIso = isoDate(focus);

            const cells = Array.from({length: 42}, (_, index) => {
                const date = new Date(year, month, 1 - offset + index);
                const iso = isoDate(date);
                const classes = ['dp-day',
                    date.getMonth() !== month && 'is-outside',
                    iso === today && 'is-today',
                    iso === selected && 'is-selected',
                    iso === focusIso && 'is-focus'].filter(Boolean).join(' ');
                const label = `${WEEKDAYS[(date.getDay() + 1) % 7]} ${date.getDate()} ${MONTHS[date.getMonth()]} ${date.getFullYear()}`;

                return `<button class="${classes}" type="button" data-action="day" data-value="${iso}" aria-label="${label}"
                    aria-pressed="${iso === selected}" ${allowed(input, iso) ? '' : 'disabled'}>${date.getDate()}</button>`;
            });

            return `<div class="dp-weekdays">${WEEKDAYS_SHORT.map(day => `<span>${day}</span>`).join('')}</div>
                <div class="dp-days">${cells.join('')}</div>`;
        }

        function monthsView() {
            const {input, year} = state;
            const selected = (input.value || '').slice(0, 7);
            const current = isoMonth(new Date().getFullYear(), new Date().getMonth());

            return `<div class="dp-grid">${MONTHS.map((name, index) => {
                const iso = isoMonth(year, index);
                // في وضع التاريخ يكفي أن يتقاطع الشهر مع الحدود ليُسمح بفتحه
                const permitted = isMonthMode(input)
                    ? allowed(input, iso)
                    : allowed(input, `${iso}-31`) || allowed(input, `${iso}-01`);
                const classes = ['dp-cell', iso === selected && 'is-selected', iso === current && 'is-current'].filter(Boolean).join(' ');

                return `<button class="${classes}" type="button" data-action="month" data-value="${index}" ${permitted ? '' : 'disabled'}>${name}</button>`;
            }).join('')}</div>`;
        }

        function yearsView() {
            const selected = (state.input.value || '').slice(0, 4);
            const current = String(new Date().getFullYear());
            const {min, max} = bounds(state.input);

            return `<div class="dp-grid">${Array.from({length: 12}, (_, index) => {
                const year = String(yearPageStart() + index);
                const permitted = !(min && year < min.slice(0, 4)) && !(max && year > max.slice(0, 4));
                const classes = ['dp-cell', year === selected && 'is-selected', year === current && 'is-current'].filter(Boolean).join(' ');

                return `<button class="${classes}" type="button" data-action="year" data-value="${year}" ${permitted ? '' : 'disabled'}>${year}</button>`;
            }).join('')}</div>`;
        }

        const yearPageStart = () => Math.floor(state.year / 12) * 12;

        function todayAllowed() {
            const now = new Date();
            return allowed(state.input, isMonthMode(state.input) ? isoMonth(now.getFullYear(), now.getMonth()) : isoDate(now));
        }

        // ===== التفاعل =====
        function onPopClick(event) {
            const button = event.target.closest('button[data-action]');
            if (!button || button.disabled) return;

            const value = button.dataset.value;
            const actions = {
                close: () => close(true),
                previous: () => shift(-1),
                next: () => shift(1),
                'zoom-out': () => { state.view = state.view === 'days' ? 'months' : 'years'; draw(); },
                day: () => commit(value),
                month: () => pickMonth(+value),
                year: () => { state.year = +value; state.view = 'months'; draw(); },
                today: () => {
                    const now = new Date();
                    commit(isMonthMode(state.input) ? isoMonth(now.getFullYear(), now.getMonth()) : isoDate(now));
                },
                clear: () => commit(''),
            };

            actions[button.dataset.action]?.();
        }

        function shift(step) {
            if (state.view === 'days') {
                const date = new Date(state.year, state.month + step, 1);
                state.year = date.getFullYear();
                state.month = date.getMonth();
            } else {
                state.year += state.view === 'months' ? step : step * 12;
            }
            draw();
        }

        function pickMonth(month) {
            if (isMonthMode(state.input)) return commit(isoMonth(state.year, month));

            state.month = month;
            state.view = 'days';
            draw();
        }

        function commit(value) {
            const input = state.input;
            input.value = value;
            input.dispatchEvent(new Event('input', {bubbles: true}));
            input.dispatchEvent(new Event('change', {bubbles: true}));
            close(true);
        }

        // الأسهم معكوسة أفقيًا لأن التقويم من اليمين لليسار
        const KEY_STEPS = {ArrowRight: -1, ArrowLeft: 1, ArrowUp: -7, ArrowDown: 7};

        function onKeydown(event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                return close(true);
            }
            if (state.view !== 'days') return;

            if (event.key === 'Enter') {
                const iso = isoDate(state.focus);
                if (allowed(state.input, iso)) {
                    event.preventDefault();
                    commit(iso);
                }
                return;
            }

            const focus = new Date(state.focus);
            if (KEY_STEPS[event.key]) focus.setDate(focus.getDate() + KEY_STEPS[event.key]);
            else if (event.key === 'PageUp') focus.setMonth(focus.getMonth() - 1);
            else if (event.key === 'PageDown') focus.setMonth(focus.getMonth() + 1);
            else return;

            event.preventDefault();
            state.focus = focus;
            state.year = focus.getFullYear();
            state.month = focus.getMonth();
            draw();
        }

        // ===== التهيئة =====
        function scan(root) {
            if (root.matches?.(SELECTOR)) enhance(root);
            root.querySelectorAll?.(SELECTOR).forEach(enhance);
        }

        // يلتقط حقول الصفحة أثناء تحميلها، وما يضيفه Alpine لاحقًا (بنود جديدة)، وصفحات التنقل عبر Livewire
        new MutationObserver(mutations => mutations.forEach(mutation =>
            mutation.addedNodes.forEach(node => node.nodeType === 1 && scan(node)),
        )).observe(document.documentElement, {childList: true, subtree: true});

        document.addEventListener('DOMContentLoaded', () => scan(document));
        document.addEventListener('livewire:navigate', () => close(false));

        window.AquaDatePicker = {scan, open, close};
    })();
</script>
