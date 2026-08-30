@php($items = \App\Support\Lookups::bottomNav())
@php($moreRoutes = ['maintenance-plans.*', 'users.*'])
@php($moreActive = request()->routeIs($moreRoutes))

{{-- بداية: شريط التنقل السفلي العائم (الهاتف فقط — التنقل الأساسي) --}}
<nav class="lg:hidden fixed inset-x-4 z-40 bottom-nav-safe transition-all duration-300 ease-out" id="bottomNav">
    <div
        class="mx-auto max-w-[480px] bg-white/95 backdrop-blur-md border border-gray-100 shadow-[0_10px_30px_-8px_rgba(26,26,26,0.25)] rounded-[1.75rem] p-1.5 flex items-stretch gap-1">

        @foreach ($items as $item)
            @php($active = request()->routeIs($item['match'] ?? $item['route']))
            <a class="flex-1 min-h-[54px] flex flex-col items-center justify-center gap-1 rounded-[1.4rem] transition-all duration-200 {{ $active ? 'bg-brand-green text-brand-dark' : 'text-gray-400 active:bg-gray-50' }}"
                href="{{ route($item['route']) }}">
                <x-icon class="w-[22px] h-[22px]" :name="$item['icon']" />
                <span class="text-[10px] {{ $active ? 'font-semibold' : 'font-medium' }} leading-none">{{ $item['label'] }}</span>
            </a>
        @endforeach

        {{-- المزيد: يفتح الـ Bottom Sheet --}}
        <button aria-controls="moreSheet" aria-expanded="false"
            class="flex-1 min-h-[54px] flex flex-col items-center justify-center gap-1 rounded-[1.4rem] transition-all duration-200 {{ $moreActive ? 'bg-brand-green text-brand-dark' : 'text-gray-400 active:bg-gray-50' }}"
            id="openMoreSheet" type="button">
            <x-icon class="w-[22px] h-[22px]" name="menu" />
            <span class="text-[10px] {{ $moreActive ? 'font-semibold' : 'font-medium' }} leading-none">المزيد</span>
        </button>
    </div>
</nav>
{{-- نهاية: شريط التنقل السفلي --}}
