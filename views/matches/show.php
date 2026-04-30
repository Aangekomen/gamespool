<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var array $match */
/** @var ?array $game */
/** @var array $participants */
use GamesPool\Models\Game;
$title = 'Match';
$type  = $game['score_type'] ?? 'win_loss';
?>

<div class="max-w-lg mx-auto">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-bold text-navy dark:text-slate-100"><?= e($game['name'] ?? 'Match') ?></h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm">
                <?= e(date('d-m-Y H:i', strtotime((string) $match['started_at']))) ?>
                · <?= e(Game::scoreTypeLabel($type)) ?>
                <?php if ($match['label']): ?> · <?= e($match['label']) ?><?php endif; ?>
            </p>
        </div>
        <span class="text-xs px-2 py-1 rounded-full font-medium
            <?= $match['state'] === 'in_progress' ? 'bg-amber-100 text-amber-800' : ($match['state'] === 'completed' ? 'bg-brand-light text-brand-dark' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300') ?>">
            <?= $match['state'] === 'in_progress' ? 'Bezig' : ($match['state'] === 'completed' ? 'Afgerond' : 'Geannuleerd') ?>
        </span>
    </div>

    <?php if ($type === 'team_score' && $match['state'] === 'completed'):
        $bySide = ['A' => [], 'B' => []];
        foreach ($participants as $p) {
            $side = $p['match_side'] ?? null;
            if ($side === 'A' || $side === 'B') $bySide[$side][] = $p;
        }
        $scoreA = $bySide['A'][0]['raw_score'] ?? null;
        $scoreB = $bySide['B'][0]['raw_score'] ?? null;
        $aWin = $scoreA !== null && $scoreB !== null && (int) $scoreA > (int) $scoreB;
        $bWin = $scoreA !== null && $scoreB !== null && (int) $scoreB > (int) $scoreA;
    ?>
        <div class="grid grid-cols-2 gap-2 mb-2">
            <?php foreach (['A' => $aWin, 'B' => $bWin] as $side => $isWin):
                $score = $side === 'A' ? $scoreA : $scoreB;
            ?>
                <div class="rounded-2xl border <?= $isWin ? 'border-brand bg-brand-light' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900' ?> p-4 text-center shadow-card">
                    <p class="text-[11px] uppercase tracking-wide font-bold <?= $isWin ? 'text-brand-dark' : 'text-slate-500 dark:text-slate-400' ?>">Team <?= $side ?></p>
                    <p class="text-4xl font-black tabular-nums <?= $isWin ? 'text-navy' : 'text-navy dark:text-slate-100' ?>"><?= e((string) ($score ?? '–')) ?></p>
                    <ul class="mt-2 space-y-1">
                        <?php foreach ($bySide[$side] as $sp):
                            $spName = $sp['display_name'] ?? '–';
                            $spTextCls = $isWin ? 'text-navy' : 'text-navy dark:text-slate-100';
                        ?>
                            <li class="text-sm font-semibold truncate <?= $spTextCls ?>"><?= e($spName) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="text-center text-xs text-slate-500 dark:text-slate-400">+<?= (int) ($participants[0]['points_awarded'] ?? 0) ?> punten voor winnaars</p>
    <?php else: ?>
    <ul class="space-y-2">
        <?php foreach ($participants as $p):
            $name = $p['display_name'] ?? 'Onbekend';
            $isWinner = ($p['result'] ?? null) === 'win';

            // Winner card: keep its content readable on the brand-light background
            // in both light and dark mode (no dark: text overrides).
            $cardCls = $isWinner
                ? 'border-brand bg-brand-light'
                : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900';
            $nameCls   = $isWinner ? 'font-semibold text-navy truncate' : 'font-semibold text-navy dark:text-slate-100 truncate';
            $subCls    = $isWinner ? 'text-xs text-brand-dark' : 'text-xs text-slate-500 dark:text-slate-400';
            $bigCls    = $isWinner ? 'text-lg font-bold tabular-nums text-navy' : 'text-lg font-bold tabular-nums text-navy dark:text-slate-100';
            $avatarCls = $isWinner ? 'bg-white text-brand-dark' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400';
        ?>
            <li class="rounded-xl border <?= $cardCls ?> p-3 flex items-center gap-3 shadow-card">
                <div class="w-10 h-10 rounded-full <?= $avatarCls ?> flex items-center justify-center text-sm font-bold shrink-0 overflow-hidden">
                    <?php if (!empty($p['avatar_path'])): ?>
                        <img src="<?= e(url('/uploads/avatars/' . $p['avatar_path'])) ?>" alt="" class="w-full h-full object-cover">
                    <?php else: ?>
                        <?= e(strtoupper(mb_substr($name, 0, 1))) ?>
                    <?php endif; ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="<?= $nameCls ?>"><?= e($name) ?></p>
                    <?php if ($match['state'] === 'completed' && $p['result']): ?>
                        <p class="<?= $subCls ?>">
                            <?= match ($p['result']) { 'win' => 'Winst', 'draw' => 'Gelijk', 'loss' => 'Verlies', default => '' } ?>
                            <?php if ($p['raw_score'] !== null): ?> · score <?= e((string) $p['raw_score']) ?><?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
                <?php if ($match['state'] === 'completed'): ?>
                    <?php if ($type === 'elo' && $p['rating_after'] !== null): ?>
                        <div class="text-right">
                            <p class="<?= $bigCls ?>"><?= e((string) $p['rating_after']) ?></p>
                            <?php $delta = (int) $p['points_awarded']; ?>
                            <p class="text-xs <?= $delta >= 0 ? 'text-brand-dark' : 'text-red-600' ?>">
                                <?= $delta >= 0 ? '+' : '' ?><?= e((string) $delta) ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <span class="<?= $bigCls ?>"><?= e((string) (int) $p['points_awarded']) ?></span>
                    <?php endif; ?>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <?php if ($match['state'] === 'in_progress'): ?>
        <div class="mt-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-4 py-3 flex items-center justify-between shadow-card">
            <div class="text-sm text-slate-500 dark:text-slate-400">Speeltijd</div>
            <div id="liveTimer" class="text-2xl font-extrabold tabular-nums text-navy dark:text-slate-100"
                 data-started="<?= e((string) $match['started_at']) ?>">--:--</div>
        </div>
        <a href="<?= e(url('/matches/' . $match['id'] . '/record')) ?>"
           class="block text-center mt-3 w-full rounded-lg bg-brand text-white font-semibold px-4 py-3 hover:bg-brand-dark">
            Uitslag invoeren
        </a>
    <?php elseif ($match['state'] === 'completed' && !empty($match['ended_at'])):
        $secs = max(0, strtotime((string) $match['ended_at']) - strtotime((string) $match['started_at']));
        $h = floor($secs / 3600); $m = floor(($secs % 3600) / 60); $s = $secs % 60;
        $duration = $h > 0 ? sprintf('%dh %02dm', $h, $m) : sprintf('%dm %02ds', $m, $s);
    ?>
        <p class="mt-3 text-center text-xs text-slate-500 dark:text-slate-400">
            Speeltijd: <strong class="text-navy dark:text-slate-100"><?= e($duration) ?></strong>
        </p>
    <?php endif; ?>

    <?php if ($match['state'] === 'completed'): ?>
        <?php if (!empty($h2h) && ($h2h['data']['matches'] ?? 0) > 0):
            $hd = $h2h['data'];
            $aw = (int) $hd['a']['wins'];
            $bw = (int) $hd['b']['wins'];
            $ap = (int) $hd['a']['points'];
            $bp = (int) $hd['b']['points'];
        ?>
            <div class="mt-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card">
                <p class="text-[11px] uppercase tracking-wide font-bold text-slate-500 dark:text-slate-400 mb-2">Onderlinge balans · <?= e((string) ($game['name'] ?? '')) ?></p>
                <div class="grid grid-cols-3 items-center gap-2">
                    <div class="text-center min-w-0">
                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate"><?= e((string) $h2h['a_name']) ?></p>
                        <p class="text-3xl font-black tabular-nums <?= $aw > $bw ? 'text-brand-dark' : 'text-navy dark:text-slate-100' ?>"><?= $aw ?></p>
                        <p class="text-[10px] uppercase tracking-wide text-slate-400">winsten · <?= $ap ?> pt</p>
                    </div>
                    <div class="text-center text-slate-400 font-bold">vs</div>
                    <div class="text-center min-w-0">
                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate"><?= e((string) $h2h['b_name']) ?></p>
                        <p class="text-3xl font-black tabular-nums <?= $bw > $aw ? 'text-brand-dark' : 'text-navy dark:text-slate-100' ?>"><?= $bw ?></p>
                        <p class="text-[10px] uppercase tracking-wide text-slate-400">winsten · <?= $bp ?> pt</p>
                    </div>
                </div>
                <p class="text-center text-[11px] text-slate-500 dark:text-slate-400 mt-2">
                    Over <?= (int) $hd['matches'] ?> afgeronde wedstrijd<?= $hd['matches'] === 1 ? '' : 'en' ?>.
                </p>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('/matches/' . $match['id'] . '/rematch')) ?>" class="mt-3">
            <?= csrf_field() ?>
            <button class="w-full rounded-lg bg-navy text-white font-semibold px-4 py-3 hover:bg-navy-soft flex items-center justify-center gap-2">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v6h6M20 20v-6h-6M5 14a8 8 0 0 0 14.5 2M19 10A8 8 0 0 0 4.5 8"/></svg>
                Rematch — punten opsparen
            </button>
        </form>
        <p class="text-center text-xs text-slate-500 dark:text-slate-400 mt-1">
            Speel opnieuw met dezelfde spelers; alle punten tellen op in je totaal.
        </p>
    <?php endif; ?>

    <?php if (!empty($game['rules'])): ?>
        <details class="mt-4 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-card overflow-hidden">
            <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-navy dark:text-slate-100 flex items-center justify-between">
                <span>📖 Spelregels — <?= e($game['name']) ?></span>
                <span class="text-xs text-slate-400">tik om te openen</span>
            </summary>
            <div class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line border-t border-slate-100 dark:border-slate-800">
                <?= nl2br(e((string) $game['rules'])) ?>
            </div>
        </details>
    <?php endif; ?>

    <div class="mt-6 text-center">
        <a href="<?= e(url('/matches')) ?>" class="text-sm text-slate-500 dark:text-slate-400 hover:text-navy">← terug naar matches</a>
    </div>
</div>

<?php if ($match['state'] === 'in_progress'): ?>
<script>
(function () {
    const el = document.getElementById('liveTimer');
    if (!el) return;
    const started = new Date(el.dataset.started.replace(' ', 'T')).getTime();
    if (isNaN(started)) return;
    const pad = n => String(n).padStart(2, '0');
    function tick() {
        const secs = Math.max(0, Math.floor((Date.now() - started) / 1000));
        const h = Math.floor(secs / 3600);
        const m = Math.floor((secs % 3600) / 60);
        const s = secs % 60;
        el.textContent = (h > 0 ? h + ':' + pad(m) : pad(m)) + ':' + pad(s);
    }
    tick();
    setInterval(tick, 1000);
})();
</script>
<?php endif; ?>
