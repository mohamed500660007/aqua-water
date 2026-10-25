{{-- الرسومات البديلة للعبة (أصلية) — تظهر لو مفيش صور في public/images/game. كلها باصّة لليمين. --}}
@if ($actor === 'tom')
    <svg class="tj-art" viewBox="0 0 160 120" width="132" height="99" aria-hidden="true">
        <path class="tj-tail" d="M40 64C14 62 8 34 24 22" fill="none" stroke="#EA580C" stroke-width="10" stroke-linecap="round" />
        <g class="tj-leg tj-leg-a"><rect x="44" y="76" width="13" height="32" rx="6.5" fill="#EA580C" /><ellipse cx="50.5" cy="107" rx="8" ry="4.5" fill="#FFEDD5" /></g>
        <g class="tj-leg tj-leg-b"><rect x="60" y="76" width="13" height="32" rx="6.5" fill="#C2410C" /><ellipse cx="66.5" cy="107" rx="8" ry="4.5" fill="#FED7AA" /></g>
        <g class="tj-leg tj-leg-b"><rect x="94" y="76" width="13" height="32" rx="6.5" fill="#EA580C" /><ellipse cx="100.5" cy="107" rx="8" ry="4.5" fill="#FFEDD5" /></g>
        <g class="tj-leg tj-leg-a"><rect x="108" y="76" width="13" height="32" rx="6.5" fill="#C2410C" /><ellipse cx="114.5" cy="107" rx="8" ry="4.5" fill="#FED7AA" /></g>
        <ellipse cx="80" cy="70" rx="48" ry="26" fill="#FB923C" />
        <path d="M58 48q4 12 0 22M74 45q4 12 0 24M90 46q4 12 0 22" fill="none" stroke="#EA580C" stroke-width="5" stroke-linecap="round" />
        <ellipse cx="86" cy="82" rx="30" ry="11" fill="#FFEDD5" />
        <path d="M106 30l4-26 16 18z" fill="#FB923C" />
        <path d="M130 22l12-20 6 26z" fill="#FB923C" />
        <path d="M110 24l2-13 8 9z" fill="#FDA4AF" />
        <path d="M134 20l7-11 3 13z" fill="#FDA4AF" />
        <circle cx="126" cy="46" r="27" fill="#FB923C" />
        <path d="M118 22q4 6 0 12M128 20q4 6 0 12" fill="none" stroke="#EA580C" stroke-width="4" stroke-linecap="round" />
        <ellipse cx="144" cy="58" rx="13" ry="9" fill="#FFEDD5" />
        <ellipse cx="124" cy="42" rx="6" ry="8" fill="#fff" />
        <ellipse cx="139" cy="41" rx="7" ry="9" fill="#fff" />
        <circle cx="126" cy="43" r="3.4" fill="#1A1A1A" />
        <circle cx="142" cy="42" r="4" fill="#1A1A1A" />
        <path class="tj-brow" d="M118 31l12 4M134 31l12-2" stroke="#7C2D12" stroke-width="3" stroke-linecap="round" />
        <path d="M151 52l5 3-5 3z" fill="#F472B6" />
        <path d="M150 60q-4 6-10 3" fill="none" stroke="#1A1A1A" stroke-width="2" stroke-linecap="round" />
        <path d="M148 56l14-4M148 60l14 1M148 63l12 5" stroke="#7C2D12" stroke-width="1.4" stroke-linecap="round" />
    </svg>
@elseif ($actor === 'jerry')
    <svg class="tj-art" viewBox="0 0 90 66" width="70" height="51" aria-hidden="true">
        <path class="tj-tail" d="M18 44C4 44 0 30 8 22" fill="none" stroke="#F9A8D4" stroke-width="3.5" stroke-linecap="round" />
        <g class="tj-leg tj-leg-a"><rect x="30" y="48" width="6" height="14" rx="3" fill="#92400E" /></g>
        <g class="tj-leg tj-leg-b"><rect x="50" y="48" width="6" height="14" rx="3" fill="#92400E" /></g>
        <ellipse cx="42" cy="42" rx="25" ry="16" fill="#C2803A" />
        <ellipse cx="46" cy="48" rx="14" ry="7" fill="#FDE6C8" />
        <circle cx="54" cy="16" r="12" fill="#C2803A" />
        <circle cx="54" cy="16" r="7" fill="#FBCFE8" />
        <circle cx="66" cy="32" r="15" fill="#C2803A" />
        <ellipse cx="78" cy="36" rx="7" ry="5" fill="#FDE6C8" />
        <circle cx="70" cy="28" r="3" fill="#1A1A1A" />
        <circle cx="71" cy="27" r="1" fill="#fff" />
        <circle cx="84" cy="34" r="3.2" fill="#1A1A1A" />
        <path d="M73 40q5 4 10 0" fill="none" stroke="#1A1A1A" stroke-width="1.6" stroke-linecap="round" />
        <path d="M80 36l9-3M80 38l9 1M80 40l8 4" stroke="#57534E" stroke-width="1" stroke-linecap="round" />
    </svg>
@else
    <svg class="tj-art" viewBox="0 0 150 110" width="138" height="101" aria-hidden="true">
        <path class="tj-tail" d="M28 52l-14-10" stroke="#6B7280" stroke-width="8" stroke-linecap="round" />
        <g class="tj-leg tj-leg-a"><rect x="36" y="70" width="16" height="32" rx="7" fill="#6B7280" /></g>
        <g class="tj-leg tj-leg-b"><rect x="56" y="70" width="16" height="32" rx="7" fill="#4B5563" /></g>
        <g class="tj-leg tj-leg-b"><rect x="88" y="70" width="16" height="32" rx="7" fill="#6B7280" /></g>
        <g class="tj-leg tj-leg-a"><rect x="106" y="70" width="16" height="32" rx="7" fill="#4B5563" /></g>
        <ellipse cx="74" cy="64" rx="50" ry="28" fill="#9CA3AF" />
        <ellipse cx="80" cy="76" rx="30" ry="12" fill="#E5E7EB" />
        <path d="M96 40h40" stroke="#B91C1C" stroke-width="7" stroke-linecap="round" />
        <path d="M100 37l3-7 3 7M112 37l3-7 3 7M124 37l3-7 3 7" fill="#D1D5DB" />
        <circle cx="118" cy="40" r="26" fill="#9CA3AF" />
        <path d="M98 22q-10-10-16 2l10 8z" fill="#6B7280" />
        <path d="M134 18q10-6 12 6l-10 6z" fill="#6B7280" />
        <ellipse cx="128" cy="56" rx="20" ry="13" fill="#D1D5DB" />
        <path d="M112 60q16 10 32 0" fill="none" stroke="#1A1A1A" stroke-width="2.5" stroke-linecap="round" />
        <path d="M116 61l3 6 3-5M134 61l3 5 3-6" fill="#fff" />
        <ellipse cx="141" cy="46" rx="6" ry="4.5" fill="#1A1A1A" />
        <circle cx="116" cy="34" r="4" fill="#1A1A1A" />
        <circle cx="132" cy="33" r="4" fill="#1A1A1A" />
        <path d="M108 27l12 4M128 28l10-3" stroke="#1A1A1A" stroke-width="3" stroke-linecap="round" />
    </svg>
@endif
