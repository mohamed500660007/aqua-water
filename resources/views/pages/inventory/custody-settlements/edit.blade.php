@extends('layouts.app')

@section('title', 'تعديل التسوية ' . $settlement->number())
@section('page-title', 'تعديل التسوية ' . $settlement->number())
@section('page-subtitle', 'المسودة لم تُرحّل بعد ولم تغيّر أي رصيد')

@section('content')
    <x-ui.page-header icon="scale" subtitle="المسودة لم تُرحّل بعد ولم تغيّر أي رصيد" :title="'تعديل التسوية ' . $settlement->number()">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('inventory.custody-settlements.index')">رجوع
                للتسويات</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    @include('pages.inventory.custody-settlements.form', [
        'action' => route('inventory.custody-settlements.update', $settlement),
        'method' => 'PUT',
    ])
@endsection
