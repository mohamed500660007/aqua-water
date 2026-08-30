@extends('layouts.app')

@section('title', 'خطة صيانة جديدة')
@section('page-title', 'خطة صيانة جديدة')
@section('page-subtitle', 'إنشاء خطة صيانة دورية')

@section('content')
    <x-ui.page-header icon="shield-check" subtitle="إنشاء خطة صيانة دورية" title="خطة صيانة جديدة">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('maintenance-plans.index')">رجوع للقائمة</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    @include('pages.maintenance-plans.form', [
        'plan' => null,
        'action' => route('maintenance-plans.store'),
        'method' => 'POST',
        'submitLabel' => 'حفظ الخطة',
    ])
@endsection
