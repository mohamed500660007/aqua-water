@extends('layouts.app')

@section('title', 'تعديل منتج')
@section('page-title', 'تعديل منتج')
@section('page-subtitle', $product->name)

@section('content')
    <x-ui.page-header icon="pencil" :subtitle="$product->sku" :title="'تعديل ' . $product->name">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('products.show', $product)">رجوع للتفاصيل</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    @include('pages.products.form', [
        'product' => $product,
        'action' => route('products.update', $product),
        'method' => 'PUT',
        'submitLabel' => 'حفظ التعديلات',
    ])
@endsection
