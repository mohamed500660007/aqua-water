{{-- صف الأرقام الرئيسية في التقرير المطبوع.
     items: قائمة [العنوان، القيمة، الوحدة (اختياري)، ملاحظة (اختياري)] — العنصر المميَّز يُمرَّر عبر highlight (رقم الفهرس). --}}
@props(['items', 'highlight' => null])

<table class="rpt-figures">
    <tr>
        @foreach ($items as $index => $item)
            @php([$label, $value, $unit, $hint] = array_pad($item, 4, null))
            <td @class(['is-highlight' => $index === $highlight])>
                <span class="rpt-figure-label">{{ $label }}</span>
                <span class="rpt-figure-value">{{ $value }}@if ($unit) <small>{{ $unit }}</small>@endif</span>
                @if ($hint)
                    <span class="rpt-figure-hint">{{ $hint }}</span>
                @endif
            </td>
        @endforeach
    </tr>
</table>
