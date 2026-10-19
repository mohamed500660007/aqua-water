{{-- خطوة اختيار الجهاز: بحث بالاسم أو الكود أو رقم الهاتف، ثم الضغط على الجهاز يفتح النموذج له.
     المتغيرات: $results، $route (اسم مسار صفحة النموذج) --}}
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden max-w-3xl">
    <form class="p-5 border-b border-gray-100" action="{{ route($route) }}" method="GET" x-data="{ q: @js(request('q', '')) }"
        x-on:submit.prevent="navigateForm($el)">
        <p class="text-[11px] font-bold text-gray-400 mb-1">الخطوة 1</p>
        <h2 class="text-sm font-bold text-gray-800 mb-3">اختر جهاز العميل</h2>
        <x-ui.search-input name="q" placeholder="اسم العميل أو كوده أو رقم هاتفه أو كود عقد الجهاز..." value="{{ request('q') }}"
            x-model="q" x-on:input.debounce.450ms="navigateForm($el.form)" autofocus />
    </form>

    <div class="divide-y divide-gray-50">
        @foreach ($results as $device)
            <a class="flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50/70 transition-colors"
                href="{{ route($route, ['contract' => $device->id]) }}" wire:navigate>
                <x-ui.avatar :name="$device->customer?->name ?? '—'" size="w-10 h-10" />
                <div class="min-w-0 flex-1">
                    <p class="text-[13px] font-bold text-gray-800 truncate">{{ $device->customer?->name }}</p>
                    <p class="text-[11px] text-gray-400 truncate">
                        <span dir="ltr">{{ $device->code }}</span> ·
                        {{ $device->items->pluck('product_name')->join('، ') ?: 'جهاز' }} ·
                        تركيب <span dir="ltr">{{ $device->installation_date->format('Y-m-d') }}</span>
                    </p>
                </div>
                <span class="text-[12px] text-gray-400 shrink-0" dir="ltr">{{ $device->customer?->primaryPhone?->phone }}</span>
                <x-icon class="w-4 h-4 text-gray-300 shrink-0" name="chevron-left" />
            </a>
        @endforeach
    </div>

    @if (filled(request('q')) && $results->isEmpty())
        <x-ui.empty-state icon="search-x" title="لا يوجد جهاز مطابق"
            message="الأجهزة تظهر هنا بعد تسجيل عقد بيعها وتاريخ تركيبها." />
    @elseif (blank(request('q')))
        <p class="px-5 py-6 text-[12px] text-gray-400 text-center">ابدأ بكتابة اسم العميل أو رقم هاتفه — أو افتح النموذج من «ملف الصيانة» مباشرة.</p>
    @endif
</div>
