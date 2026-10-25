@php
    $navGroups = \App\Support\Lookups::navGroups();
@endphp

{{-- بداية: الصف العلوي (سطح المكتب فقط): عنوان الصفحة يمينًا، الشريط في المنتصف، وأزرار الصفحة يسارًا.
     الشريط محفوظ عبر @@persist لا يُعاد رسمه بين الصفحات، وعنوان كل صفحة وأزرارها تُنقل إلى
     الخانتين الجانبيتين عبر x-teleport من partials.shell.header-desktop. الأنماط في partials.shell.topnav-assets. --}}
<header class="app-topnav hidden lg:block" x-data="topNav()" x-on:keydown.escape.window="close()"
    x-on:keydown.window="focusSearch($event)">
    <div class="tn-row">
        <div class="tn-slot tn-slot-start" id="tnStart"></div>

        <div class="tn-bar" x-ref="bar">
            <a class="tn-brand" href="{{ route('home') }}" title="{{ $settings->get('company_name') }}" wire:navigate>
                <span class="tn-logo"><x-icon class="w-5 h-5" name="droplets" /></span>
                <span>
                    <span class="tn-brand-name">{{ $settings->get('company_name') }}</span>
                    <span class="tn-brand-sub">لوحة التحكم</span>
                </span>
            </a>

            <span class="tn-divider"></span>

            <nav class="tn-triggers" aria-label="التنقل الرئيسي" x-ref="triggers" x-on:mouseleave="leave()">
                <span class="tn-pill" x-bind:class="{ 'is-visible': pill.visible }"
                    x-bind:style="`--pill-x: ${pill.x}px; --pill-w: ${pill.w}px`"></span>

                @foreach ($navGroups as $index => $group)
                    @php
                        $hrefs = array_map(fn ($item) => route($item['route']), $group['items']);
                        $isCurrent = collect($hrefs)->contains(fn ($href) => \App\Support\Lookups::isNavActive($href));
                    @endphp
                    <button class="tn-trigger {{ $isCurrent ? 'is-current' : '' }}" type="button" aria-haspopup="true"
                        x-ref="trigger{{ $index }}"
                        x-bind:class="{ 'is-open': open === {{ $index }}, 'is-current': $store.nav.isActiveAny(@js($hrefs)) }"
                        x-bind:aria-expanded="open === {{ $index }}"
                        x-on:mouseenter="enter({{ $index }}, $el)"
                        x-on:click="toggle({{ $index }}, $el)"
                        x-on:keydown.arrow-down.prevent="openAndFocus({{ $index }}, $el)">
                        <x-icon :name="$group['icon']" />
                        {{ $group['label'] }}
                        <x-icon class="tn-chevron" name="chevron-down" />
                    </button>
                @endforeach
            </nav>

            <div class="tn-tools">
                <form class="tn-search" action="{{ route('customers.index') }}" method="GET" role="search">
                    <x-icon class="tn-search-icon" name="search" />
                    <input name="q" placeholder="ابحث عن عميل بالاسم أو الهاتف..." type="search" x-ref="search"
                        aria-label="ابحث عن عميل" />

                </form>
            </div>

            {{-- لوحة واحدة لكل المجموعات: تتشكّل على مقاس المجموعة المفتوحة وتنزلق تحت زرها --}}
            <div class="tn-panel" x-bind:class="{ 'is-open': open !== null, 'is-morphing': morphing }"
                x-bind:style="`--x: ${panel.x}px; --w: ${panel.w}px; --h: ${panel.h}px; --arrow: ${panel.arrow}px`"
                x-on:mouseenter="stay()" x-on:mouseleave="leave()" x-on:focusout="if (! $el.contains($event.relatedTarget)) close()">
                <span class="tn-arrow"></span>

                <div class="tn-viewport">
                    @foreach ($navGroups as $index => $group)
                        <div class="tn-layer" role="menu" aria-label="{{ $group['label'] }}" x-ref="layer{{ $index }}"
                            x-bind:class="layerClass({{ $index }})" x-bind:inert="open !== {{ $index }}">
                            <div class="tn-feature">
                                <x-icon class="tn-feature-watermark" :name="$group['icon']" />
                                <span class="tn-feature-icon"><x-icon :name="$group['icon']" /></span>
                                <span class="tn-feature-title">{{ $group['label'] }}</span>
                                <p class="tn-feature-text">{{ $group['tagline'] }}</p>
                                <span class="tn-feature-count">{{ count($group['items']) }} أقسام</span>

                                <a class="tn-feature-action" href="{{ route($group['action']['route']) }}" wire:navigate>
                                    <x-icon :name="$group['action']['icon']" />
                                    {{ $group['action']['label'] }}
                                </a>
                            </div>

                            <div class="tn-items" style="{{ count($group['items']) > 3 ? '--cols: 2' : '--cols: 1; --col-w: 300px' }}">
                                @foreach ($group['items'] as $itemIndex => $item)
                                    @php
                                        $href = route($item['route']);
                                    @endphp
                                    <a class="tn-item {{ \App\Support\Lookups::isNavActive($href) ? 'is-active' : '' }}" role="menuitem"
                                        style="--i: {{ $itemIndex }}" data-nav-href="{{ $href }}" href="{{ $href }}"
                                        x-bind:class="{ 'is-active': $store.nav.isActive(@js($href)) }" wire:navigate>
                                        <span class="tn-item-icon tone-{{ $item['tone'] }}"><x-icon :name="$item['icon']" /></span>
                                        <span class="tn-item-text">
                                            <b>{{ $item['label'] }}</b>
                                            <small>{{ $item['hint'] }}</small>
                                        </span>
                                        @if ($item['badge'] ?? null)
                                            <span class="tn-badge">{{ $item['badge'] }}</span>
                                        @endif
                                        <span class="tn-here">أنت هنا</span>
                                        <x-icon class="tn-item-go" name="arrow-left" />
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="tn-slot tn-slot-end" id="tnEnd"></div>
    </div>
</header>
{{-- نهاية: شريط التنقل العلوي --}}
