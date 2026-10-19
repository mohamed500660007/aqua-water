@extends('layouts.app')

@section('title', 'مراحل الشمع')
@section('page-title', 'مراحل الشمع')
@section('page-subtitle', 'أي شمعة في كل مرحلة من مراحل الفلتر وكل كام شهر تتغير')

@section('content')
    <x-ui.page-header icon="sliders-horizontal" subtitle="أي شمعة في كل مرحلة من مراحل الفلتر وكل كام شهر تتغير" title="مراحل الشمع">
    </x-ui.page-header>

    @include('pages.maintenance.partials.tabs')

    <form action="{{ route('maintenance.stages.update') }}" method="POST">
        @csrf
        @method('PUT')

        @if ($errors->any())
            <div class="mb-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3.5">
                <ul class="list-disc pr-5 space-y-1">
                    @foreach ($errors->all() as $message)
                        <li class="text-[12px] text-red-500">{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-5">
            <section class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="space-y-3">
                    @foreach ($positions as $position)
                        @php($stage = $stages[$position] ?? null)
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end rounded-2xl border border-gray-100 p-3.5">
                            <div class="sm:col-span-1 flex sm:block items-center gap-2">
                                <span class="w-10 h-10 rounded-xl bg-brand-dark text-brand-green font-black flex items-center justify-center">{{ $position }}</span>
                            </div>
                            <x-ui.field col="sm:col-span-7" label="شمعة المرحلة {{ $position }}" name="stages[{{ $position }}][product_id]" type="select"
                                :options="$products" :value="old('stages.' . $position . '.product_id', $stage?->product_id)" placeholder="غير مستخدمة" />
                            <x-ui.field col="sm:col-span-4" label="تتغير كل (شهر)" name="stages[{{ $position }}][interval_months]" type="number"
                                min="1" max="120" :value="old('stages.' . $position . '.interval_months', $stage?->interval_months)" placeholder="مثال: 3" />
                        </div>
                    @endforeach
                </div>
            </section>

            <aside class="lg:col-span-1">
                <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 lg:sticky lg:top-2">
                    <h2 class="text-sm font-bold text-gray-800 mb-3">كيف يعمل الجدول؟</h2>
                    <ul class="space-y-2.5 text-[12px] leading-relaxed text-gray-600 list-disc pr-4">
                        <li>لكل جهاز مركّب: موعد كل مرحلة = تاريخ التركيب + دورية المرحلة.</li>
                        <li>بعد تسجيل صيانة المرحلة يتجدد موعدها من تاريخ الصيانة تلقائيًا.</li>
                        <li>المرحلة المتروكة «غير مستخدمة» لا تظهر في ملف الصيانة.</li>
                        <li>تعديل المراحل يعيد حساب مواعيد كل الأجهزة فورًا.</li>
                    </ul>
                    <x-ui.btn class="w-full mt-5" icon="check" type="submit">حفظ المراحل</x-ui.btn>
                </section>
            </aside>
        </div>
    </form>
@endsection
