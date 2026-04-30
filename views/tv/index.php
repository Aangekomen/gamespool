<?php
/** @var array $active */
/** @var array $top */
/** @var array $topSeason */
/** @var array $topWeek */
/** @var array $devices */
/** @var string $seasonLabel */
/** @var int $seasonDays */
/** @var ?array $currentGame */
/** @var array $allGames */
/** @var string $scoringMode */
$tvTitle = $currentGame ? htmlspecialchars($currentGame['name']) : 'Alle sporten';
$tvSubtitle = $currentGame ? 'TV — ' . htmlspecialchars($currentGame['name']) : 'TV — globaal · 1 pt per winst';
?>
<!doctype html>
<html lang="nl" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f172a">
    <meta http-equiv="refresh" content="10">
    <title>FlexiComp · <?= $tvTitle ?> · TV</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: {
                brand: { DEFAULT:'#35b782', dark:'#2ba16f', light:'#e8f6f0' },
                navy:  { DEFAULT:'#171a56', soft:'#3a3d7a' },
            }, fontFamily: { sans: ['Inter','system-ui','sans-serif'] } } }
        };
    </script>
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        @keyframes pulse-dot { 0%,100% { opacity: 1 } 50% { opacity: .35 } }
        .live-dot { animation: pulse-dot 1.4s ease-in-out infinite; }
        .slide { display: none; animation: fadeIn .6s ease-out; }
        .slide.active { display: flex; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    </style>
</head>
<body class="min-h-screen flex flex-col">

<!-- Header — altijd zichtbaar -->
<header class="px-8 py-5 flex items-center justify-between border-b border-slate-800/70 shrink-0">
    <div class="flex items-center gap-3 min-w-0">
        <span class="inline-block w-9 h-9 rounded-md bg-navy relative shrink-0">
            <span class="absolute inset-1.5 rounded-sm bg-brand"></span>
        </span>
        <div class="min-w-0">
            <p class="text-2xl font-extrabold tracking-tight text-white truncate"><?= $tvTitle ?></p>
            <p class="text-[10px] uppercase tracking-widest text-slate-500 truncate"><?= $tvSubtitle ?></p>
        </div>
        <span class="hidden md:inline-block text-xs uppercase tracking-widest text-slate-500 ml-3 shrink-0">
            Seizoen <?= htmlspecialchars($seasonLabel) ?> · nog <?= (int) $seasonDays ?> dagen
        </span>
    </div>
    <div class="flex items-center gap-6">
        <!-- Slide-indicator dots -->
        <div id="dots" class="flex items-center gap-1.5"></div>
        <div class="text-right">
            <p id="clock" class="text-3xl font-bold tabular-nums text-white">--:--</p>
            <p id="date" class="text-xs uppercase tracking-widest text-slate-400">––––</p>
        </div>
    </div>
</header>

<nav class="px-8 py-2 border-b border-slate-800/70 flex items-center gap-2 overflow-x-auto whitespace-nowrap shrink-0">
    <a href="<?= htmlspecialchars(url('/tv')) ?>"
       class="text-xs uppercase tracking-widest font-bold px-3 py-1.5 rounded-full border <?= $currentGame === null ? 'bg-brand text-navy border-brand' : 'border-slate-800 text-slate-400 hover:text-white hover:border-slate-600' ?>">
        Globaal
    </a>
    <?php foreach ($allGames as $g): $sel = $currentGame && (int) $currentGame['id'] === (int) $g['id']; ?>
        <a href="<?= htmlspecialchars(url('/tv/' . $g['slug'])) ?>"
           class="text-xs uppercase tracking-widest font-bold px-3 py-1.5 rounded-full border <?= $sel ? 'bg-brand text-navy border-brand' : 'border-slate-800 text-slate-400 hover:text-white hover:border-slate-600' ?>">
            <?= htmlspecialchars($g['name']) ?>
        </a>
    <?php endforeach; ?>
</nav>

<main class="flex-1 px-8 py-6 overflow-hidden">

    <!-- Slide 1: Now playing + tafels -->
    <section class="slide active flex-col gap-6 h-full" data-slide="now">
        <div>
            <h2 class="flex items-center gap-3 text-2xl font-extrabold uppercase tracking-wider text-slate-300 mb-4">
                <span class="relative flex h-3 w-3">
                    <span class="absolute inline-flex h-full w-full rounded-full bg-brand opacity-60 live-dot"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-brand"></span>
                </span>
                Now playing
                <span class="ml-auto text-sm font-medium text-slate-500"><?= count($active) ?> actief</span>
            </h2>
            <?php if (empty($active)): ?>
                <div class="rounded-2xl border border-slate-800 bg-slate-900/40 p-8 text-center text-slate-500">
                    Geen lopende matches.
                </div>
            <?php else: ?>
                <div class="grid grid-cols-2 gap-3">
                    <?php foreach ($active as $am):
                        $names = $am['participant_names'] ?? [];
                    ?>
                        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
                            <div class="flex items-center justify-between mb-1.5">
                                <p class="text-base font-bold text-white truncate">
                                    <?= htmlspecialchars($am['game_name']) ?><?= $am['label'] ? ' · ' . htmlspecialchars($am['label']) : '' ?>
                                </p>
                                <span class="shrink-0 text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 rounded-full
                                    <?= $am['state'] === 'waiting' ? 'bg-amber-500/20 text-amber-300' : 'bg-brand/20 text-brand' ?>">
                                    <?= $am['state'] === 'waiting' ? 'Wacht' : 'Bezig' ?>
                                </span>
                            </div>
                            <p class="text-sm text-slate-400 truncate">
                                <?php if (!empty($names)): ?>
                                    <?= htmlspecialchars(implode(' vs ', $names)) ?>
                                <?php else: ?>
                                    — geen deelnemers —
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <h2 class="text-2xl font-extrabold uppercase tracking-wider text-slate-300 mb-4">
                Tafels <span class="text-sm font-medium text-slate-500">— scan om te starten</span>
            </h2>
            <?php if (empty($devices)): ?>
                <div class="rounded-2xl border border-slate-800 bg-slate-900/40 p-8 text-center text-slate-500">
                    Nog geen apparaten ingericht.
                </div>
            <?php else: ?>
                <div class="grid grid-cols-3 gap-3">
                    <?php foreach ($devices as $d):
                        $busy    = (int) $d['active_count'] > 0;
                        $waiting = $busy && ($d['active_state'] ?? '') === 'waiting';
                    ?>
                        <div class="rounded-2xl border <?= $busy ? 'border-amber-500/40 bg-amber-500/5' : 'border-slate-800 bg-slate-900/60' ?> p-4 flex items-center gap-3">
                            <div class="w-12 h-12 rounded-md bg-white p-1 shrink-0">
                                <img src="<?= htmlspecialchars(url('/qr.svg?text=' . urlencode(url('/d/' . $d['code'])) . '&size=120')) ?>"
                                     alt="" class="w-full h-full block">
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-white text-base truncate"><?= htmlspecialchars($d['name']) ?></p>
                                <p class="text-xs text-slate-400 truncate"><?= htmlspecialchars($d['game_name'] ?? 'geen spel gekoppeld') ?></p>
                                <p class="text-[10px] uppercase tracking-widest font-bold mt-0.5 <?= $busy ? 'text-amber-300' : 'text-brand' ?>">
                                    <?php if ($waiting): ?>
                                        Wacht op tegenstander
                                    <?php elseif ($busy && !empty($d['active_started_at'])): ?>
                                        Bezig · <span class="device-timer" data-started="<?= htmlspecialchars((string) $d['active_started_at']) ?>">--:--</span>
                                    <?php elseif ($busy): ?>
                                        Bezig
                                    <?php else: ?>
                                        Vrij · <?= htmlspecialchars($d['code']) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Slide 2: Seizoens-leaderboard -->
    <section class="slide flex-col h-full" data-slide="season">
        <h2 class="text-2xl font-extrabold uppercase tracking-wider text-slate-300 mb-4 flex items-center gap-3">
            🏆 Seizoen <?= htmlspecialchars($seasonLabel) ?>
            <span class="text-sm font-medium text-slate-500">— nog <?= (int) $seasonDays ?> dagen tot reset</span>
        </h2>
        <?php if (empty($topSeason)): ?>
            <div class="rounded-2xl border border-slate-800 bg-slate-900/40 p-8 text-center text-slate-500">
                Nog geen scores in dit seizoen.
            </div>
        <?php else: ?>
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 divide-y divide-slate-800/60 overflow-hidden">
                <?php foreach ($topSeason as $i => $p):
                    $rankClr = $i === 0 ? 'text-amber-400' : ($i === 1 ? 'text-slate-300' : ($i === 2 ? 'text-amber-700' : 'text-slate-500'));
                ?>
                    <div class="flex items-center gap-4 px-5 py-3">
                        <span class="w-8 text-2xl font-black tabular-nums text-center <?= $rankClr ?>"><?= $i + 1 ?></span>
                        <div class="w-11 h-11 rounded-full bg-slate-800 flex items-center justify-center text-base font-bold text-slate-300 shrink-0 overflow-hidden">
                            <?php if (!empty($p['avatar_path'])): ?>
                                <img src="<?= htmlspecialchars(url('/uploads/avatars/' . $p['avatar_path'])) ?>" alt="" class="w-full h-full object-cover">
                            <?php else: ?>
                                <?= htmlspecialchars(strtoupper(mb_substr((string) $p['display_name'], 0, 1))) ?>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-lg font-bold text-white truncate"><?= htmlspecialchars($p['display_name']) ?></p>
                            <p class="text-xs text-slate-500"><?= (int) $p['matches_played'] ?> matches · <?= (int) $p['wins'] ?> winst</p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-extrabold tabular-nums text-white"><?= (int) $p['total_points'] ?></p>
                            <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold">Punten</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Slide 3: Hall of fame (lifetime + week champion) -->
    <section class="slide flex-col h-full" data-slide="hof">
        <h2 class="text-2xl font-extrabold uppercase tracking-wider text-slate-300 mb-4">
            👑 Hall of Fame <span class="text-sm font-medium text-slate-500">— lifetime</span>
        </h2>
        <?php if (empty($top)): ?>
            <div class="rounded-2xl border border-slate-800 bg-slate-900/40 p-8 text-center text-slate-500">
                Nog geen scores.
            </div>
        <?php else: ?>
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 divide-y divide-slate-800/60 overflow-hidden">
                <?php foreach ($top as $i => $p):
                    $rankClr = $i === 0 ? 'text-amber-400' : ($i === 1 ? 'text-slate-300' : ($i === 2 ? 'text-amber-700' : 'text-slate-500'));
                ?>
                    <div class="flex items-center gap-4 px-5 py-3">
                        <span class="w-8 text-2xl font-black tabular-nums text-center <?= $rankClr ?>"><?= $i + 1 ?></span>
                        <div class="w-11 h-11 rounded-full bg-slate-800 flex items-center justify-center text-base font-bold text-slate-300 shrink-0 overflow-hidden">
                            <?php if (!empty($p['avatar_path'])): ?>
                                <img src="<?= htmlspecialchars(url('/uploads/avatars/' . $p['avatar_path'])) ?>" alt="" class="w-full h-full object-cover">
                            <?php else: ?>
                                <?= htmlspecialchars(strtoupper(mb_substr((string) $p['display_name'], 0, 1))) ?>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-lg font-bold text-white truncate"><?= htmlspecialchars($p['display_name']) ?></p>
                            <p class="text-xs text-slate-500"><?= (int) $p['matches_played'] ?> matches · <?= (int) $p['wins'] ?> winst</p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-extrabold tabular-nums text-white"><?= (int) $p['total_points'] ?></p>
                            <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold">Punten</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Slide 4: Top deze week (kleinere highlight) -->
    <section class="slide flex-col h-full" data-slide="week">
        <h2 class="text-2xl font-extrabold uppercase tracking-wider text-slate-300 mb-4">
            🔥 Top deze week
        </h2>
        <?php if (empty($topWeek)): ?>
            <div class="rounded-2xl border border-slate-800 bg-slate-900/40 p-8 text-center text-slate-500">
                Nog geen actie deze week. Wie pakt de eerste plek?
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <?php foreach ($topWeek as $i => $p):
                    $podium = $i === 0 ? 'border-amber-400 bg-amber-400/10' : ($i === 1 ? 'border-slate-400 bg-slate-400/10' : ($i === 2 ? 'border-amber-700 bg-amber-700/10' : 'border-slate-800 bg-slate-900/60'));
                ?>
                    <div class="rounded-2xl border <?= $podium ?> p-5 text-center">
                        <p class="text-5xl font-black mb-2"><?= $i === 0 ? '🥇' : ($i === 1 ? '🥈' : ($i === 2 ? '🥉' : '#' . ($i + 1))) ?></p>
                        <div class="w-16 h-16 rounded-full bg-slate-800 mx-auto flex items-center justify-center text-xl font-bold text-slate-300 overflow-hidden mb-2">
                            <?php if (!empty($p['avatar_path'])): ?>
                                <img src="<?= htmlspecialchars(url('/uploads/avatars/' . $p['avatar_path'])) ?>" alt="" class="w-full h-full object-cover">
                            <?php else: ?>
                                <?= htmlspecialchars(strtoupper(mb_substr((string) $p['display_name'], 0, 1))) ?>
                            <?php endif; ?>
                        </div>
                        <p class="text-base font-bold text-white truncate"><?= htmlspecialchars($p['display_name']) ?></p>
                        <p class="text-2xl font-extrabold tabular-nums text-white mt-1"><?= (int) $p['total_points'] ?></p>
                        <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold">punten</p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

</main>

<script>
    function tick() {
        const d = new Date();
        const pad = n => String(n).padStart(2, '0');
        document.getElementById('clock').textContent = pad(d.getHours()) + ':' + pad(d.getMinutes());
        const days = ['ZO','MA','DI','WO','DO','VR','ZA'];
        const months = ['jan','feb','mrt','apr','mei','jun','jul','aug','sep','okt','nov','dec'];
        document.getElementById('date').textContent = days[d.getDay()] + ' ' + d.getDate() + ' ' + months[d.getMonth()];
    }
    tick();
    setInterval(tick, 30000);

    // Live device timers
    function pad(n) { return String(n).padStart(2, '0'); }
    function refreshDeviceTimers() {
        const now = Date.now();
        document.querySelectorAll('.device-timer').forEach(el => {
            const started = new Date(el.dataset.started.replace(' ', 'T')).getTime();
            if (isNaN(started)) return;
            const secs = Math.max(0, Math.floor((now - started) / 1000));
            const h = Math.floor(secs / 3600);
            const m = Math.floor((secs % 3600) / 60);
            el.textContent = (h > 0 ? h + ':' + pad(m) : pad(m)) + ':' + pad(secs % 60);
        });
    }
    refreshDeviceTimers();
    setInterval(refreshDeviceTimers, 1000);

    // Slide-rotatie — wissel elke 12s, dots in de header tonen voortgang.
    // sessionStorage onthoudt de huidige slide-index én hoe lang we daar al
    // op zitten, zodat een 10s page-refresh de rotatie niet steeds reset.
    (function () {
        const slides = Array.from(document.querySelectorAll('.slide'));
        const dotsEl = document.getElementById('dots');
        if (slides.length <= 1) return;
        slides.forEach((_, i) => {
            const d = document.createElement('span');
            d.className = 'inline-block w-2 h-2 rounded-full transition bg-slate-700';
            dotsEl.appendChild(d);
        });
        const dots = Array.from(dotsEl.children);
        const SLIDE_MS = 12000;

        // Lees opgeslagen positie + verstreken tijd op deze slide
        let savedIdx = 0;
        let savedElapsed = 0;
        try {
            const raw = sessionStorage.getItem('tv_slide');
            if (raw) {
                const obj = JSON.parse(raw);
                savedIdx = (obj.idx | 0) % slides.length;
                savedElapsed = Math.max(0, Date.now() - (obj.t || Date.now()));
            }
        } catch (e) {}

        let idx = savedIdx;
        function activate(i) {
            // Reset huidige
            slides.forEach((s, j) => s.classList.toggle('active', j === i));
            dots.forEach((d, j) => {
                d.classList.toggle('bg-brand', j === i);
                d.classList.toggle('bg-slate-700', j !== i);
                d.classList.toggle('w-6', j === i);
                d.classList.toggle('w-2', j !== i);
            });
            idx = i;
        }
        function persist(timestamp) {
            try { sessionStorage.setItem('tv_slide', JSON.stringify({ idx, t: timestamp })); } catch (e) {}
        }

        activate(idx);
        // Als we al een tijd op deze slide zaten, wachten we korter tot de switch.
        const remaining = Math.max(500, SLIDE_MS - (savedElapsed % SLIDE_MS));
        setTimeout(function tick() {
            const next = (idx + 1) % slides.length;
            activate(next);
            persist(Date.now());
            setTimeout(tick, SLIDE_MS);
        }, remaining);
        // Persist nu vast met "we zitten net hier" (Date.now adjusted) zodat
        // refreshes binnen die SLIDE_MS de timer correct doorrekenen.
        persist(Date.now() - (savedElapsed % SLIDE_MS));
    })();
</script>
</body>
</html>
