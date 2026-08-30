@props(['tabs' => [], 'model' => 'tab'])

{{-- شريط تبويبات — يتطلب x-data يحتوي على المتغير المُمرّر في model --}}
<div class="flex items-center gap-1.5 overflow-x-auto scrollbar-hide bg-gray-50 p-1.5 rounded-2xl border border-gray-100">
    @foreach ($tabs as $key => $label)
        <button class="shrink-0 px-3.5 py-2 rounded-xl text-[12px] font-bold transition-all duration-200" type="button"
            x-bind:class="{{ $model }} === '{{ $key }}' ? 'bg-white text-brand-dark shadow-sm' : 'text-gray-400 hover:text-gray-700'"
            x-on:click="{{ $model }} = '{{ $key }}'">{{ $label }}</button>
    @endforeach
</div>
