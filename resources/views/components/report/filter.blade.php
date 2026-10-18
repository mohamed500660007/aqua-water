{{-- حقل فلتر في شريط أدوات التقرير المطبوع (لا يظهر في الورقة). options: [القيمة => النص] --}}
@props(['name', 'label', 'options' => [], 'selected' => null, 'placeholder' => 'الكل'])

<label class="flex flex-col gap-1 min-w-0">
    <span class="text-[11px] font-bold text-gray-500">{{ $label }}</span>
    <select class="h-9 w-full min-w-0 rounded border border-gray-300 bg-white px-2 text-xs font-semibold text-gray-800 focus:border-black focus:outline-none"
        name="{{ $name }}">
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $value => $text)
            <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $text }}</option>
        @endforeach
    </select>
</label>
