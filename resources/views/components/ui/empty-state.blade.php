@props(['icon' => 'search-x', 'title' => 'لا توجد نتائج', 'message' => 'جرّب تعديل كلمات البحث أو إعادة ضبط الفلاتر.'])

{{-- أيقونة داكنة عائمة داخل حلقتين، واحدة منقطة تدور ببطء وعليها نقطة ليمونية --}}
<div {{ $attributes->merge(['class' => 'ui-empty']) }}>
    <div class="ui-empty-art" aria-hidden="true">
        <span class="ui-empty-ring"></span>
        <span class="ui-empty-ring is-inner"></span>
        <span class="ui-empty-icon"><x-icon :name="$icon" /></span>
    </div>
    <h3>{{ $title }}</h3>
    <p>{{ $message }}</p>
    @isset($action)
        <div class="mt-5">{{ $action }}</div>
    @endisset
</div>
