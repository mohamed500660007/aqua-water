@extends('layouts.print')

@section('title', 'إيصال تحصيل — '.$installment->contract->code)

@section('toolbar')
    <header class="no-print w-full max-w-5xl mb-5 flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-xl shadow-md border border-gray-200">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-black text-white flex items-center justify-center text-lg">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <div>
                <h1 class="text-base font-bold text-gray-800 leading-tight">إيصال تحصيل قسط</h1>
                <p class="text-xs text-gray-500">{{ $installment->contract->customer?->name }} — القسط {{ $installment->number }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a class="px-3.5 py-2 text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center gap-1.5"
                href="{{ route('installments.index') }}">
                <i class="fa-solid fa-arrow-right"></i>
                <span>رجوع للأقساط</span>
            </a>
            <button class="px-4 py-2 text-xs font-bold text-white bg-black hover:bg-gray-800 rounded-lg shadow transition-all flex items-center gap-2"
                onclick="window.print()" type="button">
                <i class="fa-solid fa-print text-sm"></i>
                <span>طباعة الإيصال</span>
            </button>
        </div>
    </header>
@endsection

@section('sheets')
    <div class="page w-full flex flex-col items-center">
        @include('partials.installments.receipt-sheet', ['installment' => $installment])
    </div>
@endsection
