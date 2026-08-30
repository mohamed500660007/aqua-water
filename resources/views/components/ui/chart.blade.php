@props(['id', 'config', 'height' => 'h-[240px]'])

{{-- مخطط Chart.js — الإعدادات تُمرَّر عبر وسم JSON منفصل --}}
<div class="relative w-full {{ $height }}">
    <canvas data-chart="{{ $id }}-cfg" id="{{ $id }}"></canvas>
</div>
<script id="{{ $id }}-cfg" type="application/json">@json($config)</script>
