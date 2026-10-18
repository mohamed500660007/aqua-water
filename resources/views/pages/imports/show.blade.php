@extends('layouts.app')

{{-- صفحة استيراد من إكسيل مشتركة. المتغيرات:
     $title، $subtitle، $backUrl، $backLabel، $templateUrl، $storeUrl، $templateHint، $tips (list)، $maxRows، $noun --}}
@section('title', $title)
@section('page-title', $title)
@section('page-subtitle', $subtitle)

@php($importErrors = session('importErrors', []))

@section('content')
    <x-ui.page-header icon="file-spreadsheet" :subtitle="$subtitle" :title="$title">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="$backUrl">{{ $backLabel }}</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-5">
        <div class="lg:col-span-2 space-y-4 lg:space-y-5">
            {{-- الخطوة 1: القالب --}}
            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                    <span class="w-12 h-12 shrink-0 rounded-2xl bg-brand-green/25 text-green-700 flex items-center justify-center">
                        <x-icon class="w-6 h-6" name="file-down" />
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-[11px] font-bold text-gray-400 mb-0.5">الخطوة 1</p>
                        <h2 class="text-sm font-bold text-gray-800">حمّل قالب الإكسيل</h2>
                        <p class="text-[12px] text-gray-500 mt-1">{{ $templateHint }}</p>
                    </div>
                    {{-- تحميل ملف عادي لا تنقّل SPA --}}
                    <a class="inline-flex items-center justify-center gap-2 min-h-12 px-5 rounded-xl brand-green hover:bg-[#c2f040] shadow-sm text-[13px] font-bold whitespace-nowrap transition-all active:scale-95"
                        href="{{ $templateUrl }}" download>
                        <x-icon class="w-[18px] h-[18px]" name="download" />
                        تحميل القالب
                    </a>
                </div>
            </section>

            {{-- الخطوة 2: الرفع --}}
            <form class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5" action="{{ $storeUrl }}"
                method="POST" enctype="multipart/form-data" x-data="{ fileName: '', sending: false }" x-on:submit="sending = true">
                @csrf
                <div class="flex items-center gap-4 mb-4">
                    <span class="w-12 h-12 shrink-0 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center">
                        <x-icon class="w-6 h-6" name="file-up" />
                    </span>
                    <div>
                        <p class="text-[11px] font-bold text-gray-400 mb-0.5">الخطوة 2</p>
                        <h2 class="text-sm font-bold text-gray-800">ارفع الملف بعد ملئه</h2>
                    </div>
                </div>

                <label class="flex flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed px-4 py-8 text-center cursor-pointer transition-colors"
                    x-bind:class="fileName ? 'border-brand-green bg-brand-green/10' : '{{ $errors->has('file') ? 'border-red-300 bg-red-50/40' : 'border-gray-200 hover:bg-gray-50' }}'">
                    <x-icon class="w-8 h-8 text-gray-400" name="file-spreadsheet" />
                    <span class="text-[13px] font-bold text-gray-700" x-text="fileName || 'اضغط لاختيار ملف الإكسيل'"></span>
                    <span class="text-[11px] text-gray-400">ملف إكسيل (xlsx) — حتى 5 ميجابايت · {{ number_format($maxRows) }} {{ $noun }} كحد أقصى</span>
                    <input class="sr-only" accept=".xlsx" name="file" required type="file"
                        x-on:change="fileName = $event.target.files[0]?.name || ''" />
                </label>

                @error('file')
                    <p class="text-xs font-semibold text-red-500 mt-2">{{ $message }}</p>
                @enderror

                <x-ui.btn class="w-full mt-4" icon="upload" type="submit" x-bind:disabled="! fileName || sending">
                    <span x-text="sending ? 'جارٍ الاستيراد...' : 'استيراد'"></span>
                </x-ui.btn>
            </form>

            @if ($importErrors)
                <section class="bg-white rounded-2xl border border-red-100 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 bg-red-50 border-b border-red-100">
                        <h2 class="flex items-center gap-2 text-sm font-bold text-red-600">
                            <x-icon class="w-4 h-4" name="circle-alert" /> لم يُستورد أي {{ $noun }}
                        </h2>
                        <p class="text-[12px] text-red-500 mt-1">
                            {{ number_format(count($importErrors)) }} صف من أصل {{ number_format(session('importRows')) }} فيها أخطاء —
                            صحّحها في نفس الملف وارفعه مرة أخرى.
                        </p>
                    </div>
                    <div class="max-h-[420px] overflow-y-auto divide-y divide-gray-50">
                        @foreach ($importErrors as $row => $messages)
                            <div class="flex items-start gap-3 px-5 py-3">
                                <span class="shrink-0 min-w-[64px] text-center rounded-lg bg-gray-100 px-2 py-1 text-[11px] font-black text-gray-700">
                                    صف {{ $row }}</span>
                                <ul class="space-y-0.5 text-[12px] text-gray-600">
                                    @foreach ($messages as $message)
                                        <li>{{ $message }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        <aside class="lg:col-span-1">
            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 lg:sticky lg:top-2">
                <h2 class="flex items-center gap-2 text-sm font-bold text-gray-800 mb-3">
                    <x-icon class="w-4 h-4 text-gray-400" name="info" /> قبل الرفع
                </h2>
                <ul class="space-y-2.5 text-[12px] leading-relaxed text-gray-600 list-disc pr-4">
                    @foreach ($tips as $tip)
                        <li>{{ $tip }}</li>
                    @endforeach
                </ul>
            </section>
        </aside>
    </div>
@endsection
