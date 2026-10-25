{{-- أنماط وسلوك تنقل الهاتف: ترويسة داكنة، شريط سفلي بمؤشر منزلق وزر قائمة مرتفع، ولوحة قائمة كاملة.
     نفس لغة شريط سطح المكتب (partials.shell.topnav-assets) — ألوان القطع tone-* معرّفة هناك. --}}
<style data-purpose="mobile-nav">
    .m-island {
        --m-ease: cubic-bezier(.22, .9, .24, 1);
        --m-spring: cubic-bezier(.34, 1.56, .64, 1);
        position: relative;
        border-radius: 22px;
        background:
            radial-gradient(120% 160% at 100% 0%, rgba(212, 255, 91, .12), transparent 45%),
            radial-gradient(90% 180% at 0% 100%, rgba(180, 148, 247, .2), transparent 50%),
            linear-gradient(180deg, #202020, #141414);
        box-shadow: 0 18px 40px -22px rgba(0, 0, 0, .7), inset 0 1px 0 rgba(255, 255, 255, .07);
    }

    /* نفس الإطار المتدرّج الدوّار في شريط سطح المكتب */
    .m-island::before {
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

    /* ===== الترويسة ===== */
    .m-header {
        display: flex;
        align-items: center;
        gap: 9px;
        height: 52px;
        padding: 0 6px 0 7px;
    }

    .m-logo {
        width: 36px;
        height: 36px;
        flex-shrink: 0;
        border-radius: 12px;
        display: grid;
        place-items: center;
        background: linear-gradient(145deg, #E4FF8A, #C6F23E);
        color: #1A1A1A;
        box-shadow: 0 8px 18px -8px rgba(212, 255, 91, .7);
    }

    .m-title { min-width: 0; flex: 1; }
    .m-title small { display: block; font-size: 11px; line-height: 1.3; color: rgba(255, 255, 255, .5); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .m-title strong { display: block; font-size: 15px; line-height: 1.3; font-weight: 800; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .m-icon-btn {
        width: 40px;
        height: 40px;
        flex-shrink: 0;
        border-radius: 13px;
        display: grid;
        place-items: center;
        color: #fff;
        background: rgba(255, 255, 255, .08);
        border: 1px solid rgba(255, 255, 255, .08);
        transition: transform .2s var(--m-ease), background-color .2s ease;
    }

    .m-icon-btn:active { transform: scale(.92); background: rgba(255, 255, 255, .14); }

    /* ===== الشريط السفلي ===== */
    .m-dock {
        display: grid;
        grid-template-columns: 1fr 1fr 70px 1fr 1fr;
        align-items: center;
        height: 60px;
        padding: 0 6px;
    }

    .m-indicator {
        position: absolute;
        top: 7px;
        left: 0;
        height: 46px;
        border-radius: 15px;
        background: linear-gradient(160deg, #E4FF8A, #CBF547);
        box-shadow: 0 10px 22px -10px rgba(212, 255, 91, .9);
        opacity: 0;
        transform: translateX(var(--ind-x, 0)) scale(.6);
        width: var(--ind-w, 0);
        transition: transform .5s var(--m-spring), width .4s var(--m-ease), opacity .2s ease;
        pointer-events: none;
    }

    .m-indicator.is-visible { opacity: 1; transform: translateX(var(--ind-x, 0)) scale(1); }

    .m-tab {
        position: relative;
        z-index: 1;
        height: 46px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 2px;
        border-radius: 15px;
        color: rgba(255, 255, 255, .58);
        -webkit-tap-highlight-color: transparent;
        transition: color .25s ease;
    }

    .m-tab svg, .m-tab i { width: 19px; height: 19px; transition: transform .4s var(--m-spring); }
    .m-tab span { font-size: 11px; font-weight: 700; line-height: 1; }
    .m-tab:active svg { transform: scale(.85); }
    .m-tab.is-active { color: #1A1A1A; }
    .m-tab.is-active svg { transform: translateY(-1px); }
    html.is-navigating .m-tab.is-active { animation: tn-blink 1s ease-in-out infinite; }

    /* زر القائمة المرتفع في المنتصف */
    .m-fab-slot { position: relative; height: 100%; display: flex; justify-content: center; }

    .m-fab {
        position: absolute;
        top: -18px;
        width: 56px;
        height: 56px;
        border-radius: 20px;
        display: grid;
        place-items: center;
        color: #1A1A1A;
        background: linear-gradient(150deg, #EEFFB0, #C6F23E 60%, #A8DC1F);
        box-shadow: 0 0 0 6px #FAFAFA, 0 16px 30px -12px rgba(132, 204, 22, .9);
        -webkit-tap-highlight-color: transparent;
        transition: transform .45s var(--m-spring), border-radius .45s var(--m-ease);
    }

    .m-fab::after {
        content: '';
        position: absolute;
        inset: -6px;
        border-radius: 24px;
        border: 2px solid rgba(212, 255, 91, .55);
        animation: m-ripple 2.8s ease-out infinite;
        pointer-events: none;
    }

    @keyframes m-ripple {
        0% { opacity: .9; transform: scale(.92); }
        70%, 100% { opacity: 0; transform: scale(1.25); }
    }

    .m-fab svg, .m-fab i { width: 24px; height: 24px; transition: transform .45s var(--m-spring); }
    .m-fab:active { transform: scale(.9); }
    .m-fab.is-open { border-radius: 50%; transform: rotate(0); }
    .m-fab.is-open svg { transform: rotate(135deg); }
    .m-fab.is-open::after { animation: none; opacity: 0; }

    /* الصفحة الحالية داخل القائمة لا في الشريط: نقطة على الزر */
    .m-fab-dot {
        position: absolute;
        top: 6px;
        left: 6px;
        width: 11px;
        height: 11px;
        border-radius: 999px;
        background: #1A1A1A;
        box-shadow: 0 0 0 2px #D4FF5B;
    }

    /* ===== لوحة القائمة ===== */
    .m-backdrop {
        position: fixed;
        inset: 0;
        z-index: 48;
        background: rgba(20, 20, 20, .5);
        backdrop-filter: blur(6px);
    }

    .m-sheet {
        position: fixed;
        inset-inline: 0;
        bottom: 0;
        z-index: 49;
        max-height: 86dvh;
        display: flex;
        flex-direction: column;
        border-radius: 30px 30px 0 0;
        background: #FAFAFA;
        box-shadow: 0 -30px 60px -30px rgba(0, 0, 0, .55);
        transition: transform .45s cubic-bezier(.22, .9, .24, 1);
        will-change: transform;
    }

    .m-sheet.is-dragging { transition: none; }

    .m-sheet-head {
        position: relative;
        flex-shrink: 0;
        padding: 12px 16px 14px;
        border-radius: 30px 30px 0 0;
        overflow: hidden;
        color: #fff;
        background:
            radial-gradient(110% 140% at 100% 0%, rgba(212, 255, 91, .25), transparent 55%),
            radial-gradient(100% 140% at 0% 100%, rgba(180, 148, 247, .35), transparent 55%),
            #161616;
        touch-action: none;
    }

    .m-sheet-head::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image: radial-gradient(rgba(255, 255, 255, .12) 1px, transparent 1px);
        background-size: 14px 14px;
        mask-image: linear-gradient(170deg, #000, transparent 75%);
        pointer-events: none;
    }

    .m-handle { width: 42px; height: 5px; margin: 0 auto 12px; border-radius: 999px; background: rgba(255, 255, 255, .28); }

    .m-sheet-title { position: relative; display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
    .m-sheet-title strong { font-size: 19px; font-weight: 800; }
    .m-sheet-title small { display: block; font-size: 12px; color: rgba(255, 255, 255, .55); }

    .m-search { position: relative; }

    .m-search input {
        width: 100%;
        height: 48px;
        padding: 0 46px 0 14px;
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, .12);
        background: rgba(255, 255, 255, .09);
        color: #fff;
        font-size: 16px;
        outline: none;
    }

    .m-search input::placeholder { color: rgba(255, 255, 255, .45); }
    .m-search input:focus { border-color: rgba(212, 255, 91, .55); box-shadow: 0 0 0 4px rgba(212, 255, 91, .12); }
    .m-search .m-search-icon { position: absolute; right: 15px; top: 50%; width: 18px; height: 18px; color: rgba(255, 255, 255, .5); transform: translateY(-50%); pointer-events: none; }

    /* الاختصارات الثلاثة بأعمدة متساوية فتظهر كاملة على أضيق الشاشات */
    .m-actions { position: relative; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; margin-top: 12px; }

    .m-action {
        min-width: 0;
        height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        padding: 0 6px;
        border-radius: 13px;
        background: #D4FF5B;
        color: #1A1A1A;
        font-size: 12.5px;
        font-weight: 800;
        white-space: nowrap;
    }

    .m-action:active { transform: scale(.95); }
    .m-action svg, .m-action i { width: 16px; height: 16px; }

    .m-sheet-body {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
        padding: 14px 14px calc(20px + env(safe-area-inset-bottom, 0px));
    }

    .m-group + .m-group { margin-top: 18px; }

    .m-group-head { display: flex; align-items: center; gap: 10px; padding: 0 4px 10px; }

    .m-group-icon {
        width: 34px;
        height: 34px;
        flex-shrink: 0;
        border-radius: 11px;
        display: grid;
        place-items: center;
        background: #1A1A1A;
        color: #D4FF5B;
    }

    .m-group-icon svg, .m-group-icon i { width: 17px; height: 17px; }
    .m-group-head strong { display: block; font-size: 15px; font-weight: 800; color: #111827; }
    .m-group-head small { display: block; font-size: 12px; color: #9CA3AF; line-height: 1.4; }

    .m-tiles { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }

    .m-tile {
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 13px;
        border-radius: 20px;
        background: #fff;
        border: 1px solid #EEF0F2;
        box-shadow: 0 6px 16px -14px rgba(26, 26, 26, .5);
        -webkit-tap-highlight-color: transparent;
        transition: transform .2s var(--m-ease, ease);
    }

    .m-sheet.is-open .m-tile {
        animation: m-tile-in .55s cubic-bezier(.34, 1.4, .64, 1) both;
        animation-delay: calc(var(--i) * 35ms + 120ms);
    }

    @keyframes m-tile-in {
        from { opacity: 0; transform: translateY(18px) scale(.94); }
        to { opacity: 1; transform: none; }
    }

    .m-tile:active { transform: scale(.96); }
    .m-tile .tn-item-icon { width: 42px; height: 42px; border-radius: 14px; }
    .m-tile b { display: block; font-size: 14px; font-weight: 800; color: #111827; }
    .m-tile small { display: block; margin-top: 2px; font-size: 12px; line-height: 1.45; color: #9CA3AF; }

    .m-tile.is-active { background: #F7FDE8; border-color: rgba(132, 204, 22, .45); }

    .m-tile .m-here {
        position: absolute;
        top: 12px;
        left: 12px;
        padding: 2px 8px;
        border-radius: 999px;
        background: #1A1A1A;
        color: #D4FF5B;
        font-size: 11px;
        font-weight: 800;
    }

    .m-tile:not(.is-active) .m-here { display: none; }

    @media (prefers-reduced-motion: reduce) {
        .m-island::before, .m-fab::after, .m-sheet-head::before { animation: none; }
        .m-sheet.is-open .m-tile { animation: none; }
        .m-indicator, .m-sheet { transition: opacity .15s ease; }
    }
</style>

<script data-purpose="mobile-nav">
    // الشريط السفلي: مؤشر ليموني ينزلق للتبويب النشط، ولوحة قائمة تُسحب للأسفل لإغلاقها.
    function mobileNav(primaryHrefs) {
        return {
            indicator: { x: 0, w: 0, visible: false },
            drag: { startY: null, offset: 0 },

            init() {
                // إعادة الحساب عند تغيّر الصفحة (store.nav) أو مقاس الشاشة
                this.$watch('$store.nav.path', () => this.$nextTick(() => this.placeIndicator()));
                this.$watch('$store.more.open', open => this.lockScroll(open));
                window.addEventListener('resize', () => this.placeIndicator());
                this.$nextTick(() => this.placeIndicator());
            },

            get activeIndex() {
                return primaryHrefs.findIndex(href => this.$store.nav.isActive(href));
            },

            get currentInMenu() {
                return this.activeIndex === -1 && this.$store.nav.bestMatch !== '';
            },

            placeIndicator() {
                const tab = this.$refs['tab' + this.activeIndex];
                if (!tab) return (this.indicator.visible = false);

                const inset = 4;
                this.indicator = { x: tab.offsetLeft + inset, w: tab.offsetWidth - inset * 2, visible: true };
            },

            // المحتوى يتمرر داخل #appMain لا الصفحة، فهو ما يُقفل خلف اللوحة
            lockScroll(open) {
                const main = document.getElementById('appMain');
                if (main) main.style.overflowY = open ? 'hidden' : '';
                if (!open) this.drag = { startY: null, offset: 0 };
            },

            openMenu(focusSearch = false) {
                this.$store.more.show();
                if (focusSearch) setTimeout(() => this.$refs.menuSearch?.focus(), 320);
            },

            dragStart(event) {
                this.drag = { startY: event.touches[0].clientY, offset: 0 };
            },

            dragMove(event) {
                if (this.drag.startY === null) return;
                this.drag.offset = Math.max(0, event.touches[0].clientY - this.drag.startY);
            },

            // سحب لأكثر من 90px يغلق اللوحة، وأقل من ذلك يعيدها لمكانها
            dragEnd() {
                if (this.drag.offset > 90) this.$store.more.close();
                this.drag = { startY: null, offset: 0 };
            },
        };
    }
</script>
