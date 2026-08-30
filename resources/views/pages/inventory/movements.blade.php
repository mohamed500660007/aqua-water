@extends('layouts.app')

@section('title', 'حركات المخزون')
@section('page-title', 'حركات المخزون')
@section('page-subtitle', 'سجل الوارد والمنصرف والتسويات')

@section('content')
    <x-ui.page-header icon="list" subtitle="سجل الوارد والمنصرف والتسويات" title="حركات المخزون">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('inventory.index')">أرصدة المخزن</x-ui.btn>
            <x-ui.btn icon="plus" size="sm" :href="route('inventory.movements.create')">تسجيل حركة</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>
@endsection
