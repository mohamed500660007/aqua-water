{{-- أنماط وسلوك شريط التنقل العلوي (سطح المكتب). العلامات في partials.shell.topnav.
     تُحمَّل في <head> مرة واحدة لأن الشريط نفسه محفوظ (@@persist) لا يُعاد رسمه بين الصفحات. --}}
<style data-purpose="topnav">
    @property --tn-angle {
        syntax: '<angle>';
        initial-value: 0deg;
        inherits: false;
    }

    .app-topnav {
        --tn-ease: cubic-bezier(.22, .9, .24, 1);
        position: relative;
        z-index: 45;
        flex-shrink: 0;
        padding: 12px 20px 0;
        background: #FAFAFA;
    }

    /* ===== الصف العلوي: عنوان الصفحة | الشريط | أزرار الصفحة ===== */
    .tn-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
        align-items: center;
        gap: 16px;
    }

    .tn-slot { min-width: 0; display: flex; align-items: center; }
    .tn-slot-end { justify-content: flex-end; }

    /* الشاشات الأضيق: الشريط في سطر، والعنوان والأزرار في سطر تحته */
    @media (max-width: 1279px) {
        .tn-row {
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            grid-template-areas: "bar bar" "start end";
            row-gap: 14px;
        }

        .tn-bar { grid-area: bar; justify-self: center; }
        .tn-slot-start { grid-area: start; }
        .tn-slot-end { grid-area: end; }
    }

    /* ===== الجزيرة الداكنة — بعرض محتواها فقط ===== */
    .tn-bar {
        position: relative;
        width: fit-content;
        max-width: 100%;
        height: 64px;
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 0 10px;
        border-radius: 22px;
        background:
            radial-gradient(120% 140% at 100% 0%, rgba(212, 255, 91, .10), transparent 42%),
            radial-gradient(90% 160% at 0% 100%, rgba(180, 148, 247, .16), transparent 46%),
            linear-gradient(180deg, #202020, #141414);
        box-shadow:
            0 22px 44px -26px rgba(0, 0, 0, .65),
            inset 0 1px 0 rgba(255, 255, 255, .07);
        perspective: 1400px;
    }

    /* إطار متدرّج يدور ببطء حول الشريط */
    .tn-bar::before {
        content: '';
        position: absolute;
        inset: 0;
        padding: 1px;
        border-radius: inherit;
        background: conic-gradient(from var(--tn-angle), transparent 0 62%, rgba(212, 255, 91, .85) 72%, rgba(180, 148, 247, .85) 82%, transparent 92%);
        -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
        -webkit-mask-composite: xor;
        mask-composite: exclude;
        pointer-events: none;
        animation: tn-orbit 9s linear infinite;
    }

    @keyframes tn-orbit {
        to { --tn-angle: 360deg; }
    }

    /* ===== الشعار ===== */
    .tn-brand {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0 6px 0 10px;
        flex-shrink: 0;
    }

    .tn-logo {
        position: relative;
        width: 40px;
        height: 40px;
        border-radius: 13px;
        display: grid;
        place-items: center;
        background: linear-gradient(145deg, #E4FF8A, #C6F23E);
        color: #1A1A1A;
        box-shadow: 0 0 0 0 rgba(212, 255, 91, .5), 0 8px 18px -8px rgba(212, 255, 91, .7);
        transition: box-shadow .35s var(--tn-ease), transform .35s var(--tn-ease);
    }

    .tn-brand:hover .tn-logo {
        transform: rotate(-8deg) scale(1.05);
        box-shadow: 0 0 0 6px rgba(212, 255, 91, .12), 0 10px 24px -6px rgba(212, 255, 91, .8);
    }

    .tn-brand-name { display: block; font-size: 15px; font-weight: 800; color: #fff; line-height: 1.15; }
    .tn-brand-sub { display: block; font-size: 10px; color: rgba(255, 255, 255, .42); letter-spacing: .02em; }

    .tn-divider {
        width: 1px;
        height: 28px;
        margin: 0 6px;
        background: linear-gradient(transparent, rgba(255, 255, 255, .16), transparent);
        flex-shrink: 0;
    }

    /* ===== أزرار المجموعات + الكبسولة المنزلقة ===== */
    .tn-triggers {
        position: relative;
        display: flex;
        align-items: center;
        gap: 2px;
        height: 100%;
    }

    .tn-pill {
        position: absolute;
        top: 50%;
        left: 0;
        height: 42px;
        border-radius: 14px;
        background: rgba(255, 255, 255, .085);
        border: 1px solid rgba(255, 255, 255, .07);
        opacity: 0;
        transform: translate(var(--pill-x, 0), -50%);
        width: var(--pill-w, 0);
        transition: transform .38s var(--tn-ease), width .38s var(--tn-ease), opacity .2s ease;
        pointer-events: none;
    }

    .tn-pill.is-visible { opacity: 1; }

    .tn-trigger {
        position: relative;
        z-index: 1;
        height: 42px;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 0 14px;
        border-radius: 14px;
        font-size: 13px;
        font-weight: 700;
        color: rgba(255, 255, 255, .6);
        white-space: nowrap;
        transition: color .2s ease;
    }

    .tn-trigger:hover,
    .tn-trigger.is-open { color: #fff; }

    .tn-trigger:focus-visible { outline: 2px solid #D4FF5B; outline-offset: 2px; }
    .tn-trigger svg, .tn-trigger i { width: 17px; height: 17px; }
    .tn-trigger .tn-chevron { width: 14px; height: 14px; opacity: .55; transition: transform .3s var(--tn-ease); }
    .tn-trigger.is-open .tn-chevron { transform: rotate(180deg); opacity: 1; }

    /* المجموعة التي تحتوي الصفحة الحالية: لون ليموني ونقطة مضيئة أسفلها */
    .tn-trigger.is-current { color: #D4FF5B; }

    .tn-trigger.is-current::after {
        content: '';
        position: absolute;
        bottom: -3px;
        left: 50%;
        width: 5px;
        height: 5px;
        border-radius: 999px;
        background: #D4FF5B;
        box-shadow: 0 0 10px 2px rgba(212, 255, 91, .7);
        transform: translateX(-50%);
    }

    html.is-navigating .tn-trigger.is-current::after { animation: tn-blink .9s ease-in-out infinite; }

    @keyframes tn-blink {
        50% { opacity: .25; box-shadow: none; }
    }

    /* ===== البحث ===== */
    .tn-tools { margin-inline-start: 10px; display: flex; align-items: center; gap: 8px; }

    .tn-search { position: relative; }

    /* البحث أيقونة في الوضع العادي، ويتمدد حقلًا عند الضغط عليه */
    .tn-search input {
        width: 44px;
        height: 42px;
        padding: 0 40px 0 0;
        cursor: pointer;
        border-radius: 14px;
        border: 1px solid rgba(255, 255, 255, .08);
        background: rgba(255, 255, 255, .06);
        color: #fff;
        font-size: 13px;
        outline: none;
        transition: width .35s var(--tn-ease), background-color .2s ease, border-color .2s ease, box-shadow .2s ease;
    }

    .tn-search input::placeholder { color: transparent; }
    .tn-search input:focus::placeholder { color: rgba(255, 255, 255, .38); }

    .tn-search input:focus {
        width: 260px;
        padding-inline-end: 12px;
        cursor: text;
        background: rgba(255, 255, 255, .1);
        border-color: rgba(212, 255, 91, .45);
        box-shadow: 0 0 0 4px rgba(212, 255, 91, .1);
    }

    .tn-search .tn-search-icon {
        position: absolute;
        right: 14px;
        top: 50%;
        width: 16px;
        height: 16px;
        color: rgba(255, 255, 255, .45);
        transform: translateY(-50%);
        pointer-events: none;
    }

    /* ===== اللوحة المنبثقة: لوحة واحدة تتشكّل وتنزلق بين المجموعات ===== */
    .tn-panel {
        position: absolute;
        top: calc(100% + 14px);
        left: 0;
        width: var(--w, 640px);
        height: calc(var(--h, 300px) + 22px);
        padding: 10px;
        border-radius: 28px;
        background: rgba(255, 255, 255, .97);
        border: 1px solid rgba(26, 26, 26, .06);
        box-shadow:
            0 48px 90px -36px rgba(26, 26, 26, .5),
            0 14px 34px -18px rgba(26, 26, 26, .22);
        backdrop-filter: blur(18px);
        transform-origin: var(--arrow, 50%) 0;
        transform: translateX(var(--x, 0)) translateY(-10px) rotateX(-14deg) scale(.96);
        opacity: 0;
        visibility: hidden;
        filter: blur(6px);
        transition:
            opacity .22s ease,
            transform .34s var(--tn-ease),
            filter .3s ease,
            visibility 0s linear .34s;
    }

    .tn-panel.is-open {
        opacity: 1;
        visibility: visible;
        filter: none;
        transform: translateX(var(--x, 0)) translateY(0) rotateX(0) scale(1);
        transition:
            opacity .22s ease,
            transform .42s var(--tn-ease),
            filter .3s ease,
            visibility 0s;
    }

    /* الانتقال بين مجموعتين واللوحة مفتوحة: تتحرك وتتغيّر مقاساتها بدل أن تُغلق وتُفتح */
    .tn-panel.is-open.is-morphing {
        transition:
            opacity .22s ease,
            transform .42s var(--tn-ease),
            width .42s var(--tn-ease),
            height .42s var(--tn-ease),
            filter .3s ease;
    }

    /* جسر غير مرئي بين الشريط واللوحة حتى لا تُغلق أثناء نزول المؤشر إليها */
    .tn-panel::before {
        content: '';
        position: absolute;
        inset: -18px 0 auto;
        height: 18px;
    }

    .tn-arrow {
        position: absolute;
        top: -7px;
        left: calc(var(--arrow, 50%) - 7px);
        width: 14px;
        height: 14px;
        border-radius: 3px;
        background: #fff;
        border-top: 1px solid rgba(26, 26, 26, .06);
        border-left: 1px solid rgba(26, 26, 26, .06);
        transform: rotate(45deg);
        transition: left .42s var(--tn-ease);
    }

    .tn-viewport {
        position: relative;
        height: 100%;
        overflow: hidden;
        border-radius: 20px;
    }

    .tn-layer {
        position: absolute;
        top: 0;
        inset-inline-start: 0;
        width: max-content;
        display: flex;
        gap: 10px;
        opacity: 0;
        pointer-events: none;
        transition: opacity .25s ease, transform .42s var(--tn-ease);
    }

    /* الواجهة من اليمين لليسار: المجموعات السابقة على اليمين واللاحقة على اليسار */
    .tn-layer.is-before { transform: translateX(48px); }
    .tn-layer.is-after { transform: translateX(-48px); }

    .tn-layer.is-active {
        opacity: 1;
        transform: none;
        pointer-events: auto;
    }

    /* ===== بطاقة المجموعة ===== */
    .tn-feature {
        position: relative;
        width: 236px;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        padding: 20px;
        border-radius: 20px;
        overflow: hidden;
        color: #fff;
        background:
            radial-gradient(120% 90% at 100% 0%, rgba(212, 255, 91, .32), transparent 55%),
            radial-gradient(100% 80% at 0% 100%, rgba(180, 148, 247, .38), transparent 55%),
            #161616;
    }

    /* نقاط شبكية خافتة تتحرك ببطء */
    .tn-feature::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image: radial-gradient(rgba(255, 255, 255, .13) 1px, transparent 1px);
        background-size: 14px 14px;
        mask-image: linear-gradient(160deg, #000, transparent 70%);
        animation: tn-drift 18s linear infinite;
        pointer-events: none;
    }

    @keyframes tn-drift {
        to { background-position: 140px 140px; }
    }

    .tn-feature-watermark {
        position: absolute;
        left: -26px;
        bottom: -30px;
        width: 150px !important;
        height: 150px !important;
        color: rgba(255, 255, 255, .06);
        transform: rotate(-14deg);
        pointer-events: none;
    }

    .tn-feature-icon {
        position: relative;
        width: 46px;
        height: 46px;
        border-radius: 15px;
        display: grid;
        place-items: center;
        background: rgba(255, 255, 255, .1);
        border: 1px solid rgba(255, 255, 255, .14);
        color: #D4FF5B;
        margin-bottom: 16px;
    }

    .tn-feature-icon svg, .tn-feature-icon i { width: 22px; height: 22px; }

    .tn-feature-title { position: relative; font-size: 18px; font-weight: 800; }
    .tn-feature-text { position: relative; margin-top: 6px; font-size: 12px; line-height: 1.75; color: rgba(255, 255, 255, .62); }

    .tn-feature-count {
        position: relative;
        align-self: flex-start;
        margin-top: 12px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 10.5px;
        font-weight: 700;
        color: rgba(255, 255, 255, .75);
        background: rgba(255, 255, 255, .08);
    }

    .tn-feature-action {
        position: relative;
        margin-top: auto;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        border-radius: 13px;
        background: #D4FF5B;
        color: #1A1A1A;
        font-size: 12.5px;
        font-weight: 800;
        transition: transform .25s var(--tn-ease), box-shadow .25s ease;
    }

    .tn-feature-action:hover { transform: translateY(-2px); box-shadow: 0 10px 22px -10px rgba(212, 255, 91, .9); }
    .tn-feature-action svg, .tn-feature-action i { width: 15px; height: 15px; }

    /* ===== الأقسام ===== */
    .tn-items {
        display: grid;
        grid-template-columns: repeat(var(--cols, 2), var(--col-w, 254px));
        align-content: start;
        gap: 4px;
        padding: 6px 0;
    }

    .tn-item {
        position: relative;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        border-radius: 16px;
        color: #1f2937;
        overflow: hidden;
        transition: background-color .2s ease;
    }

    .tn-layer.is-active .tn-item {
        animation: tn-item-in .5s var(--tn-ease) both;
        animation-delay: calc(var(--i) * 45ms + 70ms);
    }

    @keyframes tn-item-in {
        from { opacity: 0; transform: translateY(10px) scale(.98); filter: blur(3px); }
        to { opacity: 1; transform: none; filter: none; }
    }

    .tn-item:hover, .tn-item:focus-visible { background: #F6F7F2; outline: none; }

    /* لمعة تعبر البطاقة عند المرور */
    .tn-item::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(100deg, transparent 30%, rgba(212, 255, 91, .22) 50%, transparent 70%);
        transform: translateX(110%);
        pointer-events: none;
    }

    .tn-item:hover::after { transform: translateX(-110%); transition: transform .7s ease; }

    .tn-item-icon {
        position: relative;
        width: 42px;
        height: 42px;
        flex-shrink: 0;
        border-radius: 14px;
        display: grid;
        place-items: center;
        transition: transform .3s var(--tn-ease), box-shadow .3s ease;
    }

    .tn-item-icon svg, .tn-item-icon i { width: 19px; height: 19px; }
    .tn-item:hover .tn-item-icon { transform: translateY(-2px) rotate(-6deg); box-shadow: 0 10px 18px -10px currentColor; }

    .tone-blue { background: #EEF4FF; color: #3B82F6; }
    .tone-purple { background: #F4EFFF; color: #8B5CF6; }
    .tone-green { background: #EEFBD9; color: #4D7C0F; }
    .tone-orange { background: #FFF2E8; color: #EA580C; }
    .tone-teal { background: #E8FAF7; color: #0D9488; }
    .tone-amber { background: #FFF7E0; color: #D97706; }
    .tone-red { background: #FFEFEF; color: #E11D48; }

    .tn-item-text { min-width: 0; flex: 1; }
    .tn-item-text b { display: block; font-size: 13.5px; font-weight: 800; color: #111827; }
    .tn-item-text small { display: block; margin-top: 1px; font-size: 11px; color: #9CA3AF; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .tn-item-go {
        width: 16px;
        height: 16px;
        color: #1A1A1A;
        opacity: 0;
        transform: translateX(8px);
        transition: opacity .2s ease, transform .3s var(--tn-ease);
    }

    .tn-item:hover .tn-item-go { opacity: 1; transform: none; }

    /* القسم الحالي: حد ليموني وشارة «أنت هنا» */
    .tn-item.is-active { background: #F7FDE8; box-shadow: inset 0 0 0 1px rgba(132, 204, 22, .35); }

    .tn-here {
        flex-shrink: 0;
        padding: 2px 8px;
        border-radius: 999px;
        background: #1A1A1A;
        color: #D4FF5B;
        font-size: 10px;
        font-weight: 800;
    }

    .tn-item:not(.is-active) .tn-here { display: none; }
    .tn-item.is-active .tn-item-go { display: none; }
    html.is-navigating .tn-item.is-active { animation: tn-blink 1s ease-in-out infinite; }

    .tn-badge {
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        border-radius: 999px;
        display: grid;
        place-items: center;
        background: #FF9B9B;
        color: #fff;
        font-size: 10px;
        font-weight: 800;
    }

    @media (max-width: 1439px) {
        .tn-trigger { padding: 0 11px; }
    }

    /* ===== عنوان الصفحة (الخانة اليمنى) ===== */
    .tn-page { min-width: 0; display: flex; align-items: center; gap: 12px; }

    .tn-page-icon {
        width: 52px;
        height: 52px;
        flex-shrink: 0;
        border-radius: 17px;
        display: grid;
        place-items: center;
        color: #D4FF5B;
        background:
            radial-gradient(90% 90% at 100% 0%, rgba(212, 255, 91, .28), transparent 60%),
            radial-gradient(90% 90% at 0% 100%, rgba(180, 148, 247, .4), transparent 60%),
            #171717;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .1), 0 14px 26px -16px rgba(0, 0, 0, .8);
        animation: tn-pop .6s cubic-bezier(.34, 1.56, .64, 1) both;
    }

    .tn-page-icon svg, .tn-page-icon i { width: 23px; height: 23px; }

    @keyframes tn-pop {
        from { opacity: 0; transform: scale(.6) rotate(-12deg); }
    }

    .tn-page-title {
        min-width: 0;
        font-size: 22px;
        line-height: 1.3;
        font-weight: 800;
        color: #111;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* خط تظليل ليموني خلف العنوان يُرسم من اليمين عند فتح الصفحة */
    .tn-page-title span {
        padding: 0 4px;
        background-image: linear-gradient(transparent 58%, rgba(212, 255, 91, .85) 58%, rgba(212, 255, 91, .85) 90%, transparent 90%);
        background-repeat: no-repeat;
        background-position: right;
        background-size: 100% 100%;
        animation: tn-mark .9s .15s cubic-bezier(.22, .9, .24, 1) both;
    }

    @keyframes tn-mark {
        from { background-size: 0% 100%; }
    }

    /* ===== أزرار الصفحة (الخانة اليسرى) ===== */
    .tn-actions { min-width: 0; display: flex; align-items: center; gap: 6px; }

    .tn-dock {
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px;
        overflow: hidden;
        border-radius: 20px;
        background: rgba(255, 255, 255, .85);
        border: 1px solid #EEF0F2;
        box-shadow: 0 14px 30px -24px rgba(26, 26, 26, .55);
        animation: tn-dock-in .55s .1s cubic-bezier(.22, .9, .24, 1) both;
    }

    .tn-dock > *, .tn-more-menu > * { flex-shrink: 0; }

    @keyframes tn-dock-in {
        from { opacity: 0; transform: translateY(-8px); }
    }

    .tn-more { position: relative; flex-shrink: 0; }

    .tn-more > summary {
        width: 46px;
        height: 46px;
        display: grid;
        place-items: center;
        border-radius: 15px;
        list-style: none;
        cursor: pointer;
        color: #D4FF5B;
        background: linear-gradient(180deg, #282828, #141414);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .09), 0 10px 20px -14px rgba(0, 0, 0, .85);
        transition: transform .2s ease;
    }

    .tn-more > summary::-webkit-details-marker { display: none; }
    .tn-more > summary svg, .tn-more > summary i { width: 20px; height: 20px; }
    .tn-more > summary:hover { transform: translateY(-2px); }
    .tn-more[open] > summary { background: #D4FF5B; color: #1A1A1A; }

    .tn-more-menu {
        position: absolute;
        z-index: 50;
        top: calc(100% + 10px);
        left: 0;
        min-width: 230px;
        display: grid;
        gap: 6px;
        padding: 8px;
        border-radius: 22px;
        background: #fff;
        border: 1px solid #EEF0F2;
        box-shadow: 0 30px 60px -24px rgba(26, 26, 26, .55);
        animation: tn-menu-in .3s cubic-bezier(.34, 1.4, .64, 1) both;
        transform-origin: top left;
    }

    .tn-more-menu .ui-btn, .tn-more-menu form, .tn-more-menu form .ui-btn { width: 100%; }

    @keyframes tn-menu-in {
        from { opacity: 0; transform: translateY(-6px) scale(.95); }
    }

    @media (prefers-reduced-motion: reduce) {
        .tn-bar::before, .tn-feature::before { animation: none; }
        .tn-panel, .tn-panel.is-open, .tn-panel.is-open.is-morphing, .tn-layer, .tn-pill { transition: opacity .15s ease, visibility 0s; }
        .tn-layer.is-active .tn-item, .tn-page-icon, .tn-page-title span, .tn-dock, .tn-more-menu { animation: none; }
    }
</style>

<script data-purpose="topnav">
    // لوحة منبثقة واحدة تنتقل بين المجموعات (مقاسها وموضعها يتغيران بسلاسة)، وكبسولة تتبع المؤشر.
    // التأخيرات الصغيرة تمنع الفتح العرضي عند المرور السريع والإغلاق عند نزول المؤشر إلى اللوحة.
    function topNav() {
        return {
            open: null,
            morphing: false,
            pill: { x: 0, w: 0, visible: false },
            panel: { x: 0, w: 640, h: 300, arrow: 320 },
            timer: null,

            enter(index, trigger) {
                clearTimeout(this.timer);
                this.movePill(trigger);
                this.timer = setTimeout(() => this.show(index), this.open === null ? 90 : 0);
            },

            leave() {
                clearTimeout(this.timer);
                this.timer = setTimeout(() => this.close(), 180);
            },

            stay() {
                clearTimeout(this.timer);
            },

            toggle(index, trigger) {
                clearTimeout(this.timer);
                if (this.open === index) return this.close();
                this.movePill(trigger);
                this.show(index);
            },

            // القياس قبل الفتح: الطبقات كلها موجودة في التخطيط (شفافة فقط)، فتظهر اللوحة
            // في مكانها الصحيح من أول لحظة بدل أن تنزلق من موضعها السابق
            show(index) {
                this.morphing = this.open !== null && this.open !== index;
                this.measure(index);
                this.open = index;
            },

            close() {
                this.open = null;
                this.morphing = false;
                this.pill.visible = false;
            },

            openAndFocus(index, trigger) {
                this.toggle(index, trigger);
                this.$nextTick(() => this.$refs['layer' + index]?.querySelector('a')?.focus());
            },

            movePill(trigger) {
                const bar = this.$refs.triggers.getBoundingClientRect();
                const box = trigger.getBoundingClientRect();
                this.pill = { x: box.left - bar.left, w: box.width, visible: true };
            },

            // المقاس من طبقة المجموعة نفسها، والموضع يتوسط زرها دون أن يخرج عن حدود الشريط
            measure(index) {
                const layer = this.$refs['layer' + index];
                const trigger = this.$refs['trigger' + index];
                if (!layer || !trigger) return;

                const bar = this.$refs.bar.getBoundingClientRect();
                const box = trigger.getBoundingClientRect();
                // حشو اللوحة 10px وحدّها 1px من كل جانب
                const width = layer.offsetWidth + 22;
                const center = box.left + box.width / 2 - bar.left;
                // اللوحة قد تكون أعرض من الشريط نفسه، فتُحصر داخل حدود الشاشة لا الشريط
                const margin = 16;
                const x = Math.min(Math.max(center - width / 2, margin - bar.left), window.innerWidth - margin - bar.left - width);

                this.panel = { x, w: width, h: layer.offsetHeight, arrow: center - x };
            },

            layerClass(index) {
                if (this.open === index) return 'is-active';
                return this.open !== null && index < this.open ? 'is-before' : 'is-after';
            },

            // «/» يركّز البحث من أي مكان ما لم يكن المستخدم يكتب في حقل
            focusSearch(event) {
                const target = event.target;
                if (event.key !== '/' || target.closest('input, textarea, select, [contenteditable]')) return;
                event.preventDefault();
                this.$refs.search.focus();
            },
        };
    }

    // أزرار الصفحة بجوار الشريط: لو لم تتسع الخانة تنتقل الأزرار الثانوية (من اليمين) إلى قائمة «المزيد»
    // واحدًا تلو الآخر حتى يتسع السطر، ويبقى آخر زر — الأساسي — ظاهرًا دائمًا.
    function actionDock() {
        return {
            order: [],

            init() {
                this.order = Array.from(this.$refs.dock.children);
                this.refit = () => requestAnimationFrame(() => this.fit());

                // تغيّر مساحة الخانة نفسها (تغيير حجم النافذة أو اتساع الشريط بفتح البحث)
                this.observer = new ResizeObserver(this.refit);
                this.observer.observe(this.$el.parentElement);

                // الخط العربي والأيقونات تُرسم بعد الإدراج فتعرّض الأزرار — نعيد القياس بعدها
                this.$nextTick(this.refit);
                document.fonts?.ready.then(this.refit);
                [300, 1200].forEach(delay => setTimeout(this.refit, delay));
            },

            destroy() {
                this.observer?.disconnect();
            },

            fit() {
                const { dock, menu, more } = this.$refs;

                this.order.forEach(action => dock.appendChild(action));
                more.hidden = true;
                more.open = false;

                while (dock.scrollWidth > dock.clientWidth + 1 && dock.children.length > 1) {
                    more.hidden = false;
                    menu.appendChild(dock.firstElementChild);
                }
            },
        };
    }

    document.addEventListener('livewire:navigate', () => {
        const nav = document.querySelector('.app-topnav');
        if (nav && window.Alpine) Alpine.$data(nav).close();
    });
</script>
