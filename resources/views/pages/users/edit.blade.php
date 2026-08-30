@extends('layouts.app')

@section('title', 'تعديل مستخدم')
@section('page-title', 'تعديل مستخدم')
@section('page-subtitle', $user->name)

@section('content')
    <x-ui.page-header icon="pencil" :subtitle="$user->email" :title="'تعديل ' . $user->name">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('users.show', $user)">رجوع للتفاصيل</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    @include('pages.users.form', [
        'user' => $user,
        'action' => route('users.update', $user),
        'method' => 'PUT',
        'submitLabel' => 'حفظ التعديلات',
    ])
@endsection
