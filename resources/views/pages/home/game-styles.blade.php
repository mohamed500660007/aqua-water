<style>
    .tj {
        position: relative;
        margin-top: 16px;
        overflow: hidden;
        border-radius: 26px;
        background: #121316;
        color: #fff;
        box-shadow: 0 24px 50px -30px rgba(18, 19, 22, .6);
        user-select: none;
        -webkit-user-select: none;
    }

    /* ===== الشريط العلوي ===== */
    .tj-bar {
        position: relative;
        z-index: 5;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 14px 16px 12px;
        background:
            radial-gradient(70% 140% at 100% 0%, rgba(250, 204, 21, .18), transparent 60%),
            radial-gradient(60% 140% at 0% 0%, rgba(180, 148, 247, .22), transparent 60%);
    }

    .tj-title { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .tj-title b { display: block; font-size: 17px; font-weight: 900; letter-spacing: -.01em; }
    .tj-title small { display: block; font-size: 12px; color: rgba(255, 255, 255, .6); }
    .tj-logo { flex-shrink: 0; animation: tj-logo 2.4s ease-in-out infinite; }

    @keyframes tj-logo { 50% { transform: rotate(-8deg) scale(1.06); } }

    .tj-hud, .tj-stats { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; }

    .tj-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        height: 36px;
        padding: 0 12px;
        border-radius: 12px;
        background: rgba(255, 255, 255, .08);
        font-size: 12.5px;
        font-weight: 800;
        color: rgba(255, 255, 255, .75);
        font-variant-numeric: tabular-nums;
    }

    .tj-chip b { color: #fff; font-size: 15px; }
    .tj-chip--score { background: #FACC15; color: #1A1A1A; }
    .tj-chip--score b { color: #1A1A1A; font-size: 17px; }
    .tj-lives span { color: #F43F5E; font-size: 16px; transition: opacity .3s, transform .3s; }
    .tj-lives span.is-lost { opacity: .2; transform: scale(.7); }
    .tj-inventory { background: rgba(212, 255, 91, .16); color: #D4FF5B; }

    .tj-btn {
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 0 14px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 900;
        transition: transform .15s ease, background-color .2s ease;
    }

    .tj-btn:active { transform: scale(.94); }
    .tj-btn--go { background: #D4FF5B; color: #1A1A1A; box-shadow: 0 8px 22px -10px rgba(212, 255, 91, .9); }
    .tj-btn--icon { width: 36px; padding: 0; background: rgba(255, 255, 255, .08); color: #fff; font-size: 15px; }

    /* ===== المسرح: أوضة معيشة كرتونية ===== */
    .tj-stage {
        position: relative;
        height: 320px;
        overflow: hidden;
        outline: none;
        cursor: crosshair;
        touch-action: pan-y;
        direction: ltr;
    }

    .tj.is-playing .tj-stage { cursor: none; touch-action: none; }

    @media (max-width: 639px) {
        .tj-stage { height: 280px; }
        .tj-clock, .tj-frame { display: none; }
        .tj-window { right: 26%; }
    }

    .tj-wall {
        position: absolute;
        inset: 0 0 52% 0;
        background:
            repeating-linear-gradient(90deg, #FDE68A 0 22px, #FCD34D 22px 26px),
            #FDE68A;
        box-shadow: inset 0 -12px 0 #92400E, inset 0 -16px 0 #78350F;
    }

    .tj-frame {
        position: absolute;
        left: 22%;
        top: 22px;
        width: 70px;
        height: 54px;
        border: 6px solid #92400E;
        border-radius: 6px;
        background: linear-gradient(#93C5FD, #BBF7D0);
        transform: rotate(-3deg);
        animation: tj-swing 3.2s ease-in-out infinite;
        transform-origin: 50% -10px;
    }

    .tj-frame span {
        position: absolute;
        inset: auto 8px 6px;
        height: 18px;
        border-radius: 50% 50% 0 0;
        background: #4ADE80;
    }

    @keyframes tj-swing { 50% { transform: rotate(3deg); } }

    .tj-window {
        position: absolute;
        right: 34%;
        top: 16px;
        width: 92px;
        height: 72px;
        border: 6px solid #FFFBEB;
        border-radius: 8px;
        background: linear-gradient(#38BDF8, #BAE6FD);
        box-shadow: 0 0 0 3px #92400E;
    }

    .tj-window::before, .tj-window::after { content: ''; position: absolute; background: #FFFBEB; }
    .tj-window::before { left: 50%; top: 0; bottom: 0; width: 5px; translate: -50% 0; }
    .tj-window::after { top: 50%; left: 0; right: 0; height: 5px; translate: 0 -50%; }

    .tj-window span {
        position: absolute;
        top: 8px;
        width: 30px;
        height: 12px;
        border-radius: 999px;
        background: #fff;
        animation: tj-cloud 14s linear infinite;
    }

    @keyframes tj-cloud { from { left: -34px; } to { left: 100%; } }

    .tj-clock {
        position: absolute;
        left: 46%;
        top: 30px;
        width: 40px;
        height: 40px;
        border: 5px solid #92400E;
        border-radius: 50%;
        background: #FFFBEB;
    }

    .tj-clock-hand {
        position: absolute;
        left: 50%;
        top: 50%;
        width: 3px;
        height: 13px;
        margin-left: -1.5px;
        border-radius: 2px;
        background: #1A1A1A;
        transform-origin: 50% 0;
        animation: tj-tick 6s steps(12) infinite;
    }

    @keyframes tj-tick { to { transform: rotate(360deg); } }

    .tj-floor {
        position: absolute;
        inset: 48% 0 0 0;
        background:
            repeating-linear-gradient(90deg, rgba(0, 0, 0, .12) 0 2px, transparent 2px 90px),
            linear-gradient(#B45309, #92400E);
    }

    .tj-rug {
        position: absolute;
        left: 50%;
        top: 46%;
        width: 46%;
        height: 40%;
        translate: -50% 0;
        border-radius: 50%;
        background: repeating-radial-gradient(#DC2626 0 10px, #B91C1C 10px 20px);
        opacity: .55;
    }

    .tj-hole {
        position: absolute;
        right: 26px;
        top: calc(48% - 46px);
        width: 46px;
        height: 46px;
        border-radius: 26px 26px 0 0;
        background: #0B0B0C;
        box-shadow: 0 -4px 0 #78350F, inset 0 6px 0 rgba(255, 255, 255, .06);
    }

    .tj-hole.is-hit { animation: tj-shake .4s ease; }

    /* الجحر مقفول مؤقتًا: باب خشب صغير */
    .tj-hole.is-closed { background: repeating-linear-gradient(90deg, #A16207 0 8px, #854D0E 8px 10px); }

    .tj-doghouse {
        position: absolute;
        left: 14px;
        top: calc(48% - 66px);
        width: 78px;
        height: 70px;
        background: #DC2626;
        clip-path: polygon(50% 0, 100% 38%, 100% 100%, 0 100%, 0 38%);
    }

    .tj-doghouse::after {
        content: '';
        position: absolute;
        left: 50%;
        bottom: 0;
        width: 32px;
        height: 34px;
        translate: -50% 0;
        border-radius: 16px 16px 0 0;
        background: #1A1A1A;
    }

    .tj-doghouse span {
        position: absolute;
        top: 22px;
        left: 50%;
        translate: -50% 0;
        font-size: 8px;
        font-weight: 900;
        letter-spacing: .1em;
        color: #FEF3C7;
    }

    /* ===== الشخصيات ===== */
    .tj-actor {
        --face: 1;
        position: absolute;
        left: 0;
        top: 0;
        transform-origin: 50% 100%;
        will-change: transform;
    }

    .tj-actor.is-left { --face: -1; }
    .tj-body { transform-origin: 50% 100%; transform: scaleX(var(--face)); }
    .tj-art, .tj-sprite { display: block; overflow: visible; pointer-events: none; }
    .tj-tom .tj-sprite { width: 132px; height: auto; }
    .tj-jerry .tj-sprite { width: 70px; height: auto; }
    .tj-spike .tj-sprite { width: 138px; height: auto; }
    .tj-pose-flat { display: none; }

    .tj-shadow {
        position: absolute;
        left: 16%;
        right: 16%;
        bottom: -4px;
        height: 10px;
        border-radius: 50%;
        background: rgba(0, 0, 0, .3);
    }

    .tj-leg { transform-box: fill-box; transform-origin: 50% 8%; }
    .tj-tail { transform-box: fill-box; transform-origin: 100% 100%; animation: tj-wag 1s ease-in-out infinite alternate; }
    .is-running .tj-leg-a { animation: tj-leg .22s ease-in-out infinite alternate; }
    .is-running .tj-leg-b { animation: tj-leg .22s ease-in-out infinite alternate-reverse; }
    .is-running .tj-body { animation: tj-bob .22s ease-in-out infinite alternate; }
    .is-running .tj-tail { animation-duration: .28s; }

    @keyframes tj-leg { from { transform: rotate(38deg); } to { transform: rotate(-38deg); } }
    @keyframes tj-bob { from { transform: scaleX(var(--face)) translateY(0); } to { transform: scaleX(var(--face)) translateY(-5px) rotate(-3deg); } }
    @keyframes tj-wag { from { transform: rotate(-12deg); } to { transform: rotate(14deg); } }

    /* القط اتفرطح (مقلاة / حيطة) — نجوم بتلف فوق راسه */
    .tj-actor.is-stunned .tj-body { animation: none; transform: scaleX(var(--face)) scale(1.4, .34); transition: transform .12s cubic-bezier(.3, 1.8, .5, 1); }
    .tj-actor.is-stunned:has(.tj-pose-flat) .tj-pose-normal { display: none; }
    .tj-actor.is-stunned .tj-pose-flat { display: block; }
    .tj-stars { position: absolute; left: 50%; top: 22%; width: 0; height: 0; display: none; }
    .is-stunned .tj-stars, .is-dizzy .tj-stars { display: block; animation: tj-orbit .9s linear infinite; }
    .tj-stars i { position: absolute; color: #FACC15; font-style: normal; font-size: 18px; text-shadow: 0 0 6px rgba(250, 204, 21, .8); }
    .tj-stars i:nth-child(1) { transform: translate(-30px, -8px); }
    .tj-stars i:nth-child(2) { transform: translate(18px, -14px); }
    .tj-stars i:nth-child(3) { transform: translate(-4px, 8px); }

    @keyframes tj-orbit { from { transform: rotateY(0) rotate(0); } to { transform: rotate(360deg); } }

    /* اتزحلق على الموز: يلف في الهوا (الارتفاع من الـ JS) */
    .tj-actor.is-slip .tj-body { animation: tj-spin .5s linear infinite; }
    @keyframes tj-spin { from { transform: scaleX(var(--face)) rotate(0); } to { transform: scaleX(var(--face)) rotate(-360deg); } }

    /* صباعه في المصيدة: ينط على رجل واحدة */
    .tj-actor.is-hurt .tj-body { animation: tj-ouch .2s ease-in-out infinite alternate; }
    @keyframes tj-ouch { from { transform: scaleX(var(--face)) translateY(0) rotate(-6deg); } to { transform: scaleX(var(--face)) translateY(-22px) rotate(8deg); } }

    /* الكلب وراه: بيترعش من الخضة */
    .tj-actor.is-scared .tj-body { animation: tj-shiver .08s linear infinite alternate; }
    @keyframes tj-shiver { from { transform: scaleX(var(--face)) translateX(-2px) scale(1.04, .96); } to { transform: scaleX(var(--face)) translateX(2px) scale(.96, 1.06); } }

    /* مسك الفار: نطة فرحة */
    .tj-actor.is-gloat .tj-body { animation: tj-gloat .3s ease-in-out infinite alternate; }
    @keyframes tj-gloat { to { transform: scaleX(var(--face)) translateY(-12px) scale(1.06); } }

    .tj-actor.is-hidden { opacity: 0; transition: opacity .12s; }
    .tj-actor.is-safe { animation: tj-blink .18s steps(2) infinite; }
    @keyframes tj-blink { 50% { opacity: .35; } }

    .tj-held { position: absolute; top: -16px; left: 50%; translate: -50% 0; display: none; font-size: 20px; animation: tj-held 0.5s ease-in-out infinite alternate; }
    .tj-jerry.has-pan .tj-held { display: block; }
    @keyframes tj-held { to { transform: rotate(-24deg) translateY(-3px); } }

    .tj-spike.is-home { opacity: 0; pointer-events: none; }

    /* ===== حاجات على الأرض ===== */
    .tj-obj {
        position: absolute;
        translate: -50% -100%;
        font-size: 24px;
        line-height: 1;
        filter: drop-shadow(0 4px 2px rgba(0, 0, 0, .35));
        animation: tj-drop .4s cubic-bezier(.3, 1.7, .5, 1);
        pointer-events: none;
    }

    .tj-obj--gold { font-size: 28px; filter: drop-shadow(0 0 8px rgba(250, 204, 21, .9)); animation: tj-drop .4s cubic-bezier(.3, 1.7, .5, 1), tj-glow 1s ease-in-out infinite alternate; }
    .tj-obj--item { font-size: 28px; animation: tj-drop .4s cubic-bezier(.3, 1.7, .5, 1), tj-float 1s ease-in-out infinite alternate; }
    .tj-obj--item.is-expiring { animation: tj-blink .2s steps(2) infinite; }
    .tj-obj--peel { font-size: 22px; transform: rotate(-35deg) scaleY(.7); filter: grayscale(.2) drop-shadow(0 3px 1px rgba(0, 0, 0, .35)); }
    .tj-obj--trap { font-size: 26px; }

    @keyframes tj-drop { from { transform: translateY(-50px) scale(.4); opacity: 0; } }
    @keyframes tj-float { to { transform: translateY(-7px); } }
    @keyframes tj-glow { to { transform: scale(1.12); } }

    /* ===== المؤثرات ===== */
    .tj-fx {
        position: absolute;
        direction: rtl;
        z-index: 2000;
        translate: -50% -50%;
        pointer-events: none;
        white-space: nowrap;
        font-weight: 900;
        animation: tj-pop 1s cubic-bezier(.2, 1.4, .4, 1) forwards;
    }

    .tj-fx--word {
        font-size: 26px;
        color: #FACC15;
        -webkit-text-stroke: 6px #1A1A1A;
        paint-order: stroke fill;
        transform-origin: 50% 100%;
    }

    .tj-fx--small { font-size: 15px; color: #fff; -webkit-text-stroke: 4px #1A1A1A; paint-order: stroke fill; }
    .tj-fx--boom { width: 90px; height: 90px; border-radius: 50%; background: radial-gradient(circle, #FEF08A 0 30%, #FB923C 31% 55%, transparent 56%); animation-name: tj-boom; }
    .tj-fx--dust { width: 18px; height: 18px; border-radius: 50%; background: rgba(255, 255, 255, .75); animation: tj-dust .5s ease-out forwards; }

    @keyframes tj-pop {
        0% { opacity: 0; transform: scale(.2) rotate(-10deg); }
        25% { opacity: 1; transform: scale(1.25) rotate(4deg); }
        45% { transform: scale(1) rotate(-2deg); }
        80% { opacity: 1; }
        100% { opacity: 0; transform: translateY(-30px) scale(1); }
    }

    @keyframes tj-boom { from { opacity: 1; transform: scale(.2); } to { opacity: 0; transform: scale(1.6); } }
    @keyframes tj-dust { from { opacity: .9; transform: scale(.4); } to { opacity: 0; transform: scale(1.5) translate(var(--dx, -14px), -8px); } }
    @keyframes tj-shake { 20%, 60% { transform: translateX(-5px); } 40%, 80% { transform: translateX(5px); } }

    .tj-stage.is-shaking { animation: tj-shake .35s ease; }

    .tj-cursor {
        position: absolute;
        left: 0;
        top: 0;
        z-index: 1900;
        width: 30px;
        height: 30px;
        margin: -15px 0 0 -15px;
        border: 3px dashed #D4FF5B;
        border-radius: 50%;
        pointer-events: none;
        animation: tj-cursor 2.5s linear infinite;
    }

    @keyframes tj-cursor { to { rotate: 360deg; } }

    .tj-banner {
        position: absolute;
        direction: rtl;
        z-index: 2100;
        left: 50%;
        top: 34%;
        translate: -50% -50%;
        padding: 8px 22px;
        border-radius: 16px;
        background: #1A1A1A;
        color: #D4FF5B;
        font-size: 24px;
        font-weight: 900;
        white-space: nowrap;
        box-shadow: 0 0 0 4px #D4FF5B;
        animation: tj-pop 1.6s cubic-bezier(.2, 1.4, .4, 1) forwards;
        pointer-events: none;
    }

    .tj-throw {
        position: absolute;
        z-index: 2100;
        right: 12px;
        bottom: 12px;
        height: 42px;
        padding: 0 16px;
        border-radius: 14px;
        background: #FACC15;
        color: #1A1A1A;
        font-size: 14px;
        font-weight: 900;
        box-shadow: 0 10px 20px -10px rgba(0, 0, 0, .6);
    }

    .tj-invite {
        position: absolute;
        z-index: 2050;
        left: 50%;
        bottom: 12px;
        translate: -50% 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
        width: max-content;
        max-width: calc(100% - 24px);
        padding: 8px 16px;
        border-radius: 16px;
        background: rgba(18, 19, 22, .82);
        text-align: center;
        pointer-events: none;
        direction: rtl;
    }

    .tj-invite b { font-size: 14px; color: #D4FF5B; }
    .tj-invite span { font-size: 11.5px; color: rgba(255, 255, 255, .75); }

    .tj-overlay {
        position: absolute;
        inset: 0;
        z-index: 2200;
        display: grid;
        place-items: center;
        background: rgba(18, 19, 22, .55);
        backdrop-filter: blur(3px);
        direction: rtl;
    }

    .tj-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        padding: 18px 28px;
        border-radius: 22px;
        background: #1A1A1A;
        box-shadow: 0 0 0 3px #D4FF5B, 0 24px 40px -18px rgba(0, 0, 0, .7);
        text-align: center;
        animation: tj-pop-in .45s cubic-bezier(.3, 1.6, .5, 1);
    }

    .tj-card big { font-size: 40px; font-weight: 900; line-height: 1.1; color: #FACC15; }
    .tj-card small { font-size: 12.5px; color: rgba(255, 255, 255, .7); }

    @keyframes tj-pop-in { from { transform: scale(.5); opacity: 0; } }

    @media (prefers-reduced-motion: reduce) {
        .tj-logo, .tj-frame, .tj-window span, .tj-clock-hand, .tj-tail, .tj-obj--item, .tj-obj--gold { animation: none; }
    }
</style>
