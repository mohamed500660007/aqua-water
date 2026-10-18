@extends('layouts.app')

@section('title', 'تسوية عهدة جديدة')
@section('page-title', 'تسوية عهدة جديدة')
@section('page-subtitle', 'تصحيح رصيد عهدة موظف بالموجب أو بالسالب دون تحويل مخزني')

@section('content')
    <x-ui.page-header icon="scale" subtitle="تصحيح رصيد عهدة موظف بالموجب أو بالسالب دون تحويل مخزني" title="تسوية عهدة جديدة">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('inventory.custody-settlements.index')">رجوع
                للتسويات</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    @include('pages.inventory.custody-settlements.form', [
        'settlement' => null,
        'action' => route('inventory.custody-settlements.store'),
        'method' => 'POST',
    ])
@endsection
