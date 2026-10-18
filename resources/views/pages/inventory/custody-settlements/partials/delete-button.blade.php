{{-- حذف مسودة تسوية — المُرحّلة لا تُحذف. المتغيرات: $settlement --}}
<form action="{{ route('inventory.custody-settlements.destroy', $settlement) }}" method="POST" x-data>
    @csrf
    @method('DELETE')
    <x-ui.icon-btn icon="trash-2" label="حذف المسودة" tone="danger"
        x-on:click="$store.confirm.ask({
            title: 'حذف مسودة التسوية',
            message: {{ Js::from('سيتم حذف المسودة ' . $settlement->number() . '. لم تغيّر هذه المسودة أي رصيد.') }},
            label: 'حذف',
            onOk: () => $root.requestSubmit()
        })" />
</form>
