@props(['status' => null, 'tone' => null, 'dot' => false])

@php($t = $tone ?? \App\Support\ToneMap::forStatus((string) $status))

<span {{ $attributes->merge(['class' => "ui-badge ui-tone-{$t}"]) }}>
    @if ($dot)
        <span class="ui-badge-dot"></span>
    @endif
    {{ $status ?? $slot }}
</span>
