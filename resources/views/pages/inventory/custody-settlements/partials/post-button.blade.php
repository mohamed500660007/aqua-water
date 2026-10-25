{{-- ترحيل مسودة تسوية بعد التأكيد. المتغيرات: $settlement، $iconOnly --}}
@php($message = 'سيتم تطبيق ' . $settlement->type->label() . ' ' . $settlement->number() . ' على عهدة الموظف نهائيًا، ولن يتغير رصيد أي مخزن.')

<form action="{{ route('inventory.custody-settlements.post', $settlement) }}" method="POST" x-data="{ message: {{ Js::from($message) }} }">
    @csrf
    @if ($iconOnly ?? false)
        <x-ui.icon-btn icon="send" label="ترحيل" tone="primary"
            x-on:click="$store.confirm.ask({ title: 'ترحيل التسوية', message, label: 'ترحيل', onOk: () => $root.requestSubmit() })" />
    @else
        <x-ui.btn class="w-full" icon="send" size="sm"
            x-on:click="$store.confirm.ask({ title: 'ترحيل التسوية', message, label: 'ترحيل', onOk: () => $root.requestSubmit() })">ترحيل التسوية</x-ui.btn>
    @endif
</form>
