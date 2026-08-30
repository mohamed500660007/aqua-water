@extends('layouts.app')

@section('title', 'تعديل عقد')
@section('page-title', 'تعديل عقد')
@section('page-subtitle', $contract->code)

@section('content')
    <x-ui.page-header icon="pencil" :subtitle="$contract->customer?->name" :title="'تعديل عقد ' . $contract->code">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('contracts.show', $contract)">رجوع للتفاصيل</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    @include('pages.contracts.form', [
        'contract' => $contract,
        'action' => route('contracts.update', $contract),
        'method' => 'PUT',
        'submitLabel' => 'حفظ التعديلات',
    ])
@endsection
