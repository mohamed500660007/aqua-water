{{-- بداية: ترويسة الهاتف — جزيرة داكنة بنفس لغة شريط سطح المكتب --}}
<header class="lg:hidden sticky top-0 z-30 px-3 pt-3 pb-2 bg-[#FAFAFA]/85 backdrop-blur-md">
    <div class="m-island m-header">
        <a class="m-logo" href="{{ route('home') }}" aria-label="{{ $settings->get('company_name') }} — الرئيسية" wire:navigate>
            <x-icon class="w-5 h-5" name="droplets" />
        </a>

        <div class="m-title">
            <small>@yield('page-subtitle', $settings->get('company_name'))</small>
            <strong>@yield('page-title', 'لوحة التحكم')</strong>
        </div>

        <button class="m-icon-btn" type="button" aria-label="ابحث عن عميل"
            x-data x-on:click="Alpine.$data(document.getElementById('bottomNav')).openMenu(true)">
            <x-icon class="w-5 h-5" name="search" />
        </button>
    </div>
</header>
{{-- نهاية: ترويسة الهاتف --}}
