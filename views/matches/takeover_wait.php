<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var array $match */
/** @var ?array $game */
/** @var ?array $device */
/** @var array $participants */
/** @var int $minutesRunning */
$title = 'Wacht op tafel';
$status = $match['takeover_status'] ?? 'pending';
?>

<div class="max-w-md mx-auto">
    <div class="rounded-2xl bg-navy text-white p-5 shadow-card">
        <p class="text-xs uppercase tracking-widest text-white/60 font-bold">Wachtkamer</p>
        <h1 class="text-2xl font-bold mt-1">
            <?= e((string) ($device['name'] ?? ($game['name'] ?? 'Tafel'))) ?>
        </h1>
        <p class="text-white/70 text-sm mt-1">
            Er speelt al <?= (int) $minutesRunning ?> min een potje. We vragen of ze nog doorgaan.
        </p>
    </div>

    <!-- Huidige spelers -->
    <div class="mt-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card">
        <p class="text-xs uppercase tracking-wide font-bold text-slate-500 dark:text-slate-400 mb-2">Huidige spelers</p>
        <ul class="space-y-2">
            <?php foreach ($participants as $p):
                $name = $p['display_name'] ?? '?';
            ?>
                <li class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-brand-light text-brand-dark flex items-center justify-center font-bold overflow-hidden shrink-0">
                        <?php if (!empty($p['avatar_path'])): ?>
                            <img src="<?= e(url('/uploads/avatars/' . $p['avatar_path'])) ?>" alt="" class="w-full h-full object-cover">
                        <?php else: ?>
                            <?= e(strtoupper(mb_substr((string) $name, 0, 1))) ?>
                        <?php endif; ?>
                    </div>
                    <span class="font-semibold text-navy dark:text-slate-100"><?= e((string) $name) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Status-blok dat live ververst -->
    <div id="statusBlock" class="mt-4">
        <?php if ($status === 'pending'): ?>
            <div class="rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/40 p-4 shadow-card text-center">
                <div class="text-3xl mb-2">⏳</div>
                <p class="text-sm font-bold text-amber-900 dark:text-amber-200">We hebben de huidige spelers gevraagd</p>
                <p class="text-xs text-amber-800 dark:text-amber-300 mt-1">Ze krijgen een melding op hun telefoon en kunnen reageren.</p>
            </div>
        <?php elseif ($status === 'still_playing'): ?>
            <div class="rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/40 p-4 shadow-card text-center">
                <div class="text-3xl mb-2">🎱</div>
                <p class="text-sm font-bold text-amber-900 dark:text-amber-200">Ze spelen nog even door</p>
                <p class="text-xs text-amber-800 dark:text-amber-300 mt-1">Probeer het over een paar minuten opnieuw.</p>
            </div>
        <?php elseif ($status === 'released'): ?>
            <div class="rounded-2xl bg-brand-light dark:bg-brand-dark/25 border border-brand/30 dark:border-brand/40 p-4 shadow-card text-center">
                <div class="text-3xl mb-2">🎉</div>
                <p class="text-sm font-bold text-brand-dark dark:text-brand-light">De tafel is vrij!</p>
                <form method="post" action="<?= e(url('/m/' . $match['join_token'] . '/claim')) ?>" class="mt-3">
                    <?= csrf_field() ?>
                    <button class="w-full rounded-lg bg-brand text-white font-semibold px-4 py-3 hover:bg-brand-dark">
                        Match starten
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div class="mt-4 text-center">
        <a href="<?= e(url('/scan')) ?>" class="text-sm text-slate-500 dark:text-slate-400 hover:text-navy">← terug naar scan</a>
    </div>
</div>

<script>
(function () {
    // Poll de status elke 4s tot er een reactie binnen is, en herlaad
    // de pagina zodra dat zo is.
    const url = <?= json_encode(url('/m/' . $match['join_token'] . '/wait.json')) ?>;
    const initial = <?= json_encode($status) ?>;
    let stops = 0;
    async function poll() {
        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const j = await res.json();
            if (j.ok && (j.takeover_status !== initial || j.state === 'cancelled' || j.state === 'completed')) {
                location.reload();
                return;
            }
            stops = 0;
        } catch (e) { if (++stops > 5) return; }
        setTimeout(poll, 4000);
    }
    setTimeout(poll, 4000);
})();
</script>
