@props(['size' => 96])

{{-- شعار أكوا ووتر: قطرة مياه داخل حلقة بلون العلامة (ليموني → بنفسجي) وموجة ليمونية في قاعها.
     المعرّفات فريدة لكل نسخة: تكرار id مع نسخة مخفية (display:none) يُبطل التدرّجات في النسخ الظاهرة. --}}
@php($id = 'aqLogo'.\Illuminate\Support\Str::random(6))

<svg {{ $attributes->merge(['class' => 'brand-logo']) }} viewBox="0 0 96 96" width="{{ $size }}" height="{{ $size }}"
    role="img" aria-label="شعار {{ $settings->get('company_name') }}">
    <defs>
        <linearGradient id="{{ $id }}Ring" x1="0" x2="1" y1="0" y2="1">
            <stop offset="0" stop-color="#D4FF5B" />
            <stop offset=".55" stop-color="#B494F7" />
            <stop offset="1" stop-color="#38BDF8" />
        </linearGradient>
        <linearGradient id="{{ $id }}Water" x1="0" x2="0" y1="0" y2="1">
            <stop offset="0" stop-color="#7DD3FC" />
            <stop offset="1" stop-color="#0369A1" />
        </linearGradient>
        <clipPath id="{{ $id }}Drop">
            <path d="M48 16C48 16 25 43 25 60a23 23 0 0 0 46 0C71 43 48 16 48 16Z" />
        </clipPath>
    </defs>

    <circle class="brand-logo-ring" cx="48" cy="48" r="44" fill="none" stroke="url(#{{ $id }}Ring)"
        stroke-dasharray="210 66" stroke-linecap="round" stroke-width="3" />
    <circle cx="48" cy="48" r="37" fill="#fff" fill-opacity=".06" />

    <path d="M48 16C48 16 25 43 25 60a23 23 0 0 0 46 0C71 43 48 16 48 16Z" fill="url(#{{ $id }}Water)" />

    <g clip-path="url(#{{ $id }}Drop)">
        <g class="brand-logo-level">
            <path class="brand-logo-wave brand-logo-wave--back" fill="#B494F7" opacity=".55"
                d="M-48 64Q-40 58-32 64T-16 64T0 64T16 64T32 64T48 64T64 64T80 64T96 64T112 64V100H-48Z" />
            <path class="brand-logo-wave" fill="#D4FF5B"
                d="M-48 68Q-40 62-32 68T-16 68T0 68T16 68T32 68T48 68T64 68T80 68T96 68T112 68V100H-48Z" />
        </g>
    </g>

    <path d="M48 16C48 16 25 43 25 60a23 23 0 0 0 46 0C71 43 48 16 48 16Z" fill="none" stroke="#fff"
        stroke-opacity=".35" stroke-linejoin="round" stroke-width="1.5" />
    <ellipse cx="37" cy="54" fill="#fff" opacity=".75" rx="3.2" ry="7.5" transform="rotate(20 37 54)" />
    <circle cx="82" cy="20" r="3.2" fill="#D4FF5B" />
</svg>
