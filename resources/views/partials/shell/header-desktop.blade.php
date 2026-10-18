{{-- بداية: ترويسة سطح المكتب --}}
<header class="hidden lg:flex items-center justify-between gap-6 px-8 py-5 bg-[#FAFAFA]/90 backdrop-blur-md sticky top-0 z-30">
    <div class="min-w-0">
        <h1 class="text-xl font-bold text-gray-800 truncate">@yield('page-title', 'لوحة التحكم')</h1>
        <p class="text-xs text-gray-400 mt-0.5">@yield('page-subtitle', 'إدارة عمليات أكوا ووتر')</p>
    </div>

    <div class="flex items-center gap-3">
        {{-- أزرار الصفحة الحالية (تدفعها x-ui.page-header) --}}
        @stack('page-actions')
    </div>
</header>
{{-- نهاية: ترويسة سطح المكتب --}}
