{{-- بداية: ترويسة الهاتف --}}
<header class="lg:hidden sticky top-0 z-30 bg-[#FAFAFA]/95 backdrop-blur-md px-4 pt-3 pb-3 border-b border-gray-100">
    <div class="flex items-center gap-2.5">
        {{-- فتح قائمة التنقل الكاملة --}}
        <button
            class="w-11 h-11 shrink-0 rounded-xl bg-white border border-gray-100 text-gray-700 flex items-center justify-center active:scale-90 transition-transform"
            type="button" x-data x-on:click="$store.drawer.show()" aria-label="فتح القائمة">
            <x-icon class="w-[20px] h-[20px]" name="menu" />
        </button>

        <div class="min-w-0 flex-1">
            <p class="text-[10px] text-gray-400 leading-tight truncate">@yield('page-subtitle', 'أكوا ووتر')</p>
            <h1 class="text-[15px] font-bold text-gray-800 truncate leading-tight">@yield('page-title', 'لوحة التحكم')</h1>
        </div>

        <a class="w-11 h-11 shrink-0 rounded-xl brand-green flex items-center justify-center active:scale-90 transition-transform"
            href="{{ route('contracts.create') }}" title="عقد جديد" wire:navigate>
            <x-icon class="w-[20px] h-[20px]" name="plus" />
        </a>
    </div>
</header>
{{-- نهاية: ترويسة الهاتف --}}
