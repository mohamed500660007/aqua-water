{{-- «مطاردة البيت» — لعبة سلابستيك على طريقة كرتون القط والفار القديم. إنتي الفار:
     - لمّي الجبنة، وخدي المقلاة (القط يتخبط ويتفرطح)، وارمي قشر موز (يتزحلق ويطير)،
       واستدرجيه لمصيدة الفيران، وادخلي الجحر فيخبط في الحيطة، وخدي العضمة فييجي الكلب يطارده.
     - وهي مش شغالة: عرض تلقائي للمطاردة نفسها من غير صوت.
     صور الشخصيات وأصواتها اختيارية: أي ملف يتحط في public/images/game أو public/sounds/game بالاسم
     المكتوب تحت بيتستخدم تلقائيًا، وإلا تظهر رسومات بديلة وأصوات بتتعمل في المتصفح. --}}
@php
    $sprites = collect(['tom', 'tom-flat', 'jerry', 'spike'])
        ->mapWithKeys(fn (string $name) => [$name => file_exists(public_path("images/game/{$name}.png")) ? asset("images/game/{$name}.png") : null]);
    $sounds = collect(['theme', 'cheese', 'pan', 'slip', 'snap', 'scream', 'crash', 'bark', 'caught', 'levelup', 'item', 'start', 'gameover'])
        ->mapWithKeys(fn (string $name) => [$name => file_exists(public_path("sounds/game/{$name}.mp3")) ? asset("sounds/game/{$name}.mp3") : null])
        ->filter();
@endphp

<section class="tj home-reveal" style="--delay: .1s" aria-label="لعبة مطاردة البيت" x-data="toonChase(@js($sounds))">
    @include('pages.home.game-styles')

    <header class="tj-bar">
        <div class="tj-title">
            <span class="tj-logo">
                <svg viewBox="0 0 40 40" width="40" height="40" aria-hidden="true">
                    <rect width="40" height="40" rx="13" fill="#1A1A1A" />
                    <path d="M8 30l6-14 7 9 4-6 7 11z" fill="#FACC15" />
                    <circle cx="27" cy="13" r="4" fill="#D4FF5B" />
                </svg>
            </span>
            <span>
                <b>مطاردة البيت</b>
                <small x-text="playing ? 'إنتي الفار — الأسهم أو الماوس للحركة، والمسافة ترمي موزة' : 'لمّي الجبنة واعملي في القط المقالب كلها'"></small>
            </span>
        </div>

        <div class="tj-hud">
            <template x-if="playing || over">
                <div class="tj-stats">
                    <span class="tj-chip tj-chip--score"><b x-text="score"></b> نقطة</span>
                    <span class="tj-chip">مستوى <b x-text="level"></b></span>
                    <span class="tj-chip tj-lives" x-bind:aria-label="`${lives} أرواح`">
                        <template x-for="i in 3" x-bind:key="i">
                            <span x-bind:class="{ 'is-lost': i > lives }">♥</span>
                        </template>
                    </span>
                    <span class="tj-chip tj-inventory" x-show="pan || bananas" x-cloak>
                        <span x-show="pan">🍳</span>
                        <span x-show="bananas" x-text="`🍌×${bananas}`"></span>
                    </span>
                </div>
            </template>
            <template x-if="! playing && best > 0">
                <span class="tj-chip">أحسن نتيجة <b x-text="best"></b></span>
            </template>

            <button class="tj-btn tj-btn--icon" type="button" x-on:click="toggleSound()" x-bind:title="muted ? 'تشغيل الصوت' : 'كتم الصوت'"
                x-bind:aria-label="muted ? 'تشغيل الصوت' : 'كتم الصوت'">
                <span x-show="! muted">🔊</span>
                <span x-show="muted" x-cloak>🔇</span>
            </button>
            <button class="tj-btn tj-btn--icon" type="button" x-show="playing" x-cloak x-on:click="togglePause()"
                x-bind:title="paused ? 'كمّلي' : 'وقفي'" x-bind:aria-label="paused ? 'كمّلي' : 'وقفي'">
                <span x-text="paused ? '▶' : '❚❚'"></span>
            </button>
            <button class="tj-btn tj-btn--go" type="button" x-on:click="playing ? endGame(true) : startGame()">
                <span x-text="playing ? 'إنهاء' : 'ابدئي المطاردة'"></span>
            </button>
        </div>
    </header>

    <div class="tj-stage" x-ref="stage" tabindex="0"
        x-on:pointerdown="pointerDown($event)" x-on:pointermove="pointerMove($event)" x-on:pointerleave="pointerLeave()">
        {{-- أوضة المعيشة --}}
        <div class="tj-wall">
            <span class="tj-frame"><span></span></span>
            <span class="tj-window"><span></span></span>
            <span class="tj-clock"><span class="tj-clock-hand"></span></span>
        </div>
        <div class="tj-floor"><span class="tj-rug"></span></div>
        <span class="tj-hole" aria-hidden="true"></span>
        <span class="tj-doghouse" aria-hidden="true"><span>SPIKE</span></span>

        {{-- الشخصيات: صورة لو موجودة، وإلا الرسمة البديلة --}}
        <div class="tj-actor tj-tom" x-ref="tom">
            <span class="tj-shadow"></span>
            <div class="tj-body">
                @if ($sprites['tom'])
                    <img class="tj-sprite tj-pose-normal" src="{{ $sprites['tom'] }}" alt="" draggable="false" />
                    <img class="tj-sprite tj-pose-flat" src="{{ $sprites['tom-flat'] ?? $sprites['tom'] }}" alt="" draggable="false" />
                @else
                    @include('pages.home.game-art', ['actor' => 'tom'])
                @endif
            </div>
            <span class="tj-stars" aria-hidden="true"><i>★</i><i>★</i><i>★</i></span>
        </div>

        <div class="tj-actor tj-jerry" x-ref="jerry">
            <span class="tj-shadow"></span>
            <div class="tj-body">
                @if ($sprites['jerry'])
                    <img class="tj-sprite" src="{{ $sprites['jerry'] }}" alt="" draggable="false" />
                @else
                    @include('pages.home.game-art', ['actor' => 'jerry'])
                @endif
            </div>
            <span class="tj-held" aria-hidden="true">🍳</span>
        </div>

        <div class="tj-actor tj-spike" x-ref="spike">
            <span class="tj-shadow"></span>
            <div class="tj-body">
                @if ($sprites['spike'])
                    <img class="tj-sprite" src="{{ $sprites['spike'] }}" alt="" draggable="false" />
                @else
                    @include('pages.home.game-art', ['actor' => 'spike'])
                @endif
            </div>
        </div>

        <span class="tj-cursor" x-ref="cursor" x-show="playing && hasPointer" x-cloak></span>

        <template x-if="banner">
            <div class="tj-banner" x-text="banner"></div>
        </template>

        {{-- زرار رمي الموز على الموبايل --}}
        <button class="tj-throw" type="button" x-show="playing && bananas" x-cloak x-on:pointerdown.stop x-on:click.stop="dropPeel()">
            🍌 ارمي موزة
        </button>

        <template x-if="! playing && ! over">
            <div class="tj-invite">
                <b>إنتي الفار 🐭 — والقط وراكي!</b>
                <span>🧀 جبنة · 🍳 مقلاة تفرطحه · 🍌 موز يزحلقه · 🪤 مصيدة · 🕳️ الجحر يخبطه في الحيطة · 🦴 عضمة تجيب الكلب</span>
            </div>
        </template>

        <template x-if="paused">
            <div class="tj-overlay"><div class="tj-card"><big>⏸</big><small>متوقفة — دوسي ▶ تكمّلي</small></div></div>
        </template>

        <template x-if="over">
            <div class="tj-overlay" x-on:pointerdown.stop>
                <div class="tj-card">
                    <small>خلصت المطاردة!</small>
                    <big x-text="score"></big>
                    <small x-text="newBest ? 'رقم قياسي جديد! 🏆' : `أحسن نتيجة: ${best}`"></small>
                    <small x-text="`🍳 ${tally.pan} · 🍌 ${tally.slip} · 🪤 ${tally.trap} · 🕳️ ${tally.wall} · 🦴 ${tally.spike}`"></small>
                    <button class="tj-btn tj-btn--go mt-2" type="button" x-on:click="startGame()">مطاردة كمان</button>
                    <button class="text-[12px] text-white/60 hover:text-white" type="button" x-on:click="over = false">قفل</button>
                </div>
            </div>
        </template>
    </div>
</section>

@include('pages.home.game-script')
