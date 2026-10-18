{{-- بداية: عنوان الصفحة وأزرارها على سطح المكتب — لا يُرسمان هنا، بل يُنقلان (x-teleport) إلى خانتي
     الصف العلوي بجوار شريط التنقل، فيظهر الكل في سطر واحد. عند الانتقال لصفحة أخرى يحذف Alpine
     النسخة القديمة تلقائيًا مع حذف هذا القالب، وتحل محلها نسخة الصفحة الجديدة. --}}
<div class="hidden" x-data>
    <template x-teleport="#tnStart">
        <div class="tn-page">
            @hasstack('page-icon')
                <span class="tn-page-icon">@stack('page-icon')</span>
            @endif
            <h1 class="tn-page-title" title="@yield('page-subtitle')"><span>@yield('page-title', 'لوحة التحكم')</span></h1>
        </div>
    </template>

    @hasstack('page-actions')
        <template x-teleport="#tnEnd">
            <div class="tn-actions" x-data="actionDock()">
                {{-- ما لا يتسع له السطر من الأزرار ينتقل إلى قائمة «المزيد» ويبقى الزر الأساسي ظاهرًا --}}
                <details class="tn-more" hidden x-ref="more" x-on:click.outside="$el.open = false">
                    <summary aria-label="بقية الإجراءات"><x-icon name="ellipsis" /></summary>
                    <div class="tn-more-menu" x-ref="menu"></div>
                </details>
                <div class="tn-dock" x-ref="dock">@stack('page-actions')</div>
            </div>
        </template>
    @endif
</div>
{{-- نهاية: عنوان الصفحة وأزرارها على سطح المكتب --}}
