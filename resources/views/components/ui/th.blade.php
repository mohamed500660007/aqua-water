@props(['sort' => null, 'align' => 'right'])

<th class="ui-th text-{{ $align }}" @if ($sort) x-on:click="sortBy('{{ $sort }}')" @endif>
    @if ($sort)
        <span class="ui-th-sort cursor-pointer">
            {{ $slot }}
            <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                viewBox="0 0 24 24" x-bind:class="sortIcon('{{ $sort }}')">
                <path d="m6 9 6 6 6-6" />
            </svg>
        </span>
    @else
        {{ $slot }}
    @endif
</th>
