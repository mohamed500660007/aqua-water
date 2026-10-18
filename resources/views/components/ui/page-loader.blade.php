{{-- مؤشر تحميل الصفحات: قطرة مياه تمتلئ بموجة متحركة وتتساقط منها تموّجات.
     يظهر فقط أثناء التنقّل بين الصفحات (صنف is-navigating على <html>) وبعد تأخير قصير،
     فلا يومض في التنقّلات السريعة. الأنماط في layouts/app تحت «مؤشر التحميل». --}}
<div aria-hidden="true" class="page-loader">
    <div class="page-loader-card">
        <div class="page-loader-drop">
            <svg viewBox="0 0 64 80" width="56" height="70">
                <defs>
                    <clipPath id="pageLoaderDropClip">
                        <path d="M32 4C32 4 8 32 8 52a24 24 0 0 0 48 0C56 32 32 4 32 4Z" />
                    </clipPath>
                    <linearGradient id="pageLoaderWater" x1="0" x2="0" y1="0" y2="1">
                        <stop offset="0" stop-color="#7DD3FC" />
                        <stop offset="1" stop-color="#0284C7" />
                    </linearGradient>
                </defs>

                <path d="M32 4C32 4 8 32 8 52a24 24 0 0 0 48 0C56 32 32 4 32 4Z" fill="#E0F2FE" />

                <g clip-path="url(#pageLoaderDropClip)">
                    <g class="page-loader-level">
                        <path class="page-loader-wave page-loader-wave--back" fill="#38BDF8" opacity=".45"
                            d="M-64 6Q-56 0-48 6T-32 6T-16 6T0 6T16 6T32 6T48 6T64 6T80 6T96 6V90H-64Z" />
                        <path class="page-loader-wave" fill="url(#pageLoaderWater)"
                            d="M-64 8Q-56 2-48 8T-32 8T-16 8T0 8T16 8T32 8T48 8T64 8T80 8T96 8V90H-64Z" />
                    </g>
                </g>

                <path d="M32 4C32 4 8 32 8 52a24 24 0 0 0 48 0C56 32 32 4 32 4Z" fill="none" stroke="#0284C7"
                    stroke-linejoin="round" stroke-width="2.5" />
                <ellipse cx="21" cy="48" fill="#fff" opacity=".7" rx="3.5" ry="8" transform="rotate(18 21 48)" />
            </svg>

            <span class="page-loader-ripple"></span>
            <span class="page-loader-ripple page-loader-ripple--late"></span>
        </div>

        <p class="page-loader-text">جارٍ التحميل…</p>
    </div>
</div>
