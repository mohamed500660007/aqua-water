@props(['status' => null, 'tone' => null, 'dot' => false])

@php($t = $tone ?? \App\Support\ToneMap::forStatus((string) $status))

<span
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 text-[11px] font-bold px-2.5 py-1 rounded-full whitespace-nowrap ' . \App\Support\ToneMap::badge($t)]) }}>
    @if ($dot)
        <span class="w-1.5 h-1.5 rounded-full bg-current opacity-70"></span>
    @endif
    {{ $status ?? $slot }}
</span>
