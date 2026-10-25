@php
    $items = \App\Support\Lookups::bottomNav();
    $navGroups = \App\Support\Lookups::navGroups();
    $primaryHrefs = array_map(fn ($item) => route($item['route']), $items);
    // التبويبان الأولان يمين زر القائمة والباقي يساره
    $sides = array_chunk($items, (int) ceil(count($items) / 2));
    $tileIndex = 0;
@endphp

{{-- بداية: تنقل الهاتف — شريط سفلي داكن بمؤشر منزلق وزر قائمة مرتفع، ولوحة بكل الأقسام مجمّعة.
     محفوظ عبر @@persist، والأنماط والسلوك في partials.shell.mobile-nav-assets. --}}
<nav class="lg:hidden fixed inset-x-3 z-40 bottom-nav-safe" id="bottomNav" aria-label="التنقل الرئيسي"
    x-data="mobileNav(@js($primaryHrefs))" x-on:keydown.escape.window="$store.more.close()">

    <div class="m-backdrop" x-cloak x-show="$store.more.open" x-on:click="$store.more.close()"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

    {{-- لوحة القائمة --}}
    <section class="m-sheet" role="dialog" aria-modal="true" aria-label="كل الأقسام" x-cloak x-show="$store.more.open"
        x-bind:class="{ 'is-open': $store.more.open, 'is-dragging': drag.startY !== null }"
        x-bind:style="drag.offset ? `transform: translateY(${drag.offset}px)` : ''"
        x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
        x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full">

        <header class="m-sheet-head" x-on:touchstart.passive="dragStart($event)" x-on:touchmove.passive="dragMove($event)"
            x-on:touchend="dragEnd()">
            <div class="m-handle"></div>

            <div class="m-sheet-title">
                <div>
                    <strong>{{ $settings->get('company_name') }}</strong>
                    <small>كل أقسام لوحة التحكم</small>
                </div>
                <button class="m-icon-btn" type="button" aria-label="إغلاق القائمة" x-on:click="$store.more.close()">
                    <x-icon class="w-5 h-5" name="x" />
                </button>
            </div>

            <form class="m-search" action="{{ route('customers.index') }}" method="GET" role="search"
                x-on:submit="$store.more.close()">
                <x-icon class="m-search-icon" name="search" />
                <input name="q" placeholder="ابحث عن عميل بالاسم أو الهاتف..." type="search" x-ref="menuSearch"
                    aria-label="ابحث عن عميل" />
            </form>

            <div class="m-actions">
                @foreach ($navGroups as $group)
                    <a class="m-action" href="{{ route($group['action']['route']) }}" wire:navigate x-on:click="$store.more.close()">
                        <x-icon :name="$group['action']['icon']" />
                        {{ $group['action']['label'] }}
                    </a>
                @endforeach
            </div>
        </header>

        <div class="m-sheet-body">
            @foreach ($navGroups as $group)
                <div class="m-group">
                    <div class="m-group-head">
                        <span class="m-group-icon"><x-icon :name="$group['icon']" /></span>
                        <div class="min-w-0">
                            <strong>{{ $group['label'] }}</strong>
                            <small>{{ $group['tagline'] }}</small>
                        </div>
                    </div>

                    <div class="m-tiles">
                        @foreach ($group['items'] as $item)
                            @php
                                $href = route($item['route']);
                            @endphp
                            <a class="m-tile {{ \App\Support\Lookups::isNavActive($href) ? 'is-active' : '' }}" style="--i: {{ $tileIndex++ }}"
                                data-nav-href="{{ $href }}" href="{{ $href }}" wire:navigate x-on:click="$store.more.close()"
                                x-bind:class="{ 'is-active': $store.nav.isActive(@js($href)) }">
                                <span class="tn-item-icon tone-{{ $item['tone'] }}"><x-icon :name="$item['icon']" /></span>
                                <span>
                                    <b>{{ $item['label'] }}</b>
                                    <small>{{ $item['hint'] }}</small>
                                </span>
                                <span class="m-here">أنت هنا</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- الشريط العائم --}}
    <div class="m-island m-dock mx-auto max-w-[480px]">
        <span class="m-indicator" x-bind:class="{ 'is-visible': indicator.visible }"
            x-bind:style="`--ind-x: ${indicator.x}px; --ind-w: ${indicator.w}px`"></span>

        @foreach ($sides as $side => $sideItems)
            @foreach ($sideItems as $item)
                @php
                    $index = array_search($item, $items, true);
                    $href = route($item['route']);
                @endphp
                <a class="m-tab {{ \App\Support\Lookups::isNavActive($href) ? 'is-active' : '' }}" x-ref="tab{{ $index }}"
                    data-nav-href="{{ $href }}" href="{{ $href }}" wire:navigate
                    x-bind:class="{ 'is-active': $store.nav.isActive(@js($href)) }"
                    x-bind:aria-current="$store.nav.isActive(@js($href)) ? 'page' : null">
                    <x-icon :name="$item['icon']" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach

            @if ($side === 0)
                <div class="m-fab-slot">
                    <button class="m-fab" type="button" aria-label="كل الأقسام"
                        x-bind:class="{ 'is-open': $store.more.open }" x-bind:aria-expanded="$store.more.open"
                        x-on:click="$store.more.open ? $store.more.close() : openMenu()">
                        <x-icon name="plus" />
                        <span class="m-fab-dot" x-cloak x-show="currentInMenu && ! $store.more.open"></span>
                    </button>
                </div>
            @endif
        @endforeach
    </div>
</nav>
{{-- نهاية: تنقل الهاتف --}}
