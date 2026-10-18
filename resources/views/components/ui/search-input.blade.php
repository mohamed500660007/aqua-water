@props(['placeholder' => 'ابحث...'])

<div class="relative flex-1 min-w-0">
    <x-icon class="w-4 h-4 absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" name="search" />
    <input
        {{ $attributes->merge([
            'class' => 'ui-input w-full h-12 lg:h-11 pr-10 pl-9 text-base lg:text-[13px]',
            'type' => 'text',
            'placeholder' => $placeholder,
        ]) }} />
    <button class="absolute left-1 top-1/2 -translate-y-1/2 w-11 h-11 flex items-center justify-center text-gray-300 hover:text-gray-600 transition-colors"
        type="button" aria-label="مسح البحث" x-cloak x-on:click="q = ''" x-show="q">
        <x-icon class="w-4 h-4" name="x" />
    </button>
</div>
