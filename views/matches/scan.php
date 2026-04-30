<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php /** @var array $devices */ $title = 'Scan apparaat'; ?>

<div class="max-w-md mx-auto">
    <h1 class="text-2xl font-bold text-navy dark:text-slate-100 mb-1">Scan een apparaat</h1>
    <p class="text-slate-500 dark:text-slate-400 text-sm mb-4">
        Richt je camera op de QR-code op tafel. Heb je geen camera? Vul de 6-letter code onderaan in.
    </p>

    <div class="rounded-2xl bg-black overflow-hidden shadow-card border border-slate-200 dark:border-slate-800 relative aspect-square">
        <video id="scanVideo" autoplay playsinline muted class="w-full h-full object-cover"></video>
        <canvas id="scanCanvas" class="hidden"></canvas>
        <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
            <div class="w-3/4 aspect-square border-4 border-brand/80 rounded-2xl"></div>
        </div>
        <div id="scanStatus"
             class="absolute bottom-2 left-2 right-2 text-center text-xs font-medium text-white bg-black/50 rounded-md px-2 py-1.5">
            Camera starten…
        </div>
    </div>

    <div class="mt-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card">
        <p class="text-sm font-bold text-navy dark:text-slate-100 mb-2">Of tik de code in</p>
        <form id="manualForm" class="flex gap-2">
            <input id="manualCode" type="text" inputmode="text" autocomplete="off" maxlength="6"
                   placeholder="bv. K7FXMZ"
                   class="flex-1 rounded-lg bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 px-3 py-2.5 text-base font-mono uppercase tracking-widest text-center focus:outline-none focus:ring-2 focus:ring-brand">
            <button class="px-4 py-2.5 rounded-lg bg-brand text-white font-semibold hover:bg-brand-dark">
                Open
            </button>
        </form>
    </div>

    <p class="text-xs text-slate-500 dark:text-slate-400 text-center mt-4">
        De camera draait alleen lokaal op dit toestel — er wordt geen video naar de server gestuurd.
    </p>

    <?php if (!empty($devices)): ?>
        <div class="mt-6">
            <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-2">Tafels nu</h2>
            <ul class="space-y-2">
                <?php foreach ($devices as $d):
                    $busy = (int) $d['active_count'] > 0;
                ?>
                    <li>
                        <a href="<?= e(url('/d/' . $d['code'])) ?>"
                           class="flex items-center gap-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-2.5 hover:border-brand shadow-card">
                            <span class="w-2 h-2 rounded-full <?= $busy ? 'bg-amber-500' : 'bg-brand' ?> shrink-0"></span>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-navy dark:text-slate-100 truncate"><?= e((string) $d['name']) ?></p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                    <?= e((string) ($d['game_name'] ?? 'geen spel gekoppeld')) ?>
                                </p>
                            </div>
                            <span class="text-[11px] font-bold uppercase tracking-wide <?= $busy ? 'text-amber-700' : 'text-brand-dark' ?>">
                                <?php if ($busy && !empty($d['active_started_at'])): ?>
                                    Bezig · <span class="device-timer" data-started="<?= e((string) $d['active_started_at']) ?>">--:--</span>
                                <?php elseif ($busy): ?>
                                    Bezig
                                <?php else: ?>
                                    Vrij
                                <?php endif; ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <script>
        (function () {
            function pad(n) { return String(n).padStart(2, '0'); }
            function tick() {
                const now = Date.now();
                document.querySelectorAll('.device-timer').forEach(el => {
                    const t = new Date(el.dataset.started.replace(' ', 'T')).getTime();
                    if (isNaN(t)) return;
                    const s = Math.max(0, Math.floor((now - t) / 1000));
                    const h = Math.floor(s / 3600);
                    const m = Math.floor((s % 3600) / 60);
                    el.textContent = (h > 0 ? h + ':' + pad(m) : pad(m)) + ':' + pad(s % 60);
                });
            }
            tick();
            setInterval(tick, 1000);
        })();
        </script>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js" defer></script>
<script>
(function () {
    const video    = document.getElementById('scanVideo');
    const canvas   = document.getElementById('scanCanvas');
    const status   = document.getElementById('scanStatus');
    const manualEl = document.getElementById('manualCode');
    const manualF  = document.getElementById('manualForm');
    const baseUrl  = location.origin;
    let stopped = false;

    function go(target) {
        if (stopped) return;
        stopped = true;
        try { video.srcObject?.getTracks().forEach(t => t.stop()); } catch (e) {}
        location.href = target;
    }

    function handleText(text) {
        if (!text) return;
        // Accept either a full /d/<code> URL or the bare 6-letter code
        try {
            const u = new URL(text, baseUrl);
            if (u.pathname.startsWith('/d/')) { go(u.pathname); return; }
            if (u.pathname.startsWith('/m/')) { go(u.pathname); return; }
        } catch (e) {}
        const code = text.trim().toUpperCase().replace(/[^A-Z0-9]/g, '');
        if (code.length >= 4 && code.length <= 12) go('/d/' + code);
    }

    manualF.addEventListener('submit', (ev) => {
        ev.preventDefault();
        handleText(manualEl.value);
    });

    async function startCamera() {
        if (!navigator.mediaDevices?.getUserMedia) {
            status.textContent = 'Camera niet beschikbaar — gebruik handmatige invoer.';
            return;
        }
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: 'environment' } }, audio: false
            });
            video.srcObject = stream;
            await video.play();
            status.textContent = 'Richt op QR…';
            requestAnimationFrame(scanLoop);
        } catch (err) {
            status.textContent = 'Geen camera-toegang. Vul de code handmatig in.';
        }
    }

    function scanLoop() {
        if (stopped || video.readyState < video.HAVE_ENOUGH_DATA) {
            return requestAnimationFrame(scanLoop);
        }
        const w = video.videoWidth, h = video.videoHeight;
        canvas.width = w; canvas.height = h;
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        ctx.drawImage(video, 0, 0, w, h);
        try {
            const data = ctx.getImageData(0, 0, w, h);
            if (typeof jsQR === 'function') {
                const result = jsQR(data.data, w, h, { inversionAttempts: 'dontInvert' });
                if (result && result.data) {
                    status.textContent = 'Gevonden!';
                    handleText(result.data);
                    return;
                }
            }
        } catch (e) {}
        requestAnimationFrame(scanLoop);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startCamera, { once: true });
    } else {
        startCamera();
    }
})();
</script>
