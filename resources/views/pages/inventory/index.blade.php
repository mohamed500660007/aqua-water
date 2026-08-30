@extends('layouts.app')

@section('title', 'المخزون')
@section('page-title', 'المخزون')
@section('page-subtitle', 'أرصدة المنتجات في المستودعات')

@section('content')
    <x-ui.page-header icon="package" subtitle="أرصدة المنتجات في المستودعات" title="المخزون">
        <x-slot:actions>
            <x-ui.btn icon="list" size="sm" variant="soft" :href="route('inventory.movements.index')">حركات المخزون</x-ui.btn>
            <x-ui.btn icon="user-cog" size="sm" variant="soft" :href="route('inventory.employee-stocks.index')">مخزون
                الموظفين</x-ui.btn>
            <x-ui.btn icon="plus" size="sm" :href="route('inventory.movements.create')">تسجيل حركة</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>
@endsection
