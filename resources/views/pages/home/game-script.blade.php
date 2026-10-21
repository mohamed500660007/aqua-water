@push('scripts')
    <script>
        /**
         * أصوات اللعبة: ملف mp3 من public/sounds/game لو موجود، وإلا صوت كرتوني بيتعمل في المتصفح.
         * المتصفح بيسمح بالصوت بعد أول ضغطة بس، والعرض التلقائي (الديمو) صامت.
         */
        function toonAudio(files) {
            let ctx = null;
            let theme = null;

            const audio = () => {
                if (!ctx) {
                    const AudioContext = window.AudioContext || window.webkitAudioContext;
                    if (!AudioContext) return null;
                    ctx = new AudioContext();
                }
                if (ctx.state === 'suspended') ctx.resume();
                return ctx;
            };

            const tone = (type, from, to, duration, volume = .2, delay = 0, curve = 'exp') => {
                const c = audio();
                if (!c) return;
                const t = c.currentTime + delay;
                const osc = c.createOscillator();
                const gain = c.createGain();
                osc.type = type;
                osc.frequency.setValueAtTime(from, t);
                curve === 'exp'
                    ? osc.frequency.exponentialRampToValueAtTime(to, t + duration)
                    : osc.frequency.linearRampToValueAtTime(to, t + duration);
                gain.gain.setValueAtTime(.0001, t);
                gain.gain.exponentialRampToValueAtTime(volume, t + .012);
                gain.gain.exponentialRampToValueAtTime(.0001, t + duration);
                osc.connect(gain).connect(c.destination);
                osc.start(t);
                osc.stop(t + duration + .03);
            };

            const noise = (duration, volume = .3, from = 4000, to = 300, delay = 0, type = 'lowpass') => {
                const c = audio();
                if (!c) return;
                const t = c.currentTime + delay;
                const buffer = c.createBuffer(1, Math.ceil(c.sampleRate * duration), c.sampleRate);
                const data = buffer.getChannelData(0);
                for (let i = 0; i < data.length; i++) data[i] = Math.random() * 2 - 1;
                const src = c.createBufferSource();
                const filter = c.createBiquadFilter();
                const gain = c.createGain();
                src.buffer = buffer;
                filter.type = type;
                filter.frequency.setValueAtTime(from, t);
                filter.frequency.exponentialRampToValueAtTime(to, t + duration);
                gain.gain.setValueAtTime(volume, t);
                gain.gain.exponentialRampToValueAtTime(.0001, t + duration);
                src.connect(filter).connect(gain).connect(c.destination);
                src.start(t);
            };

            const vibrato = (type, freq, duration, volume, rate, depth, delay = 0, endFreq = freq) => {
                const c = audio();
                if (!c) return;
                const t = c.currentTime + delay;
                const osc = c.createOscillator();
                const lfo = c.createOscillator();
                const lfoGain = c.createGain();
                const gain = c.createGain();
                osc.type = type;
                osc.frequency.setValueAtTime(freq, t);
                osc.frequency.linearRampToValueAtTime(endFreq, t + duration);
                lfo.frequency.value = rate;
                lfoGain.gain.value = depth;
                lfo.connect(lfoGain).connect(osc.frequency);
                gain.gain.setValueAtTime(.0001, t);
                gain.gain.exponentialRampToValueAtTime(volume, t + .03);
                gain.gain.setValueAtTime(volume, t + duration * .7);
                gain.gain.exponentialRampToValueAtTime(.0001, t + duration);
                osc.connect(gain).connect(c.destination);
                osc.start(t); lfo.start(t);
                osc.stop(t + duration + .05); lfo.stop(t + duration + .05);
            };

            // الأصوات البديلة — كرتونية ومبالغ فيها عن قصد
            const synth = {
                cheese: () => { tone('square', 880, 1320, .07, .08); tone('square', 1320, 1760, .08, .08, .07); },
                item: () => [988, 1319, 1568, 2093].forEach((f, i) => tone('triangle', f, f, .1, .12, i * .06)),
                pan: () => {
                    // «طااااخ» معدني: ضربة + رنين طويل
                    noise(.08, .5, 6000, 1500);
                    [523, 785, 1318, 2093].forEach((f, i) => tone('sine', f, f * .98, 1.1 - i * .15, .14 - i * .02));
                },
                slip: () => {
                    tone('sine', 1600, 300, .55, .16, 0, 'lin');
                    noise(.25, .35, 900, 120, .55);
                    tone('sine', 140, 50, .3, .45, .55);
                },
                snap: () => { noise(.05, .7, 8000, 2000, 0, 'highpass'); tone('square', 1500, 900, .05, .12); },
                scream: () => vibrato('sawtooth', 620, .85, .14, 13, 60, .05, 1180),
                crash: () => {
                    noise(.5, .6, 1200, 80);
                    tone('sine', 120, 40, .45, .6);
                    [0, .18, .32].forEach(d => tone('triangle', 2200, 1800, .08, .06, .3 + d));
                },
                bark: () => [0, .22].forEach(d => { noise(.12, .55, 900, 300, d, 'bandpass'); tone('sawtooth', 300, 140, .12, .18, d); }),
                caught: () => [392, 370, 349, 294].forEach((f, i) => vibrato('triangle', f, i === 3 ? .9 : .32, .2, 6, i === 3 ? 10 : 4, i * .32, i === 3 ? f * .92 : f)),
                levelup: () => [523, 659, 784, 1047, 1319].forEach((f, i) => tone('square', f, f, .12, .08, i * .08)),
                start: () => { tone('sine', 1200, 2000, .25, .14); tone('sine', 2000, 1500, .2, .12, .26); },
                gameover: () => [784, 659, 523, 392].forEach((f, i) => tone('triangle', f, f, .28, .16, i * .2)),
            };

            return {
                muted: (() => { try { return localStorage.getItem('tj-muted') === '1'; } catch (e) { return false; } })(),

                play(name) {
                    if (this.muted) return;
                    if (files[name]) {
                        const clip = new Audio(files[name]);
                        clip.volume = .8;
                        clip.play().catch(() => synth[name]?.());
                        return;
                    }
                    synth[name]?.();
                },

                startTheme() {
                    if (this.muted || !files.theme) return;
                    theme ??= Object.assign(new Audio(files.theme), { loop: true, volume: .35 });
                    theme.play().catch(() => {});
                },

                stopTheme() { theme?.pause(); },

                unlock() { if (!this.muted) audio(); },
            };
        }

        /**
         * «مطاردة البيت»: محرك اللعبة. حالة الإطارات (المواقع والسرعات) في متغيرات عادية خارج Alpine
         * عشان التحديث 60 مرة في الثانية مايعديش على الـ reactivity؛ Alpine للأرقام والأزرار بس.
         */
        function toonChase(soundFiles) {
            const sfx = toonAudio(soundFiles);
            const CFG = {
                jerry: 255, tom: 170, tomPerLevel: 16, spike: 330,
                catchDist: 30, pickDist: 28, levelEvery: 150,
                stunMs: 2600, slipMs: 1150, hurtMs: 1700, gloatMs: 950, spikeMs: 4200, holeMs: 2600,
            };

            let g = null; // حالة اللعبة الحية

            return {
                playing: false,
                paused: false,
                over: false,
                score: 0,
                level: 1,
                lives: 3,
                best: 0,
                newBest: false,
                pan: false,
                bananas: 0,
                banner: null,
                muted: sfx.muted,
                hasPointer: false,
                tally: { pan: 0, slip: 0, trap: 0, wall: 0, spike: 0 },

                init() {
                    try { this.best = Number(localStorage.getItem('tj-best')) || 0; } catch (e) {}

                    const stage = this.$refs.stage;
                    g = {
                        stage,
                        reduced: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
                        keys: new Set(),
                        pointer: null,
                        objects: [],
                        visible: true,
                    };
                    this.measure();
                    g.tom = this.makeActor(this.$refs.tom, 132, 99);
                    g.jerry = this.makeActor(this.$refs.jerry, 70, 51);
                    g.spike = this.makeActor(this.$refs.spike, 138, 101);
                    this.resetWorld();

                    g.resize = new ResizeObserver(() => this.measure());
                    g.resize.observe(stage);
                    g.io = new IntersectionObserver(([entry]) => { g.visible = entry.isIntersecting; this.run(); });
                    g.io.observe(stage);
                    g.onVisibility = () => { if (document.hidden && this.playing && !this.paused) this.togglePause(); this.run(); };
                    document.addEventListener('visibilitychange', g.onVisibility);

                    g.onKeyDown = (e) => this.keyDown(e);
                    g.onKeyUp = (e) => g.keys.delete(e.key.toLowerCase());
                    window.addEventListener('keydown', g.onKeyDown);
                    window.addEventListener('keyup', g.onKeyUp);

                    this.run();
                },

                destroy() {
                    cancelAnimationFrame(g.frame);
                    g.resize.disconnect();
                    g.io.disconnect();
                    document.removeEventListener('visibilitychange', g.onVisibility);
                    window.removeEventListener('keydown', g.onKeyDown);
                    window.removeEventListener('keyup', g.onKeyUp);
                    sfx.stopTheme();
                },

                // ========== العالم ==========

                makeActor(el, w, h) {
                    return { el, w, h, x: 0, y: 0, z: 0, vx: 0, vy: 0, facing: 1, state: 'normal', until: 0, since: 0 };
                },

                measure() {
                    g.w = g.stage.clientWidth;
                    g.h = g.stage.clientHeight;
                    g.top = g.h * .48 + 16;
                    g.bottom = g.h - 10;
                    g.hole = { x: g.w - 49, y: g.top - 4 };
                    g.kennel = { x: 53, y: g.top + 4 };
                },

                floor(t) { return g.top + (g.bottom - g.top) * t; },

                depth(y) { return .74 + .34 * Math.min(1, Math.max(0, (y - g.top) / (g.bottom - g.top))); },

                randomSpot(margin = 70) {
                    return { x: margin + Math.random() * (g.w - margin * 2), y: this.floor(.08 + Math.random() * .84) };
                },

                resetWorld() {
                    g.objects.forEach(o => o.el.remove());
                    g.objects = [];
                    Object.assign(g.jerry, { x: g.w * .62, y: this.floor(.55), state: 'normal', until: 0, safeUntil: 0, z: 0 });
                    Object.assign(g.tom, { x: g.w * .22, y: this.floor(.45), state: 'normal', until: 0, z: 0 });
                    Object.assign(g.spike, { x: g.kennel.x, y: g.kennel.y, state: 'home', until: 0, z: 0 });
                    g.nextItemAt = performance.now() + 2500;
                    g.trapRespawnAt = 0;
                    g.holeReadyAt = 0;
                    g.stage.querySelector('.tj-hole').classList.remove('is-closed');
                    g.aiTarget = null;
                    this.ensureCheese();
                    this.ensureTraps();
                    this.render();
                },

                spawn(kind, emoji, at, extra = {}) {
                    const spot = at ?? this.freeSpot();
                    const el = document.createElement('span');
                    el.className = `tj-obj tj-obj--${kind}`;
                    el.textContent = emoji;
                    el.style.left = `${spot.x}px`;
                    el.style.top = `${spot.y}px`;
                    el.style.zIndex = String(Math.round(spot.y));
                    g.stage.appendChild(el);
                    const obj = { kind, el, x: spot.x, y: spot.y, born: performance.now(), ...extra };
                    g.objects.push(obj);
                    return obj;
                },

                remove(obj) {
                    obj.el.remove();
                    g.objects = g.objects.filter(o => o !== obj);
                },

                // مكان فاضي بعيد عن القط والفار شوية
                freeSpot() {
                    for (let i = 0; i < 12; i++) {
                        const spot = this.randomSpot();
                        const clear = [g.tom, g.jerry, ...g.objects].every(o => Math.hypot(o.x - spot.x, o.y - spot.y) > 70);
                        if (clear) return spot;
                    }
                    return this.randomSpot();
                },

                ensureCheese() {
                    while (g.objects.filter(o => o.kind === 'cheese' || o.kind === 'gold').length < 2) {
                        Math.random() < .12
                            ? this.spawn('gold', '🧀', null, { points: 50 })
                            : this.spawn('cheese', '🧀', null, { points: 10 });
                    }
                },

                ensureTraps() {
                    while (g.objects.filter(o => o.kind === 'trap').length < 2) this.spawn('trap', '🪤');
                },

                // ========== الحلقة ==========

                run() {
                    cancelAnimationFrame(g.frame);
                    const animate = g.visible && !document.hidden && !this.paused && (this.playing || !g.reduced);
                    if (!animate) return;

                    let last = performance.now();
                    const step = (now) => {
                        const dt = Math.min(.05, (now - last) / 1000);
                        last = now;
                        this.update(dt, now);
                        this.render();
                        g.frame = requestAnimationFrame(step);
                    };
                    g.frame = requestAnimationFrame(step);
                },

                moveToward(actor, target, speed, dt) {
                    const dx = target.x - actor.x;
                    const dy = target.y - actor.y;
                    const dist = Math.hypot(dx, dy);
                    if (dist < 2) { actor.vx = actor.vy = 0; return 0; }
                    const k = Math.min(dist, speed * dt) / dist;
                    actor.x += dx * k;
                    actor.y += dy * k;
                    actor.vx = (dx * k) / dt;
                    actor.vy = (dy * k) / dt;
                    if (Math.abs(dx) > 1.5) actor.facing = dx > 0 ? 1 : -1;
                    return dist;
                },

                clamp(actor) {
                    actor.x = Math.min(g.w - 24, Math.max(24, actor.x));
                    actor.y = Math.min(g.bottom, Math.max(g.top, actor.y));
                },

                setState(actor, state, ms, now) {
                    actor.state = state;
                    actor.since = now;
                    actor.until = now + ms;
                },

                update(dt, now) {
                    this.updateJerry(dt, now);
                    this.updateTom(dt, now);
                    this.updateSpike(dt, now);
                    this.updateObjects(now);
                },

                // ---------- الفار ----------

                updateJerry(dt, now) {
                    const jerry = g.jerry;

                    if (jerry.state === 'hidden') {
                        const wantsOut = this.playing && (this.keyDir().x < 0 || (g.pointer && g.pointer.x < g.w - 110));
                        if (now > jerry.until || wantsOut) {
                            jerry.state = 'normal';
                            // الجحر يقفل شوية بعد الخروج — مايتكررش مقلب الحيطة ورا بعضه
                            g.holeReadyAt = now + 6000;
                            g.stage.querySelector('.tj-hole').classList.add('is-closed');
                            jerry.x = g.hole.x - 30;
                            jerry.y = g.top + 10;
                            jerry.facing = -1;
                        }
                        jerry.vx = jerry.vy = 0;
                        return;
                    }

                    const target = this.playing ? this.playerTarget(dt) : this.jerryBrain(now);
                    if (target) this.moveToward(jerry, target, CFG.jerry, dt);
                    else jerry.vx = jerry.vy = 0;
                    this.clamp(jerry);

                    // دخل الجحر: يستخبى والقط ممكن يخبط في الحيطة
                    if (now > (g.holeReadyAt ?? 0)) g.stage.querySelector('.tj-hole').classList.remove('is-closed');
                    if (jerry.x > g.w - 78 && jerry.y < g.top + 30 && now > (g.holeReadyAt ?? 0)) {
                        this.setState(jerry, 'hidden', CFG.holeMs, now);
                        this.fx(g.hole.x, g.hole.y - 40, 'هوب!', 'small');
                    }

                    // يلم اللي جنبه
                    for (const obj of [...g.objects]) {
                        if (Math.hypot(obj.x - jerry.x, obj.y - jerry.y) > CFG.pickDist) continue;
                        if (obj.kind === 'cheese' || obj.kind === 'gold') this.eatCheese(obj);
                        else if (obj.kind === 'item') this.pickItem(obj, now);
                    }
                },

                keyDir() {
                    const k = g.keys;
                    return {
                        x: (k.has('arrowright') || k.has('d') ? 1 : 0) - (k.has('arrowleft') || k.has('a') ? 1 : 0),
                        y: (k.has('arrowdown') || k.has('s') ? 1 : 0) - (k.has('arrowup') || k.has('w') ? 1 : 0),
                    };
                },

                playerTarget() {
                    const dir = this.keyDir();
                    if (dir.x || dir.y) {
                        return { x: g.jerry.x + dir.x * 60, y: g.jerry.y + dir.y * 40 };
                    }
                    return g.pointer;
                },

                // الديمو: الفار بيلعب لوحده — يهرب، يرمي موز، يجري على المقالب والجبنة
                jerryBrain(now) {
                    const jerry = g.jerry, tom = g.tom;
                    const gap = Math.hypot(tom.x - jerry.x, tom.y - jerry.y);
                    const tomActive = tom.state === 'normal';

                    if (tomActive && gap < 130 && g.demoBananas > 0 && now - (g.lastPeel ?? 0) > 1500) {
                        g.demoBananas--;
                        g.lastPeel = now;
                        this.spawn('peel', '🍌', { x: jerry.x - jerry.facing * 18, y: jerry.y });
                    }

                    if (tomActive && gap < 95 && !g.demoPan) {
                        // يهرب للجحر لو قريب منه، وإلا عكس القط
                        if (jerry.x > g.w * .75) return { x: g.hole.x, y: g.top + 4 };
                        return { x: jerry.x + (jerry.x - tom.x) * 1.4, y: jerry.y + (jerry.y - tom.y) * 1.4 };
                    }

                    const wanted = g.objects.filter(o => ['item', 'cheese', 'gold'].includes(o.kind));
                    if (!wanted.length) return g.aiTarget ??= this.randomSpot();
                    wanted.sort((a, b) => (a.kind === 'item' ? -1 : 0) - (b.kind === 'item' ? -1 : 0)
                        || Math.hypot(a.x - jerry.x, a.y - jerry.y) - Math.hypot(b.x - jerry.x, b.y - jerry.y));
                    return wanted[0];
                },

                eatCheese(obj) {
                    this.remove(obj);
                    this.addScore(obj.points, obj.x, obj.y - 34);
                    this.sound('cheese');
                    this.ensureCheese();
                },

                pickItem(obj, now) {
                    this.remove(obj);
                    this.sound('item');

                    if (obj.type === 'pan') {
                        this.setPan(true);
                        this.fx(obj.x, obj.y - 40, 'مقلاة! 🍳', 'small');
                    } else if (obj.type === 'banana') {
                        this.playing ? (this.bananas = Math.min(3, this.bananas + 2)) : (g.demoBananas = 2);
                        this.fx(obj.x, obj.y - 40, this.playing ? 'موز! دوسي مسافة 🍌' : 'موز! 🍌', 'small');
                    } else {
                        this.fx(obj.x, obj.y - 40, 'يا سبايك!! 🦴', 'small');
                        this.callSpike(now);
                    }
                },

                setPan(value) {
                    if (this.playing) this.pan = value;
                    else g.demoPan = value;
                    g.jerry.el.classList.toggle('has-pan', value);
                },

                hasPan() { return this.playing ? this.pan : !!g.demoPan; },

                dropPeel() {
                    if (!this.playing || this.paused || this.bananas < 1 || g.jerry.state === 'hidden') return;
                    this.bananas--;
                    this.spawn('peel', '🍌', { x: g.jerry.x - g.jerry.facing * 20, y: g.jerry.y });
                    this.fx(g.jerry.x, g.jerry.y - 50, 'خد دي!', 'small');
                },

                // ---------- القط ----------

                updateTom(dt, now) {
                    const tom = g.tom, jerry = g.jerry;

                    if (tom.state !== 'normal' && tom.state !== 'scared' && now > tom.until) {
                        tom.state = 'normal';
                        tom.z = 0;
                    }

                    if (tom.state === 'slip') {
                        // يطير لقدام ويلف ويقع على وشه
                        const p = Math.min(1, (now - tom.since) / CFG.slipMs);
                        tom.z = Math.sin(p * Math.PI) * 90;
                        tom.x += tom.facing * 240 * dt * (1 - p * .5);
                        this.clamp(tom);
                        if (p > .96 && !tom.landed) {
                            tom.landed = true;
                            this.dust(tom.x, tom.y);
                            this.sound('crash');
                            this.shake();
                        }
                        return;
                    }

                    if (tom.state === 'launched') {
                        // سبايك قذفه برة الشاشة ورجع يقع
                        const p = Math.min(1, (now - tom.since) / CFG.slipMs);
                        tom.z = Math.sin(p * Math.PI) * 150;
                        tom.x += tom.facing * 320 * dt;
                        this.clamp(tom);
                        return;
                    }

                    if (['stunned', 'hurt', 'gloat'].includes(tom.state)) {
                        tom.vx = tom.vy = 0;
                        return;
                    }

                    if (tom.state === 'scared') {
                        const away = { x: tom.x + (tom.x - g.spike.x) * 2, y: tom.y + (tom.y - g.spike.y) * 2 };
                        this.moveToward(tom, away, CFG.tom + 90 + this.level * CFG.tomPerLevel, dt);
                        this.clamp(tom);
                        return;
                    }

                    tom.landed = false;
                    const target = jerry.state === 'hidden' ? { x: g.hole.x - 10, y: g.top } : jerry;
                    this.moveToward(tom, target, CFG.tom + (this.level - 1) * CFG.tomPerLevel, dt);
                    this.clamp(tom);

                    // اتحمّس ودخل ورا الفار في الجحر = خبط في الحيطة
                    if (jerry.state === 'hidden' && tom.x > g.w - 96 && tom.y < g.top + 42) {
                        this.wallSplat(now);
                        return;
                    }

                    for (const obj of [...g.objects]) {
                        const near = Math.hypot(obj.x - tom.x, obj.y - tom.y) < 24;
                        if (near && obj.kind === 'peel') return this.slip(obj, now);
                        if (near && obj.kind === 'trap') return this.trap(obj, now);
                    }

                    const gap = Math.hypot(tom.x - jerry.x, tom.y - jerry.y);
                    if (gap < CFG.catchDist && jerry.state !== 'hidden' && now > (jerry.safeUntil ?? 0)) {
                        this.hasPan() ? this.panBonk(now) : this.caught(now);
                    }
                },

                panBonk(now) {
                    const tom = g.tom;
                    this.setPan(false);
                    this.setState(tom, 'stunned', CFG.stunMs, now);
                    this.sound('pan');
                    this.fx(tom.x, tom.y - 70, 'طااااخ!', 'word');
                    this.boom(tom.x, tom.y - 40);
                    this.shake();
                    this.reward('pan', 40, tom);
                },

                slip(peel, now) {
                    const tom = g.tom;
                    this.remove(peel);
                    tom.landed = false;
                    this.setState(tom, 'slip', CFG.slipMs, now);
                    this.sound('slip');
                    this.fx(tom.x, tom.y - 80, 'وووووووه!', 'word');
                    this.reward('slip', 30, tom);
                },

                trap(trap, now) {
                    const tom = g.tom;
                    this.remove(trap);
                    g.trapRespawnAt = now + 5000;
                    this.setState(tom, 'hurt', CFG.hurtMs, now);
                    this.sound('snap');
                    setTimeout(() => this.sound('scream'), 90);
                    this.fx(tom.x, tom.y - 80, 'آآآآآآه!', 'word');
                    this.reward('trap', 35, tom);
                },

                wallSplat(now) {
                    const tom = g.tom;
                    tom.x = g.w - 110;
                    tom.facing = 1;
                    this.setState(tom, 'stunned', CFG.stunMs, now);
                    this.sound('crash');
                    this.fx(g.hole.x - 20, g.hole.y - 30, 'بووووم!', 'word');
                    this.boom(g.w - 70, g.top - 20);
                    g.stage.querySelector('.tj-hole').classList.remove('is-hit');
                    void g.stage.offsetWidth;
                    g.stage.querySelector('.tj-hole').classList.add('is-hit');
                    this.shake();
                    this.reward('wall', 50, tom);
                },

                caught(now) {
                    const tom = g.tom, jerry = g.jerry;
                    this.setState(tom, 'gloat', CFG.gloatMs, now);
                    this.fx(tom.x, tom.y - 90, 'مسكتك!', 'word');
                    this.dust(jerry.x, jerry.y);

                    // الفار يفلت ويرجع جحره، ومحمي ثانيتين
                    jerry.safeUntil = now + 2800;
                    this.setState(jerry, 'hidden', 1200, now);

                    if (!this.playing) return;

                    this.sound('caught');
                    this.lives--;
                    if (this.lives <= 0) setTimeout(() => this.endGame(false), 700);
                },

                // ---------- الكلب ----------

                callSpike(now) {
                    const spike = g.spike;
                    spike.x = g.kennel.x;
                    spike.y = g.kennel.y + 6;
                    this.setState(spike, 'chase', CFG.spikeMs, now);
                    this.sound('bark');
                    if (['normal', 'gloat'].includes(g.tom.state)) {
                        g.tom.state = 'scared';
                        this.fx(g.tom.x, g.tom.y - 90, '!!!', 'word');
                    }
                },

                updateSpike(dt, now) {
                    const spike = g.spike, tom = g.tom;

                    if (spike.state === 'home') return;

                    if (spike.state === 'chase') {
                        if (tom.state === 'normal') tom.state = 'scared';
                        this.moveToward(spike, tom, CFG.spike, dt);
                        this.clamp(spike);

                        const reached = Math.hypot(spike.x - tom.x, spike.y - tom.y) < 46 && tom.state !== 'launched';
                        if (reached || now > spike.until) {
                            if (reached) {
                                tom.facing = spike.facing;
                                this.setState(tom, 'launched', CFG.slipMs, now);
                                this.sound('bark');
                                setTimeout(() => this.sound('scream'), 150);
                                this.fx(spike.x, spike.y - 90, 'هوووووف!', 'word');
                                this.reward('spike', 45, tom);
                            } else if (tom.state === 'scared') {
                                tom.state = 'normal';
                            }
                            spike.state = 'return';
                        }
                        return;
                    }

                    // بيرجع بيته
                    if (this.moveToward(spike, g.kennel, CFG.spike * .7, dt) < 6) spike.state = 'home';
                    if (tom.state === 'scared') tom.state = 'normal';
                },

                // ---------- الحاجات ----------

                updateObjects(now) {
                    if (now > g.nextItemAt && !g.objects.some(o => o.kind === 'item')) {
                        const roll = Math.random();
                        const [type, emoji] = roll < .38 ? ['pan', '🍳'] : roll < .78 ? ['banana', '🍌'] : ['bone', '🦴'];
                        this.spawn('item', emoji, null, { type, expires: now + 8000 });
                        g.nextItemAt = now + 7000 + Math.random() * 4000;
                    }

                    for (const obj of [...g.objects]) {
                        if (obj.kind === 'item' && now > obj.expires) this.remove(obj);
                        else if (obj.kind === 'item') obj.el.classList.toggle('is-expiring', obj.expires - now < 2000);
                        if (obj.kind === 'peel' && now - obj.born > 14000) this.remove(obj);
                    }

                    if (g.trapRespawnAt && now > g.trapRespawnAt) {
                        g.trapRespawnAt = 0;
                        this.ensureTraps();
                    }
                },

                // ========== النقط والمؤثرات ==========

                reward(kind, points, at) {
                    if (!this.playing) return;
                    this.tally[kind]++;
                    this.addScore(points, at.x, at.y - 120);
                },

                addScore(points, x, y) {
                    if (!this.playing) return;
                    this.score += points;
                    this.fx(x, y, `+${points}`, 'small');

                    const level = 1 + Math.floor(this.score / CFG.levelEvery);
                    if (level > this.level) {
                        this.level = level;
                        this.showBanner(`المستوى ${level} — القط اتعصب! 😾`);
                        this.sound('levelup');
                    }
                },

                showBanner(text) {
                    this.banner = null;
                    this.$nextTick(() => {
                        this.banner = text;
                        clearTimeout(g.bannerTimer);
                        g.bannerTimer = setTimeout(() => { this.banner = null; }, 1600);
                    });
                },

                fx(x, y, text, kind) {
                    const el = document.createElement('span');
                    el.className = `tj-fx tj-fx--${kind}`;
                    el.textContent = text;
                    el.style.left = `${Math.min(g.w - 50, Math.max(50, x))}px`;
                    el.style.top = `${Math.max(24, y)}px`;
                    g.stage.appendChild(el);
                    setTimeout(() => el.remove(), 1050);
                    return el;
                },

                boom(x, y) { this.fx(x, y, '', 'boom'); },

                dust(x, y) {
                    for (let i = 0; i < 5; i++) {
                        const puff = this.fx(x + (i - 2) * 10, y - 6, '', 'dust');
                        puff.style.setProperty('--dx', `${(i - 2) * 12}px`);
                    }
                },

                shake() {
                    g.stage.classList.remove('is-shaking');
                    void g.stage.offsetWidth;
                    g.stage.classList.add('is-shaking');
                },

                sound(name) { if (this.playing) sfx.play(name); },

                render() {
                    for (const actor of [g.tom, g.jerry, g.spike]) {
                        const scale = this.depth(actor.y);
                        actor.el.style.transform = `translate(${actor.x - actor.w / 2}px, ${actor.y - actor.h - actor.z}px) scale(${scale})`;
                        actor.el.style.zIndex = String(Math.round(actor.y + actor.z));
                        const cls = actor.el.classList;
                        cls.toggle('is-running', Math.hypot(actor.vx, actor.vy) > 25 && ['normal', 'scared', 'chase', 'return'].includes(actor.state));
                        cls.toggle('is-left', actor.facing < 0);
                        cls.toggle('is-stunned', actor.state === 'stunned');
                        cls.toggle('is-slip', actor.state === 'slip' || actor.state === 'launched');
                        cls.toggle('is-hurt', actor.state === 'hurt');
                        cls.toggle('is-scared', actor.state === 'scared');
                        cls.toggle('is-gloat', actor.state === 'gloat');
                        cls.toggle('is-hidden', actor.state === 'hidden');
                        cls.toggle('is-home', actor.state === 'home');
                    }
                    g.jerry.el.classList.toggle('is-safe', performance.now() < (g.jerry.safeUntil ?? 0) && g.jerry.state !== 'hidden');

                    if (g.pointer && this.$refs.cursor) {
                        this.$refs.cursor.style.left = `${g.pointer.x}px`;
                        this.$refs.cursor.style.top = `${g.pointer.y}px`;
                    }
                },

                // ========== التحكم ==========

                localPoint(event) {
                    const rect = g.stage.getBoundingClientRect();
                    return { x: event.clientX - rect.left, y: Math.min(g.bottom, Math.max(g.top, event.clientY - rect.top)) };
                },

                pointerDown(event) {
                    sfx.unlock();
                    if (!this.playing) return;
                    g.pointer = this.localPoint(event);
                    this.hasPointer = true;
                    g.stage.focus({ preventScroll: true });
                },

                pointerMove(event) {
                    if (!this.playing || (event.pointerType !== 'mouse' && !event.buttons)) return;
                    g.pointer = this.localPoint(event);
                    this.hasPointer = true;
                },

                pointerLeave() {
                    if (g) g.pointer = null;
                    this.hasPointer = false;
                },

                keyDown(event) {
                    if (!this.playing) return;
                    const key = event.key.toLowerCase();
                    const gameKeys = ['arrowup', 'arrowdown', 'arrowleft', 'arrowright', 'w', 'a', 's', 'd', ' ', 'p'];
                    if (!gameKeys.includes(key)) return;
                    // الأسهم والمسافة للعبة بس وهي شغالة — مايحركوش الصفحة
                    if (event.target.closest('input, textarea, select')) return;
                    event.preventDefault();

                    if (key === ' ') return this.dropPeel();
                    if (key === 'p') return this.togglePause();
                    g.keys.add(key);
                    g.pointer = null;
                    this.hasPointer = false;
                },

                startGame() {
                    sfx.unlock();
                    Object.assign(this, {
                        over: false, paused: false, newBest: false, score: 0, level: 1, lives: 3, pan: false, bananas: 0,
                        tally: { pan: 0, slip: 0, trap: 0, wall: 0, spike: 0 },
                    });
                    g.demoPan = false;
                    g.demoBananas = 0;
                    g.jerry.el.classList.remove('has-pan');
                    this.resetWorld();
                    this.playing = true;
                    this.$root.classList.add('is-playing');
                    g.stage.focus({ preventScroll: true });
                    sfx.play('start');
                    sfx.startTheme();
                    this.showBanner('يلا! اهربي من القط 🐭');
                    this.run();
                },

                endGame(cancelled) {
                    if (!this.playing) return;
                    this.playing = false;
                    this.paused = false;
                    this.hasPointer = false;
                    g.pointer = null;
                    g.keys.clear();
                    this.$root.classList.remove('is-playing');
                    sfx.stopTheme();
                    this.setPan(false);

                    if (!cancelled) {
                        this.newBest = this.score > this.best;
                        if (this.newBest) {
                            this.best = this.score;
                            try { localStorage.setItem('tj-best', String(this.best)); } catch (e) {}
                        }
                        this.over = true;
                        sfx.play('gameover');
                    }
                    this.run();
                },

                togglePause() {
                    if (!this.playing) return;
                    this.paused = !this.paused;
                    this.paused ? sfx.stopTheme() : sfx.startTheme();
                    this.run();
                },

                toggleSound() {
                    sfx.muted = this.muted = !this.muted;
                    try { localStorage.setItem('tj-muted', this.muted ? '1' : '0'); } catch (e) {}
                    if (this.muted) sfx.stopTheme();
                    else { sfx.unlock(); sfx.play('item'); if (this.playing && !this.paused) sfx.startTheme(); }
                },
            };
        }
    </script>
@endpush
