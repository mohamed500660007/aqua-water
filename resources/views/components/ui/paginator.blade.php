@props(['paginator'])

{{-- ترقيم صفحات من الخادم: الصفحة الحالية شريحة داكنة برقم ليموني، الأولى والأخيرة دائمًا ظاهرتان
     وبينهما «…» عند البُعد، وشريط صغير يوضح موضعك داخل القائمة --}}
@if ($paginator->total() > 0)
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $pages = collect([1, $current - 1, $current, $current + 1, $last])
            ->filter(fn ($page) => $page >= 1 && $page <= $last)
            ->unique()
            ->sort()
            ->values();
        $progress = round($paginator->lastItem() / $paginator->total() * 100);
    @endphp

    <nav class="ui-pager" aria-label="ترقيم الصفحات">
        <div class="ui-pager-info">
            <span>عرض <b>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</b> من <b>{{ number_format($paginator->total()) }}</b> سجل</span>
            @if ($paginator->hasPages())
                <span class="ui-pager-track" aria-hidden="true"><span style="width: {{ $progress }}%"></span></span>
            @endif
        </div>

        @if ($paginator->hasPages())
            <div class="ui-pager-pages">
                <a class="ui-page {{ $paginator->onFirstPage() ? 'is-disabled' : '' }}" href="{{ $paginator->previousPageUrl() ?? '#' }}"
                    rel="prev" aria-label="الصفحة السابقة" wire:navigate>
                    <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24">
                        <path d="m9 18 6-6-6-6" />
                    </svg>
                </a>

                @foreach ($pages as $index => $page)
                    @if ($index > 0 && $page - $pages[$index - 1] > 1)
                        <span class="ui-page is-gap">…</span>
                    @endif
                    <a class="ui-page {{ $page === $current ? 'is-current' : '' }}" href="{{ $paginator->url($page) }}"
                        @if ($page === $current) aria-current="page" @endif wire:navigate>{{ $page }}</a>
                @endforeach

                <a class="ui-page {{ $paginator->hasMorePages() ? '' : 'is-disabled' }}" href="{{ $paginator->nextPageUrl() ?? '#' }}"
                    rel="next" aria-label="الصفحة التالية" wire:navigate>
                    <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24">
                        <path d="m15 18-6-6 6-6" />
                    </svg>
                </a>
            </div>
        @endif
    </nav>
@endif
