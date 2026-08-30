@props(['paginator'])

{{-- ترقيم صفحات من الخادم — بنفس شكل x-ui.pagination المستخدم في الجداول الثابتة --}}
@if ($paginator->total() > 0)
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3 border-t border-gray-100">
        <p class="text-[11px] text-gray-400">
            عرض <span class="font-bold text-gray-600">{{ $paginator->firstItem() }}</span>
            إلى <span class="font-bold text-gray-600">{{ $paginator->lastItem() }}</span>
            من <span class="font-bold text-gray-600">{{ $paginator->total() }}</span> سجل
        </p>

        @if ($paginator->hasPages())
            @php
                $window = $paginator->getUrlRange(
                    max(1, $paginator->currentPage() - 2),
                    min($paginator->lastPage(), $paginator->currentPage() + 2),
                );
            @endphp

            <div class="flex items-center gap-1">
                <a class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-gray-100 transition-colors {{ $paginator->onFirstPage() ? 'opacity-30 pointer-events-none' : '' }}"
                    href="{{ $paginator->previousPageUrl() ?? '#' }}" rel="prev">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-linecap="round"
                        stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24">
                        <path d="m9 18 6-6-6-6" />
                    </svg>
                </a>

                @foreach ($window as $page => $url)
                    <a class="min-w-[32px] h-8 px-2 rounded-lg text-[12px] font-bold flex items-center justify-center transition-colors {{ $page === $paginator->currentPage() ? 'bg-brand-green text-brand-dark' : 'text-gray-500 hover:bg-gray-100' }}"
                        href="{{ $url }}">{{ $page }}</a>
                @endforeach

                <a class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-gray-100 transition-colors {{ $paginator->hasMorePages() ? '' : 'opacity-30 pointer-events-none' }}"
                    href="{{ $paginator->nextPageUrl() ?? '#' }}" rel="next">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-linecap="round"
                        stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24">
                        <path d="m15 18-6-6 6-6" />
                    </svg>
                </a>
            </div>
        @endif
    </div>
@endif
