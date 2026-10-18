{{-- نظام مكونات موحّد بنفس لغة شريط التنقل (داكن + ليموني + لمسة بنفسجية):
     البطاقات، الفلاتر، الحقول، الجداول، الترقيم، الشارات، المؤشرات، الحالات الفارغة، التنبيهات.
     بعض القواعد تستهدف أنماط Tailwind المتكررة داخل #appContent حتى يتغيّر شكل كل الصفحات دفعة واحدة. --}}
<style data-purpose="components">
    :root {
        --ui-ink: #111111;
        --ui-lime: #D4FF5B;
        --ui-line: #ECEEF1;
        --ui-muted: #8A9099;
        --ui-ease: cubic-bezier(.22, .9, .24, 1);
        --ui-spring: cubic-bezier(.34, 1.56, .64, 1);
    }

    /* ===== ألوان موحّدة للشارات والمؤشرات ===== */
    .ui-tone-green { --tone: #4D7C0F; --tone-soft: #EEFBD9; --tone-line: #D5F09E; --tone-solid: #84CC16; }
    .ui-tone-blue { --tone: #2563EB; --tone-soft: #EEF4FF; --tone-line: #CFE0FF; --tone-solid: #3B82F6; }
    .ui-tone-purple { --tone: #7C3AED; --tone-soft: #F4EFFF; --tone-line: #E2D6FF; --tone-solid: #8B5CF6; }
    .ui-tone-amber { --tone: #B45309; --tone-soft: #FFF7E0; --tone-line: #FDE6A8; --tone-solid: #F59E0B; }
    .ui-tone-red { --tone: #DC2626; --tone-soft: #FFF0F0; --tone-line: #FFD4D4; --tone-solid: #EF4444; }
    .ui-tone-gray { --tone: #4B5563; --tone-soft: #F3F4F6; --tone-line: #E5E7EB; --tone-solid: #9CA3AF; }
    .ui-tone-teal { --tone: #0F766E; --tone-soft: #E8FAF7; --tone-line: #BEEFE7; --tone-solid: #14B8A6; }
    .ui-tone-orange { --tone: #C2410C; --tone-soft: #FFF2E8; --tone-line: #FED7BB; --tone-solid: #F97316; }

    /* ===== البطاقات ===== */
    #appContent .bg-white.rounded-2xl.border-gray-100 {
        border-radius: 24px;
        border-color: var(--ui-line);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, .9),
            0 1px 2px rgba(16, 24, 40, .04),
            0 22px 44px -36px rgba(26, 26, 26, .4);
    }

    /* شريط الفلاتر أعلى بطاقة القائمة */
    #appContent .overflow-hidden > form[method="GET"]:first-child {
        background: linear-gradient(180deg, #FCFCFD, #FFFFFF);
        border-bottom-color: var(--ui-line);
    }

    /* رابط «إعادة ضبط» داخل الفلاتر: شريحة منقطة خفيفة */
    #appContent form[method="GET"] a[href]:not(.ui-btn) {
        border-style: dashed !important;
        border-radius: 14px !important;
        transition: border-color .2s ease, color .2s ease, background-color .2s ease;
    }

    #appContent form[method="GET"] a[href]:not(.ui-btn):hover { border-color: #1A1A1A !important; color: #1A1A1A !important; background: #fff !important; }

    /* ===== الحقول ===== */
    .ui-input {
        border-radius: 14px;
        border: 1px solid #E4E7EB;
        background-color: #fff;
        color: #111827;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
        transition: border-color .2s ease, box-shadow .2s ease, background-color .2s ease;
    }

    .ui-input::placeholder { color: #A0A6B0; }
    .ui-input:hover { border-color: #CDD2D8; }

    .ui-input:focus,
    .ui-input[aria-expanded="true"],
    .ui-input.is-open {
        outline: none;
        border-color: #1A1A1A;
        box-shadow: 0 0 0 4px rgba(212, 255, 91, .5);
    }

    .ui-input.is-invalid, .ui-input.dp-invalid { border-color: #FCA5A5; box-shadow: 0 0 0 4px rgba(254, 202, 202, .55); }

    /* الحقول المكتوبة يدويًا في الصفحات (بنود الطلبيات والعقود…) تأخذ نفس التركيز */
    #appContent :is(input:not([type="checkbox"]):not([type="radio"]):not([type="file"]), select, textarea).rounded-xl:focus {
        outline: none;
        border-color: #1A1A1A !important;
        box-shadow: 0 0 0 4px rgba(212, 255, 91, .5) !important;
    }

    /* فلتر مختار: يتلوّن ليموني حتى تظهر الفلاتر النشطة بنظرة واحدة */
    .ui-select-trigger.is-filled {
        background-color: #F7FDE8;
        border-color: rgba(132, 204, 22, .6);
        color: #1A1A1A;
    }

    .ui-select-trigger.is-filled .ui-select-text { font-weight: 800; }

    .ui-menu {
        padding: 6px;
        border-radius: 18px;
        background: #fff;
        border: 1px solid var(--ui-line);
        box-shadow: 0 28px 56px -26px rgba(26, 26, 26, .45);
    }

    .ui-option {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 12px;
        border-radius: 12px;
        font-size: 13px;
        color: #4B5563;
        text-align: right;
        transition: background-color .15s ease, color .15s ease;
    }

    .ui-option:hover { background: #F5F6F8; color: #111827; }
    .ui-option.is-selected { background: #1A1A1A; color: #fff; font-weight: 700; }
    .ui-option.is-selected svg { color: var(--ui-lime); }

    /* ===== الجداول (سطح المكتب — الهاتف يحوّلها لبطاقات) ===== */
    @media (min-width: 768px) {
        #appContent table:not(.rpt-table):not(.rpt-figures) > thead {
            background: transparent !important;
            border: 0 !important;
        }

        #appContent table:not(.rpt-table):not(.rpt-figures) > tbody > tr > td {
            border-bottom: 1px solid #F2F4F6;
            transition: background-color .2s ease, box-shadow .2s ease;
        }

        #appContent table:not(.rpt-table):not(.rpt-figures) > tbody > tr:last-child > td { border-bottom: 0; }

        #appContent table:not(.rpt-table):not(.rpt-figures) > tbody > tr:hover { background: transparent !important; }
        #appContent table:not(.rpt-table):not(.rpt-figures) > tbody > tr:hover > td { background-color: #FBFDF2; }

        /* فواصل الأعمدة: خط رأسي متدرّج من البنفسجي إلى الليموني (ألوان الهوية) يتلاشى عند طرفيه،
           على الحافة البادئة لكل خلية بعد الأولى (اليمين في الواجهة العربية) */
        #appContent table:not(.rpt-table):not(.rpt-figures) > tbody > tr > td + td {
            background-image: linear-gradient(to bottom, transparent, #D3C2FC 18%, #C9EE5A 82%, transparent);
            background-repeat: no-repeat;
            background-size: 2px 70%;
            background-position: right center;
            transition: background-color .2s ease, background-size .35s var(--ui-ease), box-shadow .2s ease;
        }

        /* عند المرور تمتد فواصل الصف لكامل ارتفاعه وتشتد ألوانها */
        #appContent table:not(.rpt-table):not(.rpt-figures) > tbody > tr:hover > td + td {
            background-image: linear-gradient(to bottom, transparent, #A98BF5 15%, #A6D619 85%, transparent);
            background-size: 2px 100%;
        }

        /* فواصل العناوين: نفس التدرّج بدرجة أعمق وأقصر */
        #appContent table:not(.rpt-table):not(.rpt-figures) > thead th + th {
            background-image: linear-gradient(to bottom, transparent, #9B7BF0 22%, #9CCB12 78%, transparent);
            background-repeat: no-repeat;
            background-size: 2px 22px;
            background-position: right center;
        }

        /* شريط ليموني على حافة الصف عند المرور */
        #appContent table:not(.rpt-table):not(.rpt-figures) > tbody > tr:hover > td:first-child { box-shadow: inset -3px 0 0 #C6F23E; }

        /* الصفوف تظهر متتابعة عند فتح الصفحة */
        #appContent table:not(.rpt-table):not(.rpt-figures) > tbody > tr { animation: ui-row-in .45s var(--ui-ease) both; }
        #appContent table > tbody > tr:nth-child(2) { animation-delay: 30ms; }
        #appContent table > tbody > tr:nth-child(3) { animation-delay: 60ms; }
        #appContent table > tbody > tr:nth-child(4) { animation-delay: 90ms; }
        #appContent table > tbody > tr:nth-child(5) { animation-delay: 120ms; }
        #appContent table > tbody > tr:nth-child(6) { animation-delay: 150ms; }
        #appContent table > tbody > tr:nth-child(7) { animation-delay: 180ms; }
        #appContent table > tbody > tr:nth-child(8) { animation-delay: 210ms; }
        #appContent table > tbody > tr:nth-child(n+9) { animation-delay: 240ms; }
    }

    @keyframes ui-row-in {
        from { opacity: 0; transform: translateY(6px); }
    }

    .ui-th {
        padding: 12px 16px;
        background: #F6F7F9;
        border-bottom: 1px solid var(--ui-line);
        font-size: 11.5px;
        font-weight: 800;
        color: #6B7280;
        white-space: nowrap;
        user-select: none;
    }

    .ui-th-sort {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin: -4px -8px;
        padding: 4px 8px;
        border-radius: 999px;
        transition: background-color .2s ease, color .2s ease;
    }

    .ui-th-sort:hover { background: #ECEEF1; color: #111827; }
    .ui-th-sort svg { width: 12px; height: 12px; transition: transform .3s var(--ui-ease), opacity .2s ease; }
    .ui-th-sort.is-active { background: #1A1A1A; color: var(--ui-lime); }

    /* ===== أزرار الإجراءات (عرض / تعديل / حذف) =====
       لون كل زر يعبّر عن معناه، وعند المرور يمتلئ بلونه ويظهر اسمه في فقاعة داكنة فوقه */
    .ui-icon-btn {
        position: relative;
        width: 34px;
        height: 34px;
        flex-shrink: 0;
        display: grid;
        place-items: center;
        border-radius: 11px;
        color: #4B5563;
        transition: transform .25s var(--ui-spring), background-color .2s ease, color .2s ease, box-shadow .2s ease;
    }

    .ui-icon-btn svg, .ui-icon-btn i { width: 16px; height: 16px; transition: transform .3s var(--ui-spring); }
    .ui-icon-btn.is-primary { color: #7C3AED; }
    .ui-icon-btn.is-danger { color: #EF4444; }

    .ui-icon-btn:hover { transform: translateY(-2px); color: #fff; background: #1A1A1A; box-shadow: 0 10px 18px -10px rgba(0, 0, 0, .7); }
    .ui-icon-btn:hover svg { transform: scale(1.08) rotate(-6deg); }
    .ui-icon-btn.is-primary:hover { color: #1A1A1A; background: var(--ui-lime); box-shadow: 0 10px 18px -8px rgba(132, 204, 22, .85); }
    .ui-icon-btn.is-danger:hover { color: #fff; background: linear-gradient(160deg, #F87171, #DC2626); box-shadow: 0 10px 18px -8px rgba(220, 38, 38, .75); }
    .ui-icon-btn:active { transform: scale(.88); }
    .ui-icon-btn:focus-visible { outline: 2px solid #84CC16; outline-offset: 2px; }

    /* فقاعة الاسم من aria-label */
    .ui-icon-btn[aria-label]:not([aria-label=""])::after {
        content: attr(aria-label);
        position: absolute;
        z-index: 20;
        bottom: calc(100% + 8px);
        left: 50%;
        padding: 5px 10px;
        border-radius: 9px;
        background: #1A1A1A;
        color: var(--ui-lime);
        font-size: 11.5px;
        font-weight: 800;
        white-space: nowrap;
        pointer-events: none;
        opacity: 0;
        transform: translate(-50%, 4px) scale(.9);
        transition: opacity .15s ease, transform .2s var(--ui-spring);
        box-shadow: 0 10px 20px -10px rgba(0, 0, 0, .6);
    }

    .ui-icon-btn:hover::after, .ui-icon-btn:focus-visible::after { opacity: 1; transform: translate(-50%, 0) scale(1); }

    /* خانة الإجراءات في الجدول: الأزرار داخل كبسولة واحدة باهتة قليلًا حتى يمر المؤشر على صفها */
    #appContent td:last-child > .flex:has(.ui-icon-btn) {
        display: inline-flex !important;
        float: left;
        gap: 2px !important;
        padding: 3px;
        border-radius: 14px;
        background: #F6F7F9;
        box-shadow: inset 0 0 0 1px #ECEEF1;
        opacity: .8;
        transition: opacity .2s ease, background-color .2s ease, box-shadow .2s ease;
    }

    #appContent tr:hover > td:last-child > .flex:has(.ui-icon-btn) {
        opacity: 1;
        background: #fff;
        box-shadow: inset 0 0 0 1px #E4E7EB, 0 10px 22px -16px rgba(26, 26, 26, .55);
    }

    @media (max-width: 767px) {
        .ui-icon-btn { width: 40px; height: 40px; }
        #appContent td:last-child > .flex:has(.ui-icon-btn) { float: none; opacity: 1; }
        /* لا فقاعات على اللمس — الاسم يُقرأ من الأيقونة نفسها */
        .ui-icon-btn::after { content: none !important; }
    }

    /* ===== الترقيم ===== */
    .ui-pager {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 14px 16px;
        border-top: 1px solid var(--ui-line);
        background: linear-gradient(180deg, #FFFFFF, #FCFCFD);
    }

    .ui-pager-info { display: flex; align-items: center; gap: 12px; font-size: 12px; color: var(--ui-muted); }
    .ui-pager-info b { color: #111827; font-weight: 800; }

    /* موضعك داخل القائمة كشريط صغير */
    .ui-pager-track { width: 90px; height: 6px; border-radius: 999px; background: #EEF0F2; overflow: hidden; }

    .ui-pager-track > span {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #B494F7, #C6F23E);
        transform-origin: right;
        animation: ui-grow .8s var(--ui-ease) both;
    }

    @keyframes ui-grow {
        from { transform: scaleX(0); }
    }

    .ui-pager-pages {
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 4px;
        border-radius: 16px;
        background: #fff;
        border: 1px solid var(--ui-line);
        box-shadow: 0 10px 22px -20px rgba(26, 26, 26, .6);
    }

    .ui-page {
        min-width: 36px;
        height: 36px;
        padding: 0 8px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        font-size: 12.5px;
        font-weight: 800;
        color: #6B7280;
        transition: background-color .2s ease, color .2s ease, transform .25s var(--ui-ease);
    }

    .ui-page:hover { background: #F3F4F6; color: #111827; }
    .ui-page svg { width: 16px; height: 16px; }
    .ui-page.is-disabled { opacity: .3; pointer-events: none; }
    .ui-page.is-gap { min-width: 20px; padding: 0; color: #B6BBC3; pointer-events: none; }

    .ui-page.is-current {
        background: linear-gradient(180deg, #282828, #141414);
        color: var(--ui-lime);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .1), 0 8px 16px -10px rgba(0, 0, 0, .8);
        transform: translateY(-1px);
    }

    @media (max-width: 639px) {
        .ui-pager { justify-content: center; }
        .ui-page { min-width: 44px; height: 44px; }
    }

    /* ===== الشارات ===== */
    .ui-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        height: 26px;
        padding: 0 10px;
        border-radius: 999px;
        white-space: nowrap;
        font-size: 11.5px;
        font-weight: 800;
        color: var(--tone);
        background: var(--tone-soft);
        box-shadow: inset 0 0 0 1px var(--tone-line);
    }

    .ui-badge-dot {
        position: relative;
        width: 7px;
        height: 7px;
        border-radius: 999px;
        background: var(--tone-solid);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--tone-solid) 22%, transparent);
    }

    /* ===== بطاقات المؤشرات ===== */
    .ui-kpi {
        position: relative;
        overflow: hidden;
        isolation: isolate;
        padding: 18px;
        transition: transform .3s var(--ui-ease), box-shadow .3s ease;
    }

    .ui-kpi.is-compact { padding: 14px; }
    .ui-kpi:hover { transform: translateY(-3px); box-shadow: 0 26px 40px -30px rgba(26, 26, 26, .5) !important; }

    /* أيقونة كبيرة باهتة في الخلفية */
    .ui-kpi-glyph {
        position: absolute;
        z-index: -1;
        left: -14px;
        bottom: -18px;
        width: 92px !important;
        height: 92px !important;
        color: var(--tone);
        opacity: .07;
        transform: rotate(-12deg);
        transition: transform .5s var(--ui-ease), opacity .3s ease;
    }

    .ui-kpi:hover .ui-kpi-glyph { opacity: .12; transform: rotate(-4deg) scale(1.08); }

    .ui-kpi::after {
        content: '';
        position: absolute;
        inset-inline-start: 18px;
        bottom: 0;
        width: 34px;
        height: 3px;
        border-radius: 3px 3px 0 0;
        background: var(--tone-solid);
        transition: width .4s var(--ui-ease);
    }

    .ui-kpi:hover::after { width: 64px; }

    .ui-kpi-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; margin-bottom: 12px; }

    .ui-kpi-icon {
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        border-radius: 14px;
        color: var(--tone);
        background: var(--tone-soft);
        box-shadow: inset 0 0 0 1px var(--tone-line);
    }

    .ui-kpi.is-compact .ui-kpi-icon { width: 34px; height: 34px; border-radius: 11px; }
    .ui-kpi-icon svg, .ui-kpi-icon i { width: 19px; height: 19px; }
    .ui-kpi.is-compact .ui-kpi-icon svg, .ui-kpi.is-compact .ui-kpi-icon i { width: 16px; height: 16px; }

    .ui-kpi-label { font-size: 12px; font-weight: 600; color: var(--ui-muted); margin-bottom: 4px; }
    .ui-kpi-value { font-size: 26px; line-height: 1; font-weight: 800; color: var(--ui-ink); letter-spacing: -.01em; }
    .ui-kpi.is-compact .ui-kpi-value { font-size: 20px; }
    .ui-kpi-value small { font-size: 12px; font-weight: 700; color: var(--ui-muted); letter-spacing: 0; }
    .ui-kpi-hint { margin-top: 8px; font-size: 11.5px; color: var(--ui-muted); }

    .ui-kpi-change {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        height: 24px;
        padding: 0 8px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
    }

    .ui-kpi-change svg, .ui-kpi-change i { width: 12px; height: 12px; }

    /* ===== الحالة الفارغة ===== */
    .ui-empty { display: flex; flex-direction: column; align-items: center; text-align: center; padding: 52px 24px; }

    .ui-empty-art { position: relative; width: 112px; height: 112px; display: grid; place-items: center; margin-bottom: 18px; }

    .ui-empty-ring {
        position: absolute;
        inset: 0;
        border-radius: 999px;
        border: 1px dashed #D9DDE3;
        animation: ui-spin 26s linear infinite;
    }

    .ui-empty-ring.is-inner { inset: 16px; border-style: solid; border-color: #ECEEF1; background: radial-gradient(circle, #FBFDF2, transparent 70%); animation: none; }

    .ui-empty-ring::after {
        content: '';
        position: absolute;
        top: 10px;
        left: 14px;
        width: 9px;
        height: 9px;
        border-radius: 999px;
        background: var(--ui-lime);
        box-shadow: 0 0 0 4px rgba(212, 255, 91, .3);
    }

    .ui-empty-ring.is-inner::after { content: none; }

    @keyframes ui-spin {
        to { transform: rotate(360deg); }
    }

    .ui-empty-icon {
        position: relative;
        width: 54px;
        height: 54px;
        display: grid;
        place-items: center;
        border-radius: 18px;
        color: var(--ui-lime);
        background:
            radial-gradient(90% 90% at 100% 0%, rgba(212, 255, 91, .3), transparent 60%),
            radial-gradient(90% 90% at 0% 100%, rgba(180, 148, 247, .45), transparent 60%),
            #171717;
        box-shadow: 0 16px 28px -16px rgba(0, 0, 0, .8);
        animation: ui-float 4s ease-in-out infinite;
    }

    .ui-empty-icon svg, .ui-empty-icon i { width: 24px; height: 24px; }

    @keyframes ui-float {
        50% { transform: translateY(-5px); }
    }

    .ui-empty h3 { font-size: 15px; font-weight: 800; color: #111827; margin-bottom: 6px; }
    .ui-empty p { max-width: 300px; font-size: 12.5px; line-height: 1.8; color: var(--ui-muted); }

    /* ===== صفوف التفاصيل والصور الرمزية ===== */
    .ui-detail {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding: 11px 0;
        border-bottom: 1px dashed #E8EAED;
    }

    .ui-detail:last-child { border-bottom: 0; }
    .ui-detail-label { flex-shrink: 0; font-size: 12px; color: var(--ui-muted); }
    .ui-detail-value { font-size: 13px; font-weight: 700; color: #111827; text-align: left; }

    .ui-avatar {
        flex-shrink: 0;
        display: grid;
        place-items: center;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
        user-select: none;
        box-shadow: 0 0 0 3px #fff, 0 6px 14px -8px rgba(26, 26, 26, .5);
    }

    .ui-avatar-0 { background: linear-gradient(145deg, #EEFFB0, #C6F23E); color: #1A1A1A; }
    .ui-avatar-1 { background: linear-gradient(145deg, #EDE5FF, #C9B4FB); color: #5B21B6; }
    .ui-avatar-2 { background: linear-gradient(145deg, #E2EDFF, #A8C8FF); color: #1D4ED8; }
    .ui-avatar-3 { background: linear-gradient(145deg, #FFF3D1, #FCD679); color: #92400E; }
    .ui-avatar-4 { background: linear-gradient(145deg, #DDF8F3, #8FE3D5); color: #115E59; }
    .ui-avatar-5 { background: linear-gradient(145deg, #FFE4E4, #FCA5A5); color: #B91C1C; }

    /* ===== الهاتف: مكونات أصغر وأكثف بنفس الهوية ===== */
    @media (max-width: 767px) {
        /* بطاقة الصف: الخانة الأولى رأس البطاقة، والباقي شبكة من عمودين بخطوط رفيعة،
           والإجراءات تذييل — بدل صف كامل لكل خانة */
        .mobile-table-shell { padding: .6rem !important; background: transparent !important; }
        .mobile-card-table tbody { gap: .6rem !important; }

        .mobile-card-table tbody > tr {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1px;
            border: 0 !important;
            border-radius: 20px !important;
            background: #ECEEF1 !important;
            box-shadow: 0 0 0 1px #ECEEF1, 0 14px 26px -22px rgba(26, 26, 26, .55) !important;
            animation: ui-row-in .45s var(--ui-ease) both;
        }

        /* block لا flex: حتى تبقى العناصر المتجاورة (المبلغ + «ج.م») في سطر واحد كما في الجدول */
        .mobile-card-table tbody > tr > td {
            display: block !important;
            min-width: 0;
            min-height: 0 !important;
            padding: 9px 12px !important;
            border: 0 !important;
            background: #fff;
            font-size: 13px;
        }

        .mobile-card-table tbody > tr > td::before {
            display: block;
            margin-bottom: 3px;
            padding: 0 !important;
            font-size: 11px !important;
            font-weight: 700;
            color: #9AA1AB !important;
            line-height: 1.3 !important;
        }

        /* خانة وحيدة في آخر سطر الشبكة تمتد بعرض البطاقة بدل ترك فراغ رمادي */
        .mobile-card-table tbody > tr > td:nth-child(even):nth-last-child(2) { grid-column: 1 / -1; }

        .mobile-card-table tbody > tr > td:first-child {
            grid-column: 1 / -1;
            padding: 11px 12px !important;
            background: linear-gradient(180deg, #F7FDE6, #FFFFFF) !important;
            box-shadow: inset -3px 0 0 #C6F23E;
        }

        .mobile-card-table tbody > tr > td:first-child::before { content: none !important; }

        .mobile-card-table tbody > tr > td:last-child {
            grid-column: 1 / -1;
            background: #FBFBFC;
        }

        .mobile-card-table tbody > tr > td:last-child:has(.ui-icon-btn) {
            display: flex !important;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            padding-block: 6px !important;
        }

        .mobile-card-table tbody > tr > td:last-child:has(.ui-icon-btn)::before { align-self: center; margin: 0; }
        .mobile-card-table td > .flex:has(.ui-icon-btn) { width: auto !important; }

        .mobile-card-table .ui-avatar { width: 36px !important; height: 36px !important; }
        .mobile-card-table .ui-badge { height: 24px; padding: 0 8px; font-size: 11px; }

        /* الحقول والأزرار: 44px (أصغر مقاس لمس مريح)، ونص الحقول يبقى 16px حتى لا يكبّر iOS الصفحة */
        input.ui-input, button.ui-input { height: 44px !important; }
        .ui-btn-md, .ui-btn-lg { min-height: 44px; }
        .mobile-page-actions > :not(.mobile-actions-more) { min-height: 44px !important; }
        .mobile-actions-more > summary { width: 44px !important; height: 44px !important; }
        .mobile-filter-trigger { height: 44px !important; min-width: 44px !important; border-radius: 14px !important; }
        .mobile-filter-form { padding: .6rem !important; gap: .5rem !important; }

        /* البطاقات أقل حشوًا */
        #appContent .bg-white.rounded-2xl.border-gray-100 { border-radius: 20px; }
        #appContent .bg-white.rounded-2xl.border-gray-100.p-5 { padding: 14px !important; }

        /* المؤشرات: شرائح سحب صغيرة بدل بطاقات بعرض الشاشة */
        .mobile-kpi-row > .ui-kpi { flex: 0 0 150px !important; }
        .ui-kpi, .ui-kpi.is-compact { padding: 12px; }
        .ui-kpi-head { margin-bottom: 8px; }
        .ui-kpi-icon, .ui-kpi.is-compact .ui-kpi-icon { width: 30px; height: 30px; border-radius: 10px; }
        .ui-kpi-icon svg, .ui-kpi-icon i { width: 15px !important; height: 15px !important; }
        .ui-kpi-value, .ui-kpi.is-compact .ui-kpi-value { font-size: 19px; }
        .ui-kpi-label { font-size: 11.5px !important; margin-bottom: 2px; }
        .ui-kpi-glyph { width: 64px !important; height: 64px !important; }
        .ui-kpi::after { inset-inline-start: 12px; }

        .ui-pager { padding: 10px 12px; gap: 10px; }
        .ui-page { min-width: 40px; height: 40px; }
        .ui-empty { padding: 36px 18px; }
        .ui-empty-art { width: 92px; height: 92px; }
    }

    /* ===== التنبيهات ونافذة التأكيد ===== */
    .ui-toast {
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px;
        border-radius: 20px;
        color: #fff;
        background:
            radial-gradient(120% 160% at 100% 0%, rgba(212, 255, 91, .14), transparent 50%),
            radial-gradient(90% 180% at 0% 100%, rgba(180, 148, 247, .2), transparent 50%),
            linear-gradient(180deg, #222, #141414);
        box-shadow: 0 24px 48px -24px rgba(0, 0, 0, .7), inset 0 1px 0 rgba(255, 255, 255, .08);
    }

    .ui-toast-icon { width: 36px; height: 36px; flex-shrink: 0; display: grid; place-items: center; border-radius: 12px; }
    .ui-toast-icon.is-success { background: var(--ui-lime); color: #1A1A1A; }
    .ui-toast-icon.is-error { background: #EF4444; color: #fff; }
    .ui-toast-icon.is-info { background: #B494F7; color: #1A1A1A; }

    .ui-toast-close { width: 30px; height: 30px; flex-shrink: 0; display: grid; place-items: center; border-radius: 10px; color: rgba(255, 255, 255, .45); transition: background-color .2s ease, color .2s ease; }
    .ui-toast-close:hover { background: rgba(255, 255, 255, .1); color: #fff; }

    .ui-btn-danger-solid {
        color: #fff;
        background: linear-gradient(160deg, #F87171, #DC2626);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .3), 0 12px 22px -12px rgba(220, 38, 38, .8);
    }

    .ui-confirm-art { position: relative; width: 76px; height: 76px; margin: 0 auto 16px; display: grid; place-items: center; }
    .ui-confirm-art::before, .ui-confirm-art::after { content: ''; position: absolute; inset: 0; border-radius: 999px; background: #FFF0F0; animation: ui-pulse 2.2s ease-out infinite; }
    .ui-confirm-art::after { animation-delay: 1.1s; }

    @keyframes ui-pulse {
        from { transform: scale(.7); opacity: 1; }
        to { transform: scale(1.25); opacity: 0; }
    }

    .ui-confirm-icon { position: relative; z-index: 1; width: 56px; height: 56px; display: grid; place-items: center; border-radius: 19px; background: linear-gradient(160deg, #F87171, #DC2626); color: #fff; box-shadow: 0 14px 26px -12px rgba(220, 38, 38, .8); }

    @media (prefers-reduced-motion: reduce) {
        #appContent table > tbody > tr, .mobile-card-table tbody > tr, .ui-pager-track > span,
        .ui-empty-ring, .ui-empty-icon, .ui-confirm-art::before, .ui-confirm-art::after { animation: none; }
        .ui-kpi, .ui-icon-btn, .ui-page { transition: none; }
    }
</style>
