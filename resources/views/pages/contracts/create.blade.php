@extends('layouts.app')

@section('title', 'عقد جديد')
@section('page-title', 'عقد جديد')
@section('page-subtitle', 'عقد بيع جهاز أو عقد صيانة — نقدي أو بالتقسيط')

@section('content')
    <x-ui.page-header icon="file-text" subtitle="عقد بيع جهاز أو عقد صيانة — نقدي أو بالتقسيط" title="عقد جديد">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('contracts.index')">رجوع للقائمة</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    @include('pages.contracts.form', [
        'contract' => null,
        'action' => route('contracts.store'),
        'method' => 'POST',
        'submitLabel' => 'حفظ العقد',
    ])
@endsection
