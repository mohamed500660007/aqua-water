{{-- بداية: ترويسة الهاتف --}}
<header class="lg:hidden sticky top-0 z-30 bg-[#FAFAFA] px-5 pt-4 pb-3">
    <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <span class="w-10 h-10 shrink-0 rounded-xl bg-brand-dark text-white flex items-center justify-center">
                <x-icon class="w-[19px] h-[19px]" name="droplets" />
            </span>
            <div class="min-w-0">
                <p class="text-[10px] text-gray-400 leading-tight">أكوا ووتر</p>
                <h1 class="text-base font-bold text-gray-800 truncate leading-tight">@yield('page-title', 'لوحة التحكم')</h1>
            </div>
        </div>

        <a class="w-10 h-10 shrink-0 rounded-xl brand-green flex items-center justify-center active:scale-90 transition-transform"
            href="{{ route('contracts.create') }}" title="عقد جديد">
            <x-icon class="w-[19px] h-[19px]" name="plus" />
        </a>
    </div>
</header>
{{-- نهاية: ترويسة الهاتف --}}
