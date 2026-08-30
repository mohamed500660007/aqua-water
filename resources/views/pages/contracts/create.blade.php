@extends('layouts.app')

@section('title', 'عقد بيع جديد')
@section('page-title', 'عقد بيع جديد')
@section('page-subtitle', 'إنشاء عقد بيع نقدي أو بالتقسيط')

@section('content')
    <x-ui.page-header icon="file-text" subtitle="إنشاء عقد بيع نقدي أو بالتقسيط" title="عقد بيع جديد">
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
