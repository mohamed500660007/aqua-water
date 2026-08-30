@extends('layouts.app')

@section('title', 'عميل جديد')
@section('page-title', 'عميل جديد')
@section('page-subtitle', 'إضافة عميل إلى قاعدة البيانات')

@section('content')
    <x-ui.page-header icon="user-plus" subtitle="إضافة عميل إلى قاعدة البيانات" title="عميل جديد">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('customers.index')">رجوع للقائمة</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    @include('pages.customers.form', [
        'customer' => null,
        'action' => route('customers.store'),
        'method' => 'POST',
        'submitLabel' => 'حفظ العميل',
    ])
@endsection
