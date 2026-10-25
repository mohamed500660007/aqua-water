@extends('layouts.app')

@section('title', 'ملف التقارير')
@section('page-title', 'ملف التقارير')
@section('page-subtitle', 'كل تقارير الشركة في مكان واحد')

@php
    $today = today();
    $presets = [
        'today' => ['اليوم', $today->toDateString(), $today->toDateString()],
        'month' => ['هذا الشهر', $today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()],
        'last_month' => ['الشهر الماضي', $today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(), $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString()],
        'quarter' => ['آخر 3 شهور', $today->copy()->subMonthsNoOverflow(2)->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()],
        'year' => ['هذه السنة', $today->copy()->startOfYear()->toDateString(), $today->copy()->endOfYear()->toDateString()],
    ];
    $reportsCount = collect($groups)->sum(fn ($group) => count($group['reports']));
@endphp

@section('content')
    <style>
        .rpt-hub-card { position: relative; overflow: hidden; transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease; }
        .rpt-hub-card::before { content: ''; position: absolute; inset: 0; background: radial-gradient(120% 90% at 100% 0%, var(--tone-soft), transparent 55%); pointer-events: none; }
        .rpt-hub-card:hover { transform: translateY(-2px); border-color: var(--tone-line); box-shadow: 0 18px 36px -24px var(--tone-solid); }
        .rpt-hub-icon { background: var(--tone-soft); color: var(--tone); box-shadow: inset 0 0 0 1px var(--tone-line); }
        .rpt-hub-chip { background: var(--tone-soft); color: var(--tone); }
        .rpt-hub-open { color: var(--tone); }
        .rpt-hub-featured { background: linear-gradient(135deg, #0F172A, #1E293B); color: #fff; border-color: transparent; }
        .rpt-hub-featured::before { background: radial-gradient(110% 90% at 100% 0%, rgba(198, 242, 62, .18), transparent 60%); }
        .rpt-hub-featured .rpt-hub-icon { background: #C6F23E; color: #0F172A; box-shadow: none; }
        .rpt-hub-featured .rpt-hub-chip { background: rgba(255, 255, 255, .1); color: rgba(255, 255, 255, .85); }
        .rpt-hub-featured .rpt-hub-open { color: #C6F23E; }
    </style>

    <div x-data="{
        from: @js($presets['month'][1]),
        to: @js($presets['month'][2]),
        preset: 'month',
        presets: @js($presets),
        pick(key) { this.preset = key; this.from = this.presets[key][1]; this.to = this.presets[key][2]; },
        link(url) { return url + '?' + new URLSearchParams({ from: this.from, to: this.to }).toString(); }
    }">
        <x-ui.page-header icon="clipboard-list" subtitle="اختر الفترة مرة واحدة، وافتح أي تقرير جاهز للطباعة" title="ملف التقارير" />

        {{-- فترة واحدة لكل التقارير --}}
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-5 mb-5">
            <div class="flex flex-col lg:flex-row lg:items-end gap-4">
                <div class="flex-1 min-w-0">
                    <p class="text-[12px] font-bold text-gray-600 mb-2">فترة التقارير</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($presets as $key => [$label])
                            <button class="px-3.5 py-2 rounded-xl text-[12px] font-bold transition-colors"
                                type="button" x-on:click="pick(@js($key))"
                                x-bind:class="preset === @js($key) ? 'bg-brand-dark text-brand-green' : 'bg-gray-50 text-gray-600 hover:bg-gray-100'">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2.5 lg:w-[340px]">
                    <label class="flex flex-col gap-1">
                        <span class="text-[11px] font-bold text-gray-500">من</span>
                        <input class="h-10 px-3 rounded-xl border border-gray-200 text-[13px] focus:ring-2 focus:ring-brand-green/50 outline-none"
                            type="date" x-model="from" x-on:change="preset = null" />
                    </label>
                    <label class="flex flex-col gap-1">
                        <span class="text-[11px] font-bold text-gray-500">إلى</span>
                        <input class="h-10 px-3 rounded-xl border border-gray-200 text-[13px] focus:ring-2 focus:ring-brand-green/50 outline-none"
                            type="date" x-model="to" x-on:change="preset = null" />
                    </label>
                </div>
            </div>
            <p class="text-[11px] text-gray-400 mt-3">
                {{ $reportsCount }} تقرير — كل تقرير يفتح في صفحة مستقلة بمقاس A4 للطباعة، وفيه فلاتره الخاصة لتضييق النطاق.
            </p>
        </section>

        <div class="space-y-6">
            @foreach ($groups as $group)
                <section>
                    <div class="flex items-baseline gap-2.5 mb-3 px-1">
                        <h2 class="text-[15px] font-black text-gray-800">{{ $group['label'] }}</h2>
                        <span class="text-[11.5px] text-gray-400">{{ $group['hint'] }}</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3.5">
                        @foreach ($group['reports'] as $report)
                            @php($featured = $report['featured'] ?? false)
                            <a @class(['rpt-hub-card ui-tone-' . $report['tone'] . ' block rounded-2xl border p-4 sm:p-5', 'rpt-hub-featured md:col-span-1 xl:col-span-1' => $featured, 'bg-white border-gray-100 shadow-sm' => ! $featured])
                                href="{{ route($report['route']) }}" rel="noopener" target="_blank"
                                x-bind:href="link(@js(route($report['route'])))">
                                <div class="relative flex items-start gap-3">
                                    <span class="rpt-hub-icon w-11 h-11 shrink-0 rounded-2xl flex items-center justify-center">
                                        <x-icon class="w-5 h-5" :name="$report['icon']" />
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <h3 @class(['text-[14.5px] font-black leading-snug', 'text-gray-800' => ! $featured])>{{ $report['title'] }}</h3>
                                        <p @class(['text-[12px] leading-relaxed mt-1', 'text-white/70' => $featured, 'text-gray-500' => ! $featured])>{{ $report['description'] }}</p>
                                    </div>
                                </div>

                                <div class="relative flex flex-wrap gap-1.5 mt-3.5">
                                    @foreach ($report['includes'] as $part)
                                        <span class="rpt-hub-chip px-2 py-1 rounded-lg text-[10.5px] font-bold">{{ $part }}</span>
                                    @endforeach
                                </div>

                                <p class="rpt-hub-open relative flex items-center gap-1.5 text-[12px] font-black mt-4">
                                    <x-icon class="w-4 h-4" name="printer" /> فتح التقرير
                                    <x-icon class="w-3.5 h-3.5" name="arrow-up-left" />
                                </p>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </div>
@endsection
