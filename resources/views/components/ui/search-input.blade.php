@props(['placeholder' => 'ابحث...'])

<div class="relative flex-1 min-w-0">
    <x-icon class="w-4 h-4 absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" name="search" />
    <input
        {{ $attributes->merge([
            'class' =>
                'w-full h-11 pr-10 pl-9 rounded-xl bg-white border border-gray-200 text-[13px] focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green outline-none transition placeholder:text-gray-400',
            'type' => 'text',
            'placeholder' => $placeholder,
        ]) }} />
    <button class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-600 transition-colors"
        type="button" x-cloak x-on:click="q = ''" x-show="q">
        <x-icon class="w-4 h-4" name="x" />
    </button>
</div>
