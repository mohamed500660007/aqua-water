@props(['title', 'subtitle' => null, 'icon' => null])

{{-- العنوان يعرضه هيكل التطبيق نفسه (ترويسة الهاتف وترويسة سطح المكتب)، فلا يُكرَّر هنا.
     الأيقونة والأزرار تُدفع إلى ترويسة سطح المكتب عبر stack حتى تظهر مرة واحدة بجوار العنوان،
     وعلى الهاتف تُعرض الأزرار هنا في صف يلتفّ على الشاشات الضيقة. --}}
@if ($icon)
    @push('page-icon')
        <x-icon :name="$icon" />
    @endpush
@endif

@isset($actions)
    @push('page-actions')
        {{ $actions }}
    @endpush

    <div class="mobile-page-actions lg:hidden flex flex-wrap items-center gap-2 mb-4">{{ $actions }}</div>
@endisset
