@extends('layouts.app')

@section('title', 'ملف الصيانة')
@section('page-title', 'ملف الصيانة')
@section('page-subtitle', 'الأجهزة المستحقة لتغيير الشمع حسب جدول كل جهاز من تاريخ تركيبه')

@php($hasFilters = filled(request('q')) || request('window', 'week') !== 'week')

@section('content')
    <x-ui.page-header icon="calendar-clock" subtitle="الأجهزة المستحقة لتغيير الشمع حسب جدول كل جهاز من تاريخ تركيبه" title="ملف الصيانة">
        <x-slot:actions>
            <x-ui.btn icon="wrench" size="sm" :href="route('maintenance.operations.create')">تسجيل صيانة</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    @include('pages.maintenance.partials.tabs')

    {{-- الدورة كلها في سطر: يفهمها الموظف الجديد بدون شرح --}}
    <ol class="mb-4 grid grid-cols-1 sm:grid-cols-3 gap-2">
        @foreach ([
            ['sliders-horizontal', 'حدد الشمع مرة واحدة', 'من «إعداد الشمع»: شمعة كل مرحلة وكل كام شهر تتغير'],
            ['calendar-clock', 'المستحقين بيظهروا هنا لوحدهم', 'حسب تاريخ تركيب كل جهاز وآخر صيانة له'],
            ['wrench', 'اضغط «تمت الصيانة»', 'علّم الشمع اللي اتغير — والموعد الجاي بيتحدد تلقائيًا'],
        ] as $i => [$icon, $title, $text])
            <li class="flex items-start gap-3 rounded-2xl bg-white border border-gray-100 px-3.5 py-3">
                <span class="w-8 h-8 shrink-0 rounded-xl bg-brand-green/20 text-brand-dark flex items-center justify-center">
                    <x-icon class="w-4 h-4" :name="$icon" />
                </span>
                <span class="min-w-0">
                    <span class="block text-[12.5px] font-bold text-gray-800">{{ $i + 1 }}. {{ $title }}</span>
                    <span class="block text-[11px] text-gray-400 leading-relaxed">{{ $text }}</span>
                </span>
            </li>
        @endforeach
    </ol>

    @if ($stages->isEmpty())
        <div class="mb-4 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3.5">
            <x-icon class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" name="triangle-alert" />
            <div class="flex-1">
                <p class="text-[13px] font-bold text-amber-800">لم تُضبط مراحل الشمع بعد</p>
                <p class="text-[12px] text-amber-700 mt-0.5">حدّد شمعة كل مرحلة (1 → 7) وكل كام شهر تتغير، ليُنشأ جدول صيانة تلقائي لكل جهاز مركّب.</p>
            </div>
            <x-ui.btn icon="sliders-horizontal" size="sm" :href="route('maintenance.stages.edit')">ضبط المراحل</x-ui.btn>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <form class="p-4 border-b border-gray-100 flex flex-col lg:flex-row lg:items-center gap-2.5" action="{{ route('maintenance.file') }}"
            method="GET" x-data="{ q: @js(request('q', '')) }" x-on:submit.prevent="navigateForm($el)">
            <x-ui.search-input name="q" placeholder="ابحث باسم العميل أو كوده أو كود عقد الجهاز..." value="{{ request('q') }}"
                x-model="q" x-on:input.debounce.450ms="navigateForm($el.form)" />
            <x-ui.select icon="calendar-clock" name="window" :options="$windows" placeholder="خلال 7 أيام"
                :selected="$window" x-on:change="navigateForm($el.form)" />
            @if ($hasFilters)
                <a class="shrink-0 h-11 px-3.5 rounded-xl text-[12px] font-bold text-gray-500 border border-gray-200 flex items-center gap-1.5"
                    href="{{ route('maintenance.file') }}" wire:navigate>
                    <x-icon class="w-3.5 h-3.5" name="rotate-ccw" /> إعادة ضبط
                </a>
            @endif
        </form>

        <div class="overflow-x-auto scrollbar-hide">
            <table class="w-full min-w-[920px]">
                <thead>
                    <tr>
                        <x-ui.th>العميل</x-ui.th>
                        <x-ui.th>الجهاز</x-ui.th>
                        <x-ui.th>الشمعات المستحقة</x-ui.th>
                        <x-ui.th>أقرب موعد</x-ui.th>
                        <x-ui.th>آخر صيانة</x-ui.th>
                        <x-ui.th>عقد الصيانة</x-ui.th>
                        <x-ui.th align="left">إجراءات</x-ui.th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($devices as $device)
                        @php($nextDue = \Illuminate\Support\Carbon::parse($device->next_due_at))
                        <tr>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <x-ui.avatar :name="$device->customer?->name ?? '—'" size="w-10 h-10" />
                                    <div class="min-w-0">
                                        <p class="text-[13px] font-bold text-gray-800 truncate">{{ $device->customer?->name ?? '—' }}</p>
                                        <p class="text-[11px] text-gray-400" dir="ltr">{{ $device->customer?->primaryPhone?->phone ?? $device->customer?->code }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-[12.5px] font-black text-gray-800 font-mono" dir="ltr">{{ $device->code }}</p>
                                <p class="text-[11px] text-gray-400">تركيب <span dir="ltr">{{ $device->installation_date->format('Y-m-d') }}</span></p>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1 max-w-[300px]">
                                    @foreach ($device->candleSchedules as $schedule)
                                        <x-ui.badge :tone="$schedule->isOverdue() ? 'red' : 'amber'"
                                            title="{{ $stages->get($schedule->position)?->product?->name }} — {{ $schedule->next_due_at->format('Y-m-d') }}">
                                            {{ $schedule->position }} · {{ \Illuminate\Support\Str::limit($stages->get($schedule->position)?->product?->name ?? 'مرحلة', 14) }}
                                        </x-ui.badge>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <p class="text-[12.5px] font-bold {{ $nextDue->lt(today()) ? 'text-red-600' : 'text-gray-800' }}" dir="ltr">{{ $nextDue->format('Y-m-d') }}</p>
                                <p class="text-[11px] text-gray-400">
                                    @if ($nextDue->lt(today()))
                                        متأخر {{ $nextDue->diffInDays(today()) }} يوم
                                    @elseif ($nextDue->isToday())
                                        اليوم
                                    @else
                                        بعد {{ today()->diffInDays($nextDue) }} يوم
                                    @endif
                                </p>
                            </td>
                            <td class="px-4 py-3 text-[12px] text-gray-600">
                                @if ($device->last_serviced_at)
                                    تمت الصيانة <span class="font-bold" dir="ltr">{{ \Illuminate\Support\Carbon::parse($device->last_serviced_at)->format('Y-m-d') }}</span>
                                @else
                                    <span class="text-gray-400">لم تُسجّل صيانة</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($package = $device->activeMaintenancePackage)
                                    <x-ui.badge tone="purple">رصيد {{ $package->remainingCandles() }} شمعة</x-ui.badge>
                                @else
                                    <span class="text-[12px] text-gray-400">قطاعي</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end">
                                    <x-ui.btn icon="check" size="sm" :href="route('maintenance.operations.create', ['contract' => $device->id])">تمت الصيانة</x-ui.btn>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($devices->isEmpty())
            <x-ui.empty-state icon="calendar-check" :title="$stages->isEmpty() ? 'لا يوجد جدول صيانة بعد' : 'لا توجد أجهزة مستحقة'"
                :message="$stages->isEmpty() ? 'اضبط مراحل الشمع أولًا ليظهر العملاء المستحقون هنا تلقائيًا.' : 'كل الأجهزة في موعدها — جرّب فترة أطول من قائمة الاستحقاق.'" />
        @endif

        <x-ui.paginator :paginator="$devices" />
    </div>
@endsection
