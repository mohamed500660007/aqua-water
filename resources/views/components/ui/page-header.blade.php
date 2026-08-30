@props(['title', 'subtitle' => null, 'icon' => null])

{{-- العنوان يعرضه هيكل التطبيق نفسه (ترويسة الهاتف وترويسة سطح المكتب)، فلا يُكرَّر هنا.
     هذا المكوّن مسؤول عن صف الإجراءات فقط — يلتفّ على الشاشات الضيقة بدل أن يدفع
     الصفحة أفقيًا. --}}
@isset($actions)
    <div class="flex flex-wrap items-center gap-2 mb-4 lg:mb-5">{{ $actions }}</div>
@endisset
