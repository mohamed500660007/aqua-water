@extends('layouts.app')

@section('title', 'تسجيل حركة مخزون')
@section('page-title', 'تسجيل حركة مخزون')
@section('page-subtitle', 'وارد أو منصرف أو تسوية جرد')

@section('content')
    <x-ui.page-header icon="plus" subtitle="وارد أو منصرف أو تسوية جرد" title="تسجيل حركة مخزون">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('inventory.movements.index')">رجوع
                للسجل</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>
@endsection
