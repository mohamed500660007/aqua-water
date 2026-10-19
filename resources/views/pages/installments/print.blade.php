@extends('layouts.print')

@section('title', 'إيصالات الأقساط المستحقة')

@section('toolbar')
    <header class="no-print w-full max-w-5xl mb-5 bg-white p-4 rounded-xl shadow-md border border-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-black text-white flex items-center justify-center text-lg">
                    <i class="fa-solid fa-print"></i>
                </div>
                <div>
                    <h1 class="text-base font-bold text-gray-800 leading-tight">إيصالات الأقساط المستحقة</h1>
                    <p class="text-xs text-gray-500">
                        كل قسط غير محصَّل يستحق خلال الفترة — ثلاثة إيصالات في كل ورقة A4، ولكل إيصال كعب للمدير
                    </p>
                </div>
            </div>

            <form class="flex flex-wrap items-center gap-2" action="{{ route('installments.print') }}" method="GET">
                <label class="flex items-center gap-1.5 text-xs font-bold text-gray-600">
                    من
                    <input class="h-9 rounded-lg border-gray-300 text-xs font-sans border px-2 focus:border-black focus:outline-none"
                        name="from" type="date" value="{{ $from->toDateString() }}" />
                </label>
                <label class="flex items-center gap-1.5 text-xs font-bold text-gray-600">
                    إلى
                    <input class="h-9 rounded-lg border-gray-300 text-xs font-sans border px-2 focus:border-black focus:outline-none"
                        name="to" type="date" value="{{ $to->toDateString() }}" />
                </label>
                <button class="h-9 px-3.5 text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center gap-1.5"
                    type="submit">
                    <i class="fa-solid fa-filter"></i>
                    <span>عرض</span>
                </button>
                <a class="h-9 px-3.5 text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center gap-1.5"
                    href="{{ route('installments.index') }}">
                    <i class="fa-solid fa-arrow-right"></i>
                    <span>رجوع</span>
                </a>
                <button class="h-9 px-4 text-xs font-bold text-white bg-black hover:bg-gray-800 rounded-lg shadow transition-all flex items-center gap-2 disabled:opacity-40 disabled:pointer-events-none"
                    onclick="window.print()" type="button" @disabled($installments->isEmpty())>
                    <i class="fa-solid fa-print text-sm"></i>
                    <span>طباعة {{ $installments->count() }} إيصال</span>
                </button>
            </form>
        </div>

        <div class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap items-center gap-x-6 gap-y-1 text-xs text-gray-600">
            <span>الفترة: <b class="font-sans" dir="ltr">{{ $from->toDateString() }} → {{ $to->toDateString() }}</b></span>
            <span>عدد الإيصالات: <b>{{ $installments->count() }}</b></span>
            <span>إجمالي المستحق: <b class="font-sans">{{ number_format($installments->sum('amount'), 2) }}</b> ج.م</span>
        </div>
    </header>
@endsection

@section('sheets')
    @forelse ($installments->chunk(3) as $group)
        {{-- كل ثلاثة إيصالات في ورقة A4 واحدة تفصل بينها خطوط قصّ --}}
        <div class="page w-full flex flex-col items-center gap-6">
            @foreach ($group as $installment)
                @include('partials.installments.receipt-sheet', ['installment' => $installment, 'receiptNote' => $receiptNote])
                @unless ($loop->last)
                    <div class="hcut w-full"></div>
                @endunless
            @endforeach
        </div>
    @empty
        <div class="no-print w-full max-w-5xl bg-white rounded-xl border border-dashed border-gray-300 p-10 text-center">
            <i class="fa-regular fa-folder-open text-3xl text-gray-300 mb-3"></i>
            <p class="text-sm font-bold text-gray-700">لا توجد أقساط غير محصَّلة في هذه الفترة</p>
            <p class="text-xs text-gray-400 mt-1">غيّر التاريخ من / إلى ثم اضغط «عرض».</p>
        </div>
    @endforelse
@endsection
