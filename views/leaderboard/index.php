<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var string $period */
/** @var ?array $game */
/** @var array $games */
/** @var array $players */
/** @var array $teams */
/** @var array $seasons */
/** @var ?string $seasonKey */
/** @var ?string $seasonLabel */
use GamesPool\Models\Leaderboard;

$title = 'Leaderboard';
$isElo = $game && ($game['score_type'] === 'elo') && $period === 'lifetime';
$pointsLabel = $isElo ? 'Rating' : 'Punten';
$inputCls = 'rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand';
?>

<div class="mb-4">
    <h1 class="text-2xl font-bold text-navy dark:text-slate-100">Leaderboard</h1>
    <p class="text-slate-500 dark:text-slate-400 text-sm">Wie staat er aan kop?</p>
</div>

<!-- Seizoen-balk: telt af tot eind van het kwartaal + dropdown om terug te kijken -->
<div class="rounded-2xl bg-navy text-white px-4 py-3 mb-3 shadow-card">
    <div class="flex items-center gap-3">
        <span class="text-2xl">🏆</span>
        <div class="flex-1 min-w-0">
            <p class="text-[10px] uppercase tracking-widest text-white/60 font-bold">
                <?= $seasonLabel ? 'Seizoen ' . e($seasonLabel) . ' (archief)' : 'Seizoen ' . e(Leaderboard::currentSeasonLabel()) ?>
            </p>
            <p class="text-sm font-semibold">
                <?= $seasonLabel ? 'Historisch overzicht' : Leaderboard::seasonDaysLeft() . ' dagen tot reset' ?>
            </p>
        </div>
        <?php if (!$seasonLabel): ?>
            <a href="<?= e(url('/leaderboard?period=season')) ?>"
               class="text-xs font-semibold bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-md shrink-0">
                Bekijk
            </a>
        <?php else: ?>
            <a href="<?= e(url('/leaderboard')) ?>"
               class="text-xs font-semibold bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-md shrink-0">
                ← Nu
            </a>
        <?php endif; ?>
    </div>
    <?php if (!empty($seasons) && count($seasons) > 1): ?>
        <form method="get" action="<?= e(url('/leaderboard')) ?>" class="mt-3">
            <?php if (!empty($game['slug'])): ?>
                <input type="hidden" name="game" value="<?= e($game['slug']) ?>">
            <?php endif; ?>
            <label class="block text-[10px] uppercase tracking-widest text-white/60 font-bold mb-1">Bekijk seizoen</label>
            <select name="season" onchange="this.form.submit()"
                    class="w-full rounded-md bg-white/10 border border-white/20 text-white text-sm px-2 py-1.5">
                <option value="">Huidig seizoen</option>
                <?php foreach ($seasons as $s): ?>
                    <option value="<?= e($s['key']) ?>" <?= $seasonKey === $s['key'] ? 'selected' : '' ?>>
                        <?= e($s['label']) ?><?= $s['is_current'] ? ' · nu' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    <?php endif; ?>
</div>

<form method="get" action="<?= e(url('/leaderboard')) ?>" class="grid grid-cols-2 gap-2 mb-4">
    <?php if ($seasonKey): ?>
        <input type="hidden" name="season" value="<?= e($seasonKey) ?>">
    <?php endif; ?>
    <select name="period" onchange="this.form.submit()" class="<?= $inputCls ?>" <?= $seasonKey ? 'disabled' : '' ?>>
        <?php foreach (Leaderboard::PERIODS as $p): ?>
            <option value="<?= e($p) ?>" <?= $p === $period ? 'selected' : '' ?>>
                <?= e(Leaderboard::periodLabel($p)) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <select name="game" onchange="this.form.submit()" class="<?= $inputCls ?>">
        <option value="all">Alle spellen</option>
        <?php foreach ($games as $g): ?>
            <option value="<?= e($g['slug']) ?>" <?= ($game['slug'] ?? '') === $g['slug'] ? 'selected' : '' ?>>
                <?= e($g['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<div class="flex border-b border-slate-200 dark:border-slate-800 mb-4 text-sm">
    <button type="button" data-tab="players" class="tab-btn flex-1 px-3 py-2 border-b-2 border-brand text-navy dark:text-slate-100 font-semibold">
        Spelers
    </button>
    <button type="button" data-tab="teams" class="tab-btn flex-1 px-3 py-2 border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-navy">
        Teams
    </button>
</div>

<section data-pane="players">
    <?php if (empty($players)): ?>
        <p class="text-center text-slate-500 dark:text-slate-400 py-8">Nog geen scores in deze periode.</p>
    <?php else: ?>
        <ol class="space-y-2">
            <?php foreach ($players as $i => $p): ?>
                <li class="flex items-center gap-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2.5 shadow-card">
                    <span class="w-7 text-center text-sm font-bold tabular-nums
                        <?= $i === 0 ? 'text-amber-500' : ($i === 1 ? 'text-slate-500 dark:text-slate-400' : ($i === 2 ? 'text-amber-700' : 'text-slate-400')) ?>">
                        <?= $i + 1 ?>
                    </span>
                    <div class="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-sm font-bold text-slate-500 dark:text-slate-400 shrink-0 overflow-hidden">
                        <?php if (!empty($p['avatar_path'])): ?>
                            <img src="<?= e(url('/uploads/avatars/' . $p['avatar_path'])) ?>" alt="" class="w-full h-full object-cover">
                        <?php else: ?>
                            <?= e(strtoupper(mb_substr($p['display_name'], 0, 1))) ?>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-navy dark:text-slate-100 truncate"><?= e($p['display_name']) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            <?= (int) $p['matches_played'] ?> matches
                            <?php if (!$isElo): ?> · <?= (int) $p['wins'] ?> winst<?php endif; ?>
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold tabular-nums text-navy dark:text-slate-100"><?= e((string) (int) $p['total_points']) ?></p>
                        <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold"><?= e($pointsLabel) ?></p>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</section>

<section data-pane="teams" class="hidden">
    <?php if (empty($teams)): ?>
        <p class="text-center text-slate-500 dark:text-slate-400 py-8">Nog geen team-scores in deze periode.</p>
    <?php else: ?>
        <ol class="space-y-2">
            <?php foreach ($teams as $i => $t): ?>
                <li class="flex items-center gap-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2.5 shadow-card">
                    <span class="w-7 text-center text-sm font-bold tabular-nums
                        <?= $i === 0 ? 'text-amber-500' : ($i === 1 ? 'text-slate-500 dark:text-slate-400' : ($i === 2 ? 'text-amber-700' : 'text-slate-400')) ?>">
                        <?= $i + 1 ?>
                    </span>
                    <div class="w-9 h-9 rounded-md bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-sm font-bold text-slate-500 dark:text-slate-400 shrink-0 overflow-hidden">
                        <?php if (!empty($t['logo_path'])): ?>
                            <img src="<?= e(url('/uploads/logos/' . $t['logo_path'])) ?>" alt="" class="w-full h-full object-cover">
                        <?php else: ?>
                            <?= e(strtoupper(mb_substr($t['name'], 0, 1))) ?>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-navy dark:text-slate-100 truncate"><?= e($t['name']) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400"><?= (int) $t['matches_played'] ?> matches · <?= (int) $t['wins'] ?> winst</p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold tabular-nums text-navy dark:text-slate-100"><?= e((string) (int) $t['total_points']) ?></p>
                        <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold">Punten</p>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</section>

<script>
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.tab;
            document.querySelectorAll('.tab-btn').forEach(b => {
                const active = (b === btn);
                b.classList.toggle('border-brand', active);
                b.classList.toggle('text-navy', active);
                b.classList.toggle('dark:text-slate-100', active);
                b.classList.toggle('font-semibold', active);
                b.classList.toggle('border-transparent', !active);
                b.classList.toggle('text-slate-500', !active);
                b.classList.toggle('dark:text-slate-400', !active);
            });
            document.querySelectorAll('[data-pane]').forEach(s => {
                s.classList.toggle('hidden', s.dataset.pane !== target);
            });
        });
    });
</script>
