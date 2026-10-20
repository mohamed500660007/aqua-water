@extends('layouts.app')

@section('title', 'الرئيسية')
@section('page-title', 'الرئيسية')
@section('page-subtitle', 'مرحبًا بك في لوحة تحكم أكوا ووتر')

@php
    $quickActions = [
        ['route' => 'contracts.create', 'icon' => 'file-plus', 'label' => 'عقد جديد', 'variant' => 'primary'],
        ['route' => 'customers.create', 'icon' => 'user-plus', 'label' => 'عميل جديد', 'variant' => 'glass'],
        ['route' => 'complaints.create', 'icon' => 'message-square-plus', 'label' => 'شكوى جديدة', 'variant' => 'glass'],
    ];
    $navItems = collect($navGroups)->flatMap(fn (array $group) => $group['items']);
    $navIcons = $navItems->pluck('icon', 'route');
    $tileIndex = 0;
@endphp

@section('content')
    {{-- الأنماط داخل المحتوى لا في <head>: تُحمَّل مع الصفحة نفسها عند التنقّل بلا إعادة تحميل --}}
    <style data-purpose="home">
        .home-reveal {
            animation: home-rise .7s cubic-bezier(.22, .9, .24, 1) var(--delay, 0s) both;
        }

        /* أثناء شاشة الترحيب تنتظر الحركات حتى ترتفع الشاشة، فتبدأ أمام المستخدم لا خلفها */
        html.aq-splash-active .home-reveal {
            animation-play-state: paused;
        }

        @keyframes home-rise {
            from { opacity: 0; transform: translateY(18px) scale(.985); filter: blur(4px); }
            to { opacity: 1; transform: none; filter: none; }
        }

        /* ===== بطاقة الترحيب ===== */
        .home-hero {
            position: relative;
            isolation: isolate;
            overflow: hidden;
            border-radius: 28px;
            padding: 28px;
            color: #fff;
            background:
                radial-gradient(70% 90% at 100% 0%, rgba(212, 255, 91, .16), transparent 55%),
                radial-gradient(60% 90% at 0% 100%, rgba(180, 148, 247, .28), transparent 60%),
                radial-gradient(40% 60% at 30% 10%, rgba(56, 189, 248, .14), transparent 70%),
                #121316;
            box-shadow: 0 30px 60px -30px rgba(18, 19, 22, .55);
        }

        .home-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: -1;
            background-image: radial-gradient(rgba(255, 255, 255, .09) 1px, transparent 1px);
            background-size: 22px 22px;
            mask-image: linear-gradient(to bottom, #000, transparent 85%);
        }

        @media (min-width: 1024px) {
            .home-hero { padding: 40px 44px 36px; }
        }

        .home-hero-waves {
            position: absolute;
            inset-inline: 0;
            bottom: 0;
            z-index: -1;
            height: 120px;
            pointer-events: none;
        }

        .home-hero-waves svg {
            position: absolute;
            bottom: 0;
            width: 200%;
            height: 100%;
            animation: home-wave 14s linear infinite;
        }

        .home-hero-waves svg + svg {
            height: 70%;
            animation-duration: 9s;
            animation-direction: reverse;
        }

        @keyframes home-wave {
            to { transform: translateX(50%); }
        }

        .home-emblem {
            position: relative;
            width: 112px;
            height: 112px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            border-radius: 32px;
            background: linear-gradient(160deg, rgba(255, 255, 255, .1), rgba(255, 255, 255, .02));
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .1), 0 20px 40px -20px rgba(56, 189, 248, .6);
            animation: home-float 5s ease-in-out infinite;
        }

        .home-emblem::after {
            content: '';
            position: absolute;
            inset: -14px;
            z-index: -1;
            border-radius: 40px;
            background: radial-gradient(circle, rgba(56, 189, 248, .35), transparent 65%);
            filter: blur(10px);
        }

        .home-emblem .brand-logo-ring {
            transform-origin: 48px 48px;
            animation: home-spin 16s linear infinite;
        }

        @keyframes home-float {
            50% { transform: translateY(-6px); }
        }

        @keyframes home-spin {
            to { transform: rotate(360deg); }
        }

        /* الموجة داخل القطرة: طولها 16 وحدة، فالإزاحة بمقدارها حركة متصلة بلا قفزة */
        .brand-logo-wave { animation: logo-wave 2.2s linear infinite; }
        .brand-logo-wave--back { animation-duration: 3.4s; animation-direction: reverse; }

        @keyframes logo-wave {
            to { transform: translateX(16px); }
        }

        .home-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 12px 5px 6px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .07);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .1);
            font-size: 12px;
            font-weight: 700;
            color: rgba(255, 255, 255, .8);
        }

        .home-chip-dot {
            width: 22px;
            height: 22px;
            display: grid;
            place-items: center;
            border-radius: 999px;
            background: #D4FF5B;
            color: #121316;
        }

        .home-title {
            font-size: clamp(26px, 4vw, 40px);
            font-weight: 800;
            line-height: 1.25;
            letter-spacing: -.01em;
        }

        .home-title em {
            font-style: normal;
            background: linear-gradient(90deg, #D4FF5B, #7DD3FC 55%, #B494F7);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .home-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 44px;
            padding: 0 16px 0 18px;
            border-radius: 14px;
            font-size: 13.5px;
            font-weight: 700;
            transition: transform .25s cubic-bezier(.22, .9, .24, 1), background-color .25s ease, box-shadow .25s ease;
        }

        .home-action:hover { transform: translateY(-2px); }
        .home-action-primary { background: #D4FF5B; color: #121316; box-shadow: 0 10px 24px -10px rgba(212, 255, 91, .7); }
        .home-action-glass { background: rgba(255, 255, 255, .08); color: #fff; box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .12); }
        .home-action-glass:hover { background: rgba(255, 255, 255, .14); }

        .home-stat {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            padding: 12px 14px;
            border-radius: 18px;
            background: rgba(255, 255, 255, .06);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .08);
            backdrop-filter: blur(6px);
            transition: background-color .25s ease, transform .25s cubic-bezier(.22, .9, .24, 1);
        }

        .home-stat:hover { background: rgba(255, 255, 255, .11); transform: translateY(-2px); }

        .home-stat-icon {
            width: 38px;
            height: 38px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: var(--tone-soft);
            color: var(--tone);
        }

        .home-stat-value {
            display: block;
            font-size: 22px;
            font-weight: 800;
            line-height: 1.1;
            font-variant-numeric: tabular-nums;
        }

        .home-stat-label {
            display: block;
            font-size: 11.5px;
            line-height: 1.4;
            color: rgba(255, 255, 255, .6);
        }

        /* على شبكة العمودين يأخذ المؤشر الأخير الفردي السطر كاملًا بدل أن يبقى وحيدًا في نصفه */
        @media (max-width: 1023px) {
            .home-stat:last-child:nth-child(odd) { grid-column: span 2; }
        }

        /* ===== أقسام اللوحة ===== */
        .home-group-icon {
            width: 42px;
            height: 42px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            border-radius: 14px;
            background: #121316;
            color: #D4FF5B;
        }

        .home-group-line {
            flex: 1;
            height: 1px;
            min-width: 24px;
            background: linear-gradient(to left, #E5E7EB, transparent);
        }

        .home-tile {
            position: relative;
            isolation: isolate;
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 14px;
            min-height: 96px;
            padding: 18px;
            border-radius: 22px;
            background: #fff;
            box-shadow: inset 0 0 0 1px #EEF0F3, 0 1px 2px rgba(16, 24, 40, .04);
            transition: transform .35s cubic-bezier(.22, .9, .24, 1), box-shadow .35s ease;
        }

        .home-tile::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: -1;
            background: radial-gradient(120% 100% at 100% 0%, var(--tone-soft), transparent 60%);
            opacity: 0;
            transition: opacity .35s ease;
        }

        .home-tile:hover,
        .home-tile:focus-visible {
            transform: translateY(-4px);
            box-shadow: inset 0 0 0 1px var(--tone-line), 0 22px 40px -24px var(--tone-solid);
        }

        .home-tile:hover::before,
        .home-tile:focus-visible::before { opacity: 1; }

        .home-tile-icon {
            width: 52px;
            height: 52px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            border-radius: 16px;
            background: var(--tone-soft);
            color: var(--tone);
            box-shadow: inset 0 0 0 1px var(--tone-line);
            transition: transform .45s cubic-bezier(.34, 1.56, .64, 1);
        }

        .home-tile:hover .home-tile-icon { transform: rotate(-8deg) scale(1.08); }

        .home-tile-watermark {
            position: absolute;
            inset-inline-end: -14px;
            bottom: -18px;
            z-index: -1;
            width: 96px;
            height: 96px;
            color: var(--tone);
            opacity: .05;
            transform: rotate(-12deg);
            transition: opacity .35s ease, transform .5s cubic-bezier(.22, .9, .24, 1);
        }

        .home-tile:hover .home-tile-watermark { opacity: .1; transform: rotate(0deg) scale(1.1); }

        .home-tile-go {
            width: 36px;
            height: 36px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            border-radius: 999px;
            background: #F4F5F7;
            color: #9CA3AF;
            transition: background-color .3s ease, color .3s ease, transform .35s cubic-bezier(.22, .9, .24, 1);
        }

        .home-tile:hover .home-tile-go {
            background: #121316;
            color: #D4FF5B;
            transform: translateX(-4px);
        }

        .home-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 1px 8px;
            border-radius: 999px;
            background: var(--tone-soft);
            color: var(--tone);
            box-shadow: inset 0 0 0 1px var(--tone-line);
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        @media (prefers-reduced-motion: reduce) {
            .home-reveal, .home-emblem, .home-emblem .brand-logo-ring, .home-hero-waves svg, .brand-logo-wave { animation: none; }
        }
    </style>

    {{-- بداية: بطاقة الترحيب --}}
    <section class="home-hero home-reveal" aria-labelledby="homeTitle">
        <div class="home-hero-waves" aria-hidden="true">
            <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path fill="rgba(56, 189, 248, .08)" d="M0 60Q150 20 300 60T600 60T900 60T1200 60V120H0Z" />
            </svg>
            <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path fill="rgba(212, 255, 91, .07)" d="M0 70Q150 35 300 70T600 70T900 70T1200 70V120H0Z" />
            </svg>
        </div>

        <div class="flex flex-col lg:flex-row lg:items-center gap-6 lg:gap-10">
            <div class="flex flex-col sm:flex-row sm:items-center gap-5 flex-1 min-w-0">
                <div class="home-emblem">
                    <x-brand-logo :size="84" />
                </div>

                <div class="min-w-0">
                    <span class="home-chip" x-data="{
                        greeting: new Date().getHours() < 12 ? 'صباح الخير' : 'مساء الخير',
                        today: new Intl.DateTimeFormat('ar-EG-u-nu-latn', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(new Date()),
                    }">
                        <span class="home-chip-dot"><x-icon class="w-3.5 h-3.5" name="sun" /></span>
                        <span x-text="`${greeting} · ${today}`">أهلًا بك</span>
                    </span>

                    <h2 class="home-title mt-3" id="homeTitle">أهلًا بك في <em>أكوا ووتر</em></h2>
                    <p class="mt-2 max-w-xl text-[14px] leading-7 text-white/60">
                        كل أعمال الشركة في مكان واحد: البيع والتحصيل، والمخزون والعهد، والصيانة وفريق العمل.
                        اختر القسم الذي تريده وابدأ.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2.5 lg:flex-col lg:items-stretch lg:w-48">
                @foreach ($quickActions as $action)
                    <a class="home-action home-action-{{ $action['variant'] }}" href="{{ route($action['route']) }}" wire:navigate>
                        <x-icon class="w-[18px] h-[18px]" :name="$action['icon']" />
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="mt-7 grid grid-cols-2 lg:grid-cols-5 gap-2.5">
            @foreach ($stats as $route => $stat)
                <a class="home-stat ui-tone-{{ $stat['tone'] }}" href="{{ $stat['href'] ?? route($route) }}" wire:navigate>
                    <span class="home-stat-icon">
                        <x-icon class="w-[18px] h-[18px]" :name="$navIcons[$route]" />
                    </span>
                    <span class="min-w-0">
                        <span class="home-stat-value" dir="ltr" x-data="countUp({{ $stat['count'] }})" x-text="display">{{ number_format($stat['count']) }}</span>
                        <span class="home-stat-label">{{ $stat['label'] }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>
    {{-- نهاية: بطاقة الترحيب --}}

    {{-- بداية: كل أقسام اللوحة — من نفس مصدر شريط التنقل (Lookups::navGroups) --}}
    <div class="mt-8 space-y-9">
        @foreach ($navGroups as $groupIndex => $group)
            <section aria-labelledby="homeGroup{{ $groupIndex }}">
                <header class="home-reveal flex items-center gap-3 mb-4" style="--delay: {{ .15 + $groupIndex * .12 }}s">
                    <span class="home-group-icon"><x-icon class="w-5 h-5" :name="$group['icon']" /></span>
                    <div class="min-w-0">
                        <h3 class="text-[17px] font-extrabold text-gray-900" id="homeGroup{{ $groupIndex }}">{{ $group['label'] }}</h3>
                        <p class="text-[12.5px] text-gray-500 truncate">{{ $group['tagline'] }}</p>
                    </div>
                    <span class="home-group-line hidden sm:block"></span>
                    <span class="hidden sm:inline text-[12px] font-bold text-gray-400 whitespace-nowrap">{{ count($group['items']) }} أقسام</span>
                </header>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                    @foreach ($group['items'] as $item)
                        @php($stat = $stats[$item['route']] ?? null)
                        <a class="home-tile home-reveal ui-tone-{{ $item['tone'] }}" href="{{ route($item['route']) }}" wire:navigate
                            style="--delay: {{ .25 + $tileIndex++ * .05 }}s">
                            <x-icon class="home-tile-watermark" :name="$item['icon']" />
                            <span class="home-tile-icon"><x-icon class="w-6 h-6" :name="$item['icon']" /></span>

                            <span class="flex-1 min-w-0">
                                <span class="flex items-center gap-2 flex-wrap">
                                    <b class="text-[15px] font-extrabold text-gray-900">{{ $item['label'] }}</b>
                                    @if ($stat && $stat['count'] > 0)
                                        <span class="home-badge ui-tone-{{ $stat['tone'] }}" dir="rtl">
                                            <span dir="ltr">{{ number_format($stat['count']) }}</span> {{ $stat['label'] }}
                                        </span>
                                    @endif
                                </span>
                                <span class="block mt-1 text-[12.5px] leading-5 text-gray-500">{{ $item['hint'] }}</span>
                            </span>

                            <span class="home-tile-go"><x-icon class="w-4 h-4" name="arrow-left" /></span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
    {{-- نهاية: كل أقسام اللوحة --}}

    <p class="home-reveal mt-10 mb-2 flex items-center justify-center gap-2 text-[12px] text-gray-400" style="--delay: {{ .3 + $tileIndex * .05 }}s">
        <x-icon class="w-3.5 h-3.5" name="droplets" />
        {{ $navItems->count() }} قسمًا في لوحة تحكم أكوا ووتر
    </p>
@endsection

@push('scripts')
    {{-- شاشة الترحيب: تظهر مرة واحدة لكل جلسة متصفح عند دخول النظام. مخفية افتراضيًا (CSS)،
         ولا تظهر إلا إذا شغّلها السكربت — فلا تومض عند العودة للرئيسية أو إن تعطّل السكربت.
         تُرسم في نهاية <body> خارج #appContent، لأن تحويلات (transform) المحتوى أثناء التنقّل
         تجعل position: fixed نسبيًا لها بدل الشاشة. --}}
    <div class="aq-splash" id="aqSplash" aria-hidden="true">
        <style>
            .aq-splash {
                position: fixed;
                inset: 0;
                z-index: 100;
                display: none;
                place-items: center;
                overflow: hidden;
                background:
                    radial-gradient(50% 50% at 50% 55%, rgba(56, 189, 248, .16), transparent 70%),
                    radial-gradient(60% 60% at 100% 0%, rgba(212, 255, 91, .10), transparent 60%),
                    radial-gradient(60% 60% at 0% 100%, rgba(180, 148, 247, .18), transparent 60%),
                    #0D0E10;
                color: #fff;
                cursor: pointer;
                clip-path: circle(150% at 50% 50%);
            }

            .aq-splash.is-playing { display: grid; }

            .aq-splash.is-leaving {
                clip-path: circle(0% at 50% 50%);
                transition: clip-path .85s cubic-bezier(.76, 0, .24, 1);
            }

            .aq-splash-stage {
                position: relative;
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .aq-splash.is-leaving .aq-splash-stage {
                transform: scale(.9);
                opacity: 0;
                transition: transform .6s ease, opacity .4s ease;
            }

            /* 1) قطرة تسقط من أعلى… */
            .aq-splash-drop {
                position: absolute;
                top: 0;
                left: 50%;
                width: 22px;
                height: 30px;
                margin-left: -11px;
                border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
                background: linear-gradient(#7DD3FC, #0284C7);
                clip-path: path('M11 0C11 0 0 13 0 20a11 11 0 0 0 22 0C22 13 11 0 11 0Z');
                opacity: 0;
                animation: splash-fall .65s cubic-bezier(.55, 0, 1, .45) .1s forwards;
            }

            @keyframes splash-fall {
                0% { opacity: 1; transform: translateY(-55vh); }
                92% { opacity: 1; }
                100% { opacity: 0; transform: translateY(70px); }
            }

            /* 2) …فتتموّج المياه… */
            .aq-splash-ripple {
                position: absolute;
                top: -44px;
                left: 50%;
                width: 220px;
                height: 220px;
                margin-left: -110px;
                border-radius: 50%;
                border: 1.5px solid rgba(125, 211, 252, .5);
                opacity: 0;
                animation: splash-ripple 1.6s cubic-bezier(.22, .9, .24, 1) forwards;
            }

            .aq-splash-ripple:nth-of-type(2) { animation-delay: .75s; }
            .aq-splash-ripple:nth-of-type(3) { animation-delay: .95s; border-color: rgba(212, 255, 91, .45); }
            .aq-splash-ripple:nth-of-type(4) { animation-delay: 1.15s; border-color: rgba(180, 148, 247, .45); }

            @keyframes splash-ripple {
                0% { opacity: 1; transform: scale(.1); }
                100% { opacity: 0; transform: scale(2.4); }
            }

            /* 3) …ويولد الشعار */
            .aq-splash-logo {
                position: relative;
                width: 132px;
                height: 132px;
                display: grid;
                place-items: center;
                opacity: 0;
                animation: splash-pop .8s cubic-bezier(.34, 1.56, .64, 1) .72s forwards;
            }

            .aq-splash-logo::after {
                content: '';
                position: absolute;
                inset: -30px;
                z-index: -1;
                border-radius: 50%;
                background: radial-gradient(circle, rgba(56, 189, 248, .45), transparent 65%);
                filter: blur(14px);
                animation: splash-glow 2.4s ease-in-out infinite;
            }

            .aq-splash-logo .brand-logo-ring {
                transform-origin: 48px 48px;
                stroke-dashoffset: 276;
                animation: splash-ring 1.4s cubic-bezier(.65, 0, .35, 1) .85s forwards, home-spin 10s linear 2.25s infinite;
            }

            .aq-splash-logo .brand-logo-level {
                transform: translateY(40px);
                animation: splash-fill 1.5s cubic-bezier(.45, 0, .55, 1) 1s forwards;
            }

            @keyframes splash-pop {
                from { opacity: 0; transform: scale(.3) translateY(20px); }
                to { opacity: 1; transform: none; }
            }

            @keyframes splash-ring {
                to { stroke-dashoffset: 0; }
            }

            @keyframes splash-fill {
                to { transform: translateY(0); }
            }

            @keyframes splash-glow {
                50% { opacity: .6; transform: scale(1.08); }
            }

            /* الكلمات تظهر كاملة (لا حرفًا حرفًا) حتى تبقى الحروف العربية متصلة */
            .aq-splash-name {
                margin-top: 26px;
                display: flex;
                gap: .3em;
                font-size: clamp(30px, 6vw, 44px);
                font-weight: 800;
                line-height: 1.2;
            }

            .aq-splash-name span {
                opacity: 0;
                animation: splash-word .8s cubic-bezier(.22, .9, .24, 1) forwards;
            }

            .aq-splash-name span:first-child { animation-delay: 1.25s; }
            .aq-splash-name span:last-child {
                animation-delay: 1.42s;
                background: linear-gradient(90deg, #D4FF5B, #7DD3FC);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }

            @keyframes splash-word {
                from { opacity: 0; transform: translateY(16px); filter: blur(8px); letter-spacing: .1em; }
                to { opacity: 1; transform: none; filter: none; letter-spacing: 0; }
            }

            .aq-splash-tag {
                margin-top: 8px;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: .45em;
                color: rgba(255, 255, 255, .45);
                opacity: 0;
                animation: splash-fade .8s ease 1.6s forwards;
            }

            /* بلا letter-spacing: تباعد حروف الشعار اللاتيني جزء من شكله النهائي */
            @keyframes splash-fade {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: none; }
            }

            .aq-splash-bar {
                position: relative;
                width: 160px;
                height: 3px;
                margin-top: 28px;
                overflow: hidden;
                border-radius: 999px;
                background: rgba(255, 255, 255, .08);
                opacity: 0;
                animation: splash-fade .5s ease 1.1s forwards;
            }

            .aq-splash-bar::after {
                content: '';
                position: absolute;
                inset: 0;
                border-radius: inherit;
                background: linear-gradient(90deg, #B494F7, #7DD3FC, #D4FF5B);
                transform: scaleX(0);
                transform-origin: right;
                animation: splash-progress 1.6s cubic-bezier(.65, 0, .35, 1) 1.15s forwards;
            }

            @keyframes splash-progress {
                to { transform: scaleX(1); }
            }

            .aq-splash-skip {
                position: absolute;
                bottom: calc(24px + env(safe-area-inset-bottom, 0px));
                inset-inline: 0;
                text-align: center;
                font-size: 11px;
                color: rgba(255, 255, 255, .3);
                opacity: 0;
                animation: splash-fade .6s ease 1.8s forwards;
            }
        </style>

        <div class="aq-splash-stage">
            <span class="aq-splash-drop"></span>
            <span class="aq-splash-ripple"></span>
            <span class="aq-splash-ripple"></span>
            <span class="aq-splash-ripple"></span>
            <span class="aq-splash-ripple"></span>

            <div class="aq-splash-logo">
                <x-brand-logo :size="132" />
            </div>

            <div class="aq-splash-name"><span>أكوا</span><span>ووتر</span></div>
            <div class="aq-splash-tag" dir="ltr">AQUA WATER</div>
            <div class="aq-splash-bar"></div>
        </div>

        <span class="aq-splash-skip">اضغط في أي مكان للتخطي</span>
    </div>

    <script>
        (function () {
            const splash = document.getElementById('aqSplash');
            if (!splash) return;

            let seen = false;
            try {
                seen = sessionStorage.getItem('aq-welcome') === '1';
                sessionStorage.setItem('aq-welcome', '1');
            } catch (e) {}

            if (seen || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                splash.remove();
                return;
            }

            const root = document.documentElement;
            root.classList.add('aq-splash-active');
            splash.classList.add('is-playing');

            let done = false;
            const finish = function () {
                if (done) return;
                done = true;
                splash.classList.add('is-leaving');
                root.classList.remove('aq-splash-active');
                setTimeout(function () { splash.remove(); }, 900);
            };

            const timer = setTimeout(finish, 3000);
            splash.addEventListener('click', function () {
                clearTimeout(timer);
                finish();
            });
            // مغادرة الصفحة قبل انتهاء العرض لا تترك الحركات متوقفة في الصفحة التالية
            document.addEventListener('livewire:navigate', finish, { once: true });
        })();

        // عدّاد يصعد من الصفر إلى القيمة — يبدأ بعد شاشة الترحيب إن كانت ظاهرة
        function countUp(target) {
            return {
                display: Number(target).toLocaleString('en-US'),
                init() {
                    if (!target || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

                    const start = () => {
                        const duration = 1100;
                        const began = performance.now();
                        const step = (now) => {
                            const progress = Math.min(1, (now - began) / duration);
                            const eased = 1 - Math.pow(1 - progress, 3);
                            this.display = Math.round(target * eased).toLocaleString('en-US');
                            if (progress < 1) requestAnimationFrame(step);
                        };
                        requestAnimationFrame(step);
                    };

                    this.display = '0';
                    const waitForSplash = () => document.documentElement.classList.contains('aq-splash-active')
                        ? setTimeout(waitForSplash, 120)
                        : setTimeout(start, 250);
                    waitForSplash();
                },
            };
        }
    </script>
@endpush
