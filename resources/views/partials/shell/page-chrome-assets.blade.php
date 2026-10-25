{{-- أزرار التطبيق بنفس لغة شريط التنقل: داكن + ليموني + لمسة بنفسجية. عنوان الصفحة في partials.shell.topnav-assets. --}}
<style data-purpose="page-chrome">
    /* ===== الأزرار ===== */
    .ui-btn {
        --btn-ease: cubic-bezier(.22, .9, .24, 1);
        position: relative;
        isolation: isolate;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border-radius: 14px;
        font-weight: 800;
        white-space: nowrap;
        overflow: hidden;
        -webkit-tap-highlight-color: transparent;
        transition: transform .25s var(--btn-ease), box-shadow .25s ease, background-color .2s ease, border-color .2s ease, color .2s ease;
    }

    .ui-btn:hover { transform: translateY(-2px); }
    .ui-btn:active { transform: translateY(0) scale(.96); }
    .ui-btn:disabled, .ui-btn[aria-disabled="true"] { opacity: .5; pointer-events: none; }
    .ui-btn:focus-visible { outline: 3px solid rgba(132, 204, 22, .55); outline-offset: 2px; }

    .ui-btn-sm { min-height: 44px; padding: 0 14px; font-size: 13px; }
    .ui-btn-md { min-height: 48px; padding: 0 18px; font-size: 13px; }
    .ui-btn-lg { min-height: 50px; padding: 0 22px; font-size: 14px; }

    .ui-btn-icon { display: grid; place-items: center; flex-shrink: 0; transition: transform .35s var(--btn-ease); }
    .ui-btn-icon svg, .ui-btn-icon i { width: 16px; height: 16px; }
    .ui-btn-sm .ui-btn-icon svg, .ui-btn-sm .ui-btn-icon i { width: 15px; height: 15px; }
    .ui-btn-label { display: inline-flex; align-items: center; gap: 6px; }

    /* لمعة تعبر الزر عند المرور (الأساسي والداكن) */
    .ui-btn-primary::after,
    .ui-btn-dark::after {
        content: '';
        position: absolute;
        inset: 0;
        z-index: -1;
        background: linear-gradient(105deg, transparent 35%, rgba(255, 255, 255, .55) 50%, transparent 65%);
        transform: translateX(120%);
        pointer-events: none;
    }

    .ui-btn-dark::after { background: linear-gradient(105deg, transparent 35%, rgba(212, 255, 91, .22) 50%, transparent 65%); }
    .ui-btn-primary:hover::after, .ui-btn-dark:hover::after { transform: translateX(-120%); transition: transform .75s ease; }

    /* الأساسي: ليموني متدرّج، والأيقونة داخل دائرة داكنة */
    .ui-btn-primary {
        color: #1A1A1A;
        background: linear-gradient(160deg, #EEFFAE, #D4FF5B 45%, #BDEA33);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, .75),
            inset 0 -2px 0 rgba(0, 0, 0, .06),
            0 10px 22px -12px rgba(132, 204, 22, .9);
    }

    .ui-btn-primary:hover {
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, .75),
            inset 0 -2px 0 rgba(0, 0, 0, .06),
            0 16px 30px -12px rgba(132, 204, 22, .95);
    }

    .ui-btn-primary .ui-btn-icon {
        width: 24px;
        height: 24px;
        margin-inline-start: -6px;
        border-radius: 999px;
        background: #1A1A1A;
        color: #D4FF5B;
    }

    .ui-btn-primary.ui-btn-sm .ui-btn-icon { width: 22px; height: 22px; margin-inline-start: -4px; }
    .ui-btn-primary .ui-btn-icon svg, .ui-btn-primary .ui-btn-icon i { width: 13px; height: 13px; }
    .ui-btn-primary:hover .ui-btn-icon { transform: rotate(90deg); }

    /* الداكن: نفس جزيرة الشريط، والأيقونة ليمونية */
    .ui-btn-dark {
        color: #fff;
        background: linear-gradient(180deg, #282828, #141414);
        border: 1px solid rgba(255, 255, 255, .06);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .09), 0 10px 20px -14px rgba(0, 0, 0, .85);
    }

    .ui-btn-dark .ui-btn-icon { color: #D4FF5B; }
    .ui-btn-dark:hover { box-shadow: inset 0 1px 0 rgba(255, 255, 255, .09), 0 0 0 3px rgba(212, 255, 91, .18), 0 14px 26px -14px rgba(0, 0, 0, .9); }

    /* الثانوي: أبيض نظيف بحد خفيف */
    .ui-btn-soft {
        color: #1F2937;
        background: #fff;
        border: 1px solid #E8EAED;
        box-shadow: inset 0 1px 0 #fff, 0 6px 14px -12px rgba(26, 26, 26, .4);
    }

    .ui-btn-soft .ui-btn-icon { color: #6B7280; }
    .ui-btn-soft:hover { border-color: #CFD3D8; box-shadow: 0 12px 22px -14px rgba(26, 26, 26, .45); }
    .ui-btn-soft:hover .ui-btn-icon { color: #1A1A1A; }

    .ui-btn-outline { color: #374151; background: transparent; border: 1px solid #E5E7EB; }
    .ui-btn-outline:hover { background: #fff; border-color: #D1D5DB; }

    .ui-btn-ghost { color: #6B7280; background: transparent; }
    .ui-btn-ghost:hover { color: #1A1A1A; background: #F3F4F6; transform: none; }

    .ui-btn-danger { color: #DC2626; background: #FFF1F1; border: 1px solid #FFDCDC; }
    .ui-btn-danger:hover { background: #FFE4E4; box-shadow: 0 10px 20px -14px rgba(220, 38, 38, .6); }

    /* ===== أزرار الصفحة على الهاتف: زر «المزيد» داكن بنقاط ليمونية وقائمته بطاقة عائمة ===== */
    @media (max-width: 1023px) {
        .mobile-actions-more > summary {
            border: 0 !important;
            border-radius: 15px !important;
            background: linear-gradient(180deg, #282828, #141414) !important;
            color: #D4FF5B;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .09), 0 10px 20px -14px rgba(0, 0, 0, .85);
            letter-spacing: 1px;
            transition: transform .2s ease;
        }

        .mobile-actions-more > summary:active { transform: scale(.94); }

        .mobile-actions-more[open] > summary { background: #D4FF5B !important; color: #1A1A1A; }

        .mobile-actions-menu {
            border: 1px solid #EEF0F2 !important;
            border-radius: 22px !important;
            padding: .5rem !important;
            box-shadow: 0 30px 60px -24px rgba(26, 26, 26, .55) !important;
            animation: actions-menu-in .3s cubic-bezier(.34, 1.4, .64, 1) both;
            transform-origin: top left;
        }

        @keyframes actions-menu-in {
            from { opacity: 0; transform: translateY(-6px) scale(.95); }
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .ui-btn, .ui-btn-icon { transition: none; }
        .mobile-actions-menu { animation: none; }
        .ui-btn:hover { transform: none; }
    }
</style>
