@extends('layouts.app')

@section('title', 'منتج جديد')
@section('page-title', 'منتج جديد')
@section('page-subtitle', 'إضافة منتج إلى الكتالوج')

@section('content')
    <x-ui.page-header icon="plus" subtitle="إضافة منتج إلى الكتالوج" title="منتج جديد">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('products.index')">رجوع للقائمة</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    @include('pages.products.form', [
        'product' => null,
        'action' => route('products.store'),
        'method' => 'POST',
        'submitLabel' => 'حفظ المنتج',
    ])
@endsection
