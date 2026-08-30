@extends('layouts.app')

@section('title', 'تعديل عميل')
@section('page-title', 'تعديل عميل')
@section('page-subtitle', $customer->name)

@section('content')
    <x-ui.page-header icon="pencil" :subtitle="$customer->code" :title="'تعديل ' . $customer->name">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('customers.show', $customer)">رجوع للتفاصيل</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    @include('pages.customers.form', [
        'customer' => $customer,
        'action' => route('customers.update', $customer),
        'method' => 'PUT',
        'submitLabel' => 'حفظ التعديلات',
    ])
@endsection
