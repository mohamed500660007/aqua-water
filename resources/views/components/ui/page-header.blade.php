@props(['title', 'subtitle' => null, 'icon' => null])

{{-- العنوان يعرضه هيكل التطبيق نفسه (ترويسة الهاتف وترويسة سطح المكتب)، فلا يُكرَّر هنا.
     أزرار الصفحة تُدفع إلى ترويسة سطح المكتب عبر stack حتى تظهر مرة واحدة بجوار العنوان،
     وعلى الهاتف تُعرض هنا في صف يلتفّ على الشاشات الضيقة. --}}
@isset($actions)
    @push('page-actions')
        {{ $actions }}
    @endpush

    <div class="mobile-page-actions lg:hidden flex flex-wrap items-center gap-2 mb-4">{{ $actions }}</div>
@endisset
