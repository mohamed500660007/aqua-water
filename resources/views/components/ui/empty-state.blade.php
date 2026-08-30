@props(['icon' => 'search-x', 'title' => 'لا توجد نتائج', 'message' => 'جرّب تعديل كلمات البحث أو إعادة ضبط الفلاتر.'])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center py-14 px-6']) }}>
    <span class="w-16 h-16 rounded-3xl bg-gray-50 text-gray-300 flex items-center justify-center mb-4">
        <x-icon class="w-7 h-7" :name="$icon" />
    </span>
    <h3 class="text-sm font-bold text-gray-700 mb-1">{{ $title }}</h3>
    <p class="text-xs text-gray-400 max-w-[280px] leading-relaxed">{{ $message }}</p>
    @isset($action)
        <div class="mt-5">{{ $action }}</div>
    @endisset
</div>
