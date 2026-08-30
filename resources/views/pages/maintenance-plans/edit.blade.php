@extends('layouts.app')

@section('title', 'تعديل خطة صيانة')
@section('page-title', 'تعديل خطة صيانة')
@section('page-subtitle', $plan->name)

@section('content')
    <x-ui.page-header icon="pencil" subtitle="تعديل بيانات الخطة وبنودها" :title="'تعديل ' . $plan->name">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('maintenance-plans.show', $plan)">رجوع للتفاصيل</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    @include('pages.maintenance-plans.form', [
        'plan' => $plan,
        'action' => route('maintenance-plans.update', $plan),
        'method' => 'PUT',
        'submitLabel' => 'حفظ التعديلات',
    ])
@endsection
