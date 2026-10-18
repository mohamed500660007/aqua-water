{{-- بداية: ترويسة سطح المكتب --}}
<header class="hidden lg:flex items-center justify-between gap-6 px-8 py-5 bg-white border-b border-gray-100 sticky top-0 z-30">
    <div class="min-w-0">
        <h1 class="text-xl font-bold text-gray-800 truncate">@yield('page-title', 'لوحة التحكم')</h1>
        <p class="text-xs text-gray-400 mt-0.5">@yield('page-subtitle', 'إدارة عمليات أكوا ووتر')</p>
    </div>

    <div class="flex items-center gap-3">
        {{-- البحث في العملاء --}}
        <form action="{{ route('customers.index') }}" class="relative" method="GET">
            <x-icon class="w-4 h-4 absolute right-4 top-1/2 -translate-y-1/2 text-gray-400" name="search" />
            <input
                class="pr-11 pl-4 py-2.5 rounded-full bg-gray-50 border-none text-sm w-64 focus:ring-2 focus:ring-brand-green/50 outline-none transition-shadow placeholder:text-gray-400"
                name="q" placeholder="ابحث عن عميل بالاسم أو الهاتف..." type="search" />
        </form>

        {{-- أزرار الصفحة الحالية (تدفعها x-ui.page-header) --}}
        @stack('page-actions')
    </div>
</header>
{{-- نهاية: ترويسة سطح المكتب --}}
