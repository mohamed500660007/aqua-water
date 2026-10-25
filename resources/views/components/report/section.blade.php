{{-- قسم مرقّم داخل التقرير المطبوع: رقم + عنوان + ملاحظة اختيارية على اليسار --}}
@props(['no', 'title', 'note' => null])

<section {{ $attributes->merge(['class' => 'rpt-section']) }}>
    <div class="rpt-section-head">
        <span class="rpt-section-no">{{ $no }}</span>
        <h2 class="rpt-section-title">{{ $title }}</h2>
        @if ($note)
            <span class="rpt-section-note">{{ $note }}</span>
        @endif
    </div>
    {{ $slot }}
</section>
