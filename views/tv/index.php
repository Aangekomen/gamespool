<?php
/** @var array $active */
/** @var array $top */
/** @var array $devices */
?>
<!doctype html>
<html lang="nl" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f172a">
    <meta http-equiv="refresh" content="30">
    <title>FlexiComp · TV</title>
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
    </style>
</head>
<body class="min-h-screen flex flex-col">

<!-- Header -->
<header class="px-8 py-5 flex items-center justify-between border-b border-slate-800/70">
    <div class="flex items-center gap-3">
        <span class="inline-block w-9 h-9 rounded-md bg-navy relative">
            <span class="absolute inset-1.5 rounded-sm bg-brand"></span>
        </span>
        <span class="text-2xl font-extrabold tracking-tight text-white">FlexiComp</span>
    </div>
    <div class="text-right">
        <p id="clock" class="text-3xl font-bold tabular-nums text-white">--:--</p>
        <p id="date" class="text-xs uppercase tracking-widest text-slate-400">––––</p>
    </div>
</header>

<main class="flex-1 px-8 py-6 space-y-6">

    <!-- Now playing -->
    <section>
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
    </section>

    <!-- Leaderboard -->
    <section>
        <h2 class="text-2xl font-extrabold uppercase tracking-wider text-slate-300 mb-4">
            Leaderboard <span class="text-sm font-medium text-slate-500">— lifetime</span>
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
                        <span class="w-8 text-2xl font-black tabular-nums text-center <?= $rankClr ?>">
                            <?= $i + 1 ?>
                        </span>
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

    <!-- Devices -->
    <section>
        <h2 class="text-2xl font-extrabold uppercase tracking-wider text-slate-300 mb-4">
            Apparaten <span class="text-sm font-medium text-slate-500">— scan om te starten</span>
        </h2>

        <?php if (empty($devices)): ?>
            <div class="rounded-2xl border border-slate-800 bg-slate-900/40 p-8 text-center text-slate-500">
                Nog geen apparaten ingericht.
            </div>
        <?php else: ?>
            <div class="grid grid-cols-3 gap-3">
                <?php foreach ($devices as $d):
                    $busy = (int) $d['active_count'] > 0;
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
                                <?= $busy ? 'Bezig' : 'Vrij' ?> · <?= htmlspecialchars($d['code']) ?>
                            </p>
                        </div>
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
</script>
</body>
</html>
