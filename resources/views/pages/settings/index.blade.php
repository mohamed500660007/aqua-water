@extends('layouts.app')

@section('title', 'الإعدادات')
@section('page-title', 'الإعدادات')
@section('page-subtitle', 'بيانات الشركة والطباعة والعقود وقوائم الاختيار')

@use('App\Domain\Settings\Settings')

@php
    $tabs = $sections->map(fn ($section) => ['label' => $section['label'], 'icon' => $section['icon']])->all() + [
        'reference' => ['label' => 'القيم الثابتة', 'icon' => 'lock'],
        'system' => ['label' => 'معلومات النظام', 'icon' => 'server'],
    ];
    $active = array_key_exists(request('tab'), $tabs) ? request('tab') : array_key_first($tabs);
    // بعد خطأ تحقق يُفتح القسم الذي أُرسل منه النموذج
    $active = old('_section', $active);
@endphp

@section('content')
    <x-ui.page-header icon="settings" subtitle="كل إعداد يُطبَّق فورًا على اللوحة والتقارير والإيصالات" title="الإعدادات" />

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 lg:gap-5" x-data="{ tab: @js($active) }">
        {{-- أقسام الإعدادات --}}
        <nav class="lg:col-span-1">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-2 flex lg:flex-col gap-1 overflow-x-auto scrollbar-hide lg:sticky lg:top-2">
                @foreach ($tabs as $key => $tab)
                    <button class="shrink-0 flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl text-[13px] font-bold text-right transition-colors"
                        type="button" x-on:click="tab = @js($key); history.replaceState(null, '', '?tab={{ $key }}')"
                        x-bind:class="tab === @js($key) ? 'bg-brand-dark text-brand-green' : 'text-gray-600 hover:bg-gray-50'">
                        <x-icon class="w-[18px] h-[18px]" :name="$tab['icon']" />
                        {{ $tab['label'] }}
                    </button>
                @endforeach
            </div>
        </nav>

        <div class="lg:col-span-3 min-w-0">
            {{-- الأقسام القابلة للتعديل: نموذج مستقل لكل قسم --}}
            @foreach ($sections as $key => $section)
                <section class="bg-white rounded-2xl border border-gray-100 shadow-sm" x-cloak x-show="tab === @js($key)">
                    <form action="{{ route('settings.update', $key) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input name="_section" type="hidden" value="{{ $key }}" />

                        <header class="flex items-start gap-3 p-5 border-b border-gray-100">
                            <span class="w-10 h-10 shrink-0 rounded-xl bg-brand-green/25 text-green-700 flex items-center justify-center">
                                <x-icon class="w-5 h-5" :name="$section['icon']" />
                            </span>
                            <div>
                                <h2 class="text-[15px] font-black text-gray-800">{{ $section['label'] }}</h2>
                                <p class="text-[12px] text-gray-400 mt-0.5">{{ $section['hint'] }}</p>
                            </div>
                        </header>

                        @if ($errors->any() && old('_section') === $key)
                            <div class="mx-5 mt-4 rounded-2xl border border-red-100 bg-red-50 px-4 py-3">
                                <ul class="list-disc pr-5 space-y-1">
                                    @foreach ($errors->all() as $message)
                                        <li class="text-[12px] text-red-500">{{ $message }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-5">
                            @foreach ($section['fields'] as $name => $field)
                                @php
                                    $value = $section['values'][$name];
                                    $isList = $field['type'] === Settings::LIST;
                                    $wide = $isList || $field['type'] === Settings::TEXTAREA;
                                    $hint = $field['hint'] ?? ($isList ? count($value) . ' عنصر — سطر لكل عنصر' : null);
                                @endphp
                                <x-ui.field :col="$wide ? 'sm:col-span-2' : 'sm:col-span-1'" :hint="$hint" :label="$field['label']" :name="$name"
                                    :required="$field['required'] ?? in_array($field['type'], [Settings::INTEGER, Settings::DECIMAL], true)"
                                    :rows="$isList ? min(10, max(3, count($value))) : 2"
                                    :type="match ($field['type']) { Settings::INTEGER, Settings::DECIMAL => 'number', Settings::LIST, Settings::TEXTAREA => 'textarea', default => 'text' }"
                                    :value="$isList ? implode(PHP_EOL, $value) : $value"
                                    :step="$field['type'] === Settings::DECIMAL ? '0.01' : null"
                                    :min="$field['min'] ?? null" :max="isset($field['min']) ? $field['max'] : null" />
                            @endforeach
                        </div>

                        <footer class="flex flex-wrap items-center justify-between gap-2.5 px-5 py-4 border-t border-gray-100 bg-gray-50/60 rounded-b-2xl">
                            <x-ui.btn icon="rotate-ccw" size="sm" variant="ghost"
                                x-on:click="$store.confirm.ask({
                                    title: 'استعادة القيم الافتراضية',
                                    message: 'سترجع كل خانات «{{ $section['label'] }}» لقيمها الأصلية.',
                                    label: 'استعادة',
                                    onOk: () => $refs.reset_{{ $key }}.submit()
                                })">القيم الافتراضية</x-ui.btn>
                            <x-ui.btn icon="check" type="submit">حفظ {{ $section['label'] }}</x-ui.btn>
                        </footer>
                    </form>

                    <form action="{{ route('settings.reset', $key) }}" class="hidden" method="POST" x-ref="reset_{{ $key }}">
                        @csrf
                        @method('DELETE')
                    </form>
                </section>
            @endforeach

            {{-- قيم ثابتة مرتبطة بمنطق النظام — للعرض فقط --}}
            <section class="space-y-4" x-cloak x-show="tab === 'reference'">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <h2 class="text-[15px] font-black text-gray-800">أدوار الموظفين</h2>
                    <p class="text-[12px] text-gray-400 mt-0.5 mb-4">
                        ثابتة لأن صلاحيات النظام مرتبطة بأسمائها: «فني تركيب» يركّب ويصون ويحمل عهدة، و«مندوب مبيعات» يبيع ويحمل عهدة،
                        و«محصل» يحصّل الأقساط.</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($roles as $role)
                            <span class="px-3 py-1.5 rounded-xl bg-gray-50 border border-gray-100 text-[12.5px] font-bold text-gray-700">{{ $role }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <h2 class="text-[15px] font-black text-gray-800">خطة الصيانة</h2>
                    <p class="text-[12px] text-gray-400 mt-0.5 mb-4">سبع شمعات بمواعيد ثابتة تنطبق على كل الفلاتر، وتُنسخ على كل عقد من تاريخ تركيبه.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach ($candles as $candle)
                            <div class="flex items-center justify-between gap-3 p-3 rounded-xl border border-gray-100">
                                <span class="text-[13px] font-bold text-gray-800">{{ $candle['label'] }}</span>
                                <span class="text-[12px] font-bold text-gray-500">{{ $candle['interval'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5" x-cloak x-show="tab === 'system'">
                <h2 class="text-[15px] font-black text-gray-800 mb-4">معلومات النظام</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6">
                    @foreach ($system as $label => $value)
                        <x-ui.detail-row :label="$label"><span dir="ltr">{{ $value }}</span></x-ui.detail-row>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
@endsection
