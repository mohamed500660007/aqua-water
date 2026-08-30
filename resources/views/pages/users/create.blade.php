@extends('layouts.app')

@section('title', 'مستخدم جديد')
@section('page-title', 'مستخدم جديد')
@section('page-subtitle', 'إضافة مستخدم إلى لوحة التحكم')

@section('content')
    <x-ui.page-header icon="user-plus" subtitle="إضافة مستخدم إلى لوحة التحكم" title="مستخدم جديد">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('users.index')">رجوع للقائمة</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    @include('pages.users.form', [
        'user' => null,
        'action' => route('users.store'),
        'method' => 'POST',
        'submitLabel' => 'حفظ المستخدم',
    ])
@endsection
