@props(['sort' => null, 'align' => 'right'])

<th
    class="px-4 py-3 text-{{ $align }} text-[11px] font-bold text-gray-400 uppercase tracking-wide whitespace-nowrap select-none {{ $sort ? 'cursor-pointer group hover:text-gray-700 transition-colors' : '' }}"
    @if ($sort) x-on:click="sortBy('{{ $sort }}')" @endif>
    <span class="inline-flex items-center gap-1.5">
        {{ $slot }}
        @if ($sort)
            <svg class="w-3 h-3 transition-all duration-200" fill="none" stroke="currentColor" stroke-linecap="round"
                stroke-linejoin="round" stroke-width="2.5" viewBox="0 0 24 24"
                x-bind:class="sortIcon('{{ $sort }}')">
                <path d="m6 9 6 6 6-6" />
            </svg>
        @endif
    </span>
</th>
