@props(['label', 'description' => null, 'checked' => false])

<label class="flex items-start justify-between gap-4 py-3.5 cursor-pointer group">
    <span class="min-w-0">
        <span class="block text-[13px] font-bold text-gray-700 group-hover:text-brand-dark transition-colors">{{ $label }}</span>
        @if ($description)
            <span class="block text-[11px] text-gray-400 mt-0.5 leading-relaxed">{{ $description }}</span>
        @endif
    </span>
    <span class="shrink-0 mt-0.5" x-data="{ on: {{ $checked ? 'true' : 'false' }} }">
        <button class="relative w-11 h-6 rounded-full transition-colors duration-200" type="button"
            x-bind:class="on ? 'bg-brand-green' : 'bg-gray-200'" x-on:click="on = !on">
            <span class="absolute top-0.5 w-5 h-5 rounded-full bg-white shadow transition-all duration-200"
                x-bind:class="on ? 'right-0.5' : 'right-[22px]'"></span>
        </button>
    </span>
</label>
