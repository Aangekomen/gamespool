<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var array $totals */
/** @var array $hours */
/** @var array $days */
/** @var array $topGames */
/** @var array $topDevices */
/** @var array $growth */
$title = 'Bar stats';

$maxHour = max(1, max($hours));
$maxDay  = max(1, max($days));
$dayLabels = ['Zo','Ma','Di','Wo','Do','Vr','Za'];
?>

<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-2xl font-bold text-navy dark:text-slate-100">Bar stats</h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm">Wanneer is het druk en wat wordt het meest gespeeld?</p>
    </div>
    <a href="<?= e(url('/admin')) ?>" class="text-sm text-slate-500 dark:text-slate-400 hover:text-navy">← admin</a>
</div>

<!-- Big numbers -->
<div class="grid grid-cols-3 gap-3 mb-4">
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card text-center">
        <p class="text-3xl font-bold text-navy dark:text-slate-100 tabular-nums"><?= (int) $totals['matches_30d'] ?></p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">matches · 30d</p>
    </div>
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card text-center">
        <p class="text-3xl font-bold text-navy dark:text-slate-100 tabular-nums"><?= (int) $totals['hours_30d'] ?></p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">speeluren · 30d</p>
    </div>
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card text-center">
        <p class="text-3xl font-bold text-navy dark:text-slate-100 tabular-nums"><?= (int) $totals['new_users_30d'] ?></p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">nieuwe gebruikers</p>
    </div>
</div>

<!-- Piekuren (per uur, 0-23) -->
<div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-4">
    <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-3">Piekuren <span class="text-xs text-slate-500 dark:text-slate-400">— laatste 30 dagen</span></h2>
    <div class="flex items-end gap-1 h-32">
        <?php for ($h = 0; $h < 24; $h++):
            $val = $hours[$h];
            $pct = (int) round(($val / $maxHour) * 100);
        ?>
            <div class="flex-1 flex flex-col items-center gap-1">
                <div class="w-full bg-brand rounded-t" style="height: <?= max(2, $pct) ?>%"
                     title="<?= $h ?>:00 — <?= $val ?> matches"></div>
                <span class="text-[9px] text-slate-400 tabular-nums"><?= $h % 3 === 0 ? $h : '·' ?></span>
            </div>
        <?php endfor; ?>
    </div>
</div>

<!-- Per weekdag -->
<div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-4">
    <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-3">Per weekdag <span class="text-xs text-slate-500 dark:text-slate-400">— laatste 90 dagen</span></h2>
    <div class="grid grid-cols-7 gap-2">
        <?php for ($d = 0; $d < 7; $d++):
            $val = $days[$d];
            $pct = (int) round(($val / $maxDay) * 100);
        ?>
            <div class="text-center">
                <div class="bg-slate-100 dark:bg-slate-800 rounded-lg h-24 flex flex-col justify-end overflow-hidden">
                    <div class="bg-brand" style="height: <?= max(4, $pct) ?>%"></div>
                </div>
                <p class="text-[11px] font-semibold text-navy dark:text-slate-100 mt-1"><?= $dayLabels[$d] ?></p>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 tabular-nums"><?= (int) $val ?></p>
            </div>
        <?php endfor; ?>
    </div>
</div>

<!-- Top games + tafels naast elkaar -->
<div class="grid sm:grid-cols-2 gap-3 mb-4">
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card">
        <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-3">Populairste spellen</h2>
        <?php if (empty($topGames)): ?>
            <p class="text-xs text-slate-500 dark:text-slate-400">Nog geen data.</p>
        <?php else:
            $maxGame = max(1, max(array_map(fn ($g) => (int) $g['matches'], $topGames)));
        ?>
            <ul class="space-y-2">
                <?php foreach ($topGames as $g):
                    $pct = (int) round(((int) $g['matches'] / $maxGame) * 100);
                ?>
                    <li>
                        <div class="flex items-baseline justify-between gap-2 mb-1">
                            <p class="text-sm font-semibold text-navy dark:text-slate-100 truncate"><?= e((string) $g['name']) ?></p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 tabular-nums shrink-0">
                                <?= (int) $g['matches'] ?> · <?= (int) round(((int) $g['minutes']) / 60) ?>u
                            </p>
                        </div>
                        <div class="h-1.5 rounded bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full bg-brand" style="width: <?= $pct ?>%"></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card">
        <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-3">Drukste tafels</h2>
        <?php if (empty($topDevices)): ?>
            <p class="text-xs text-slate-500 dark:text-slate-400">Nog geen apparaten met activiteit.</p>
        <?php else:
            $maxDev = max(1, max(array_map(fn ($d) => (int) $d['matches'], $topDevices)));
        ?>
            <ul class="space-y-2">
                <?php foreach ($topDevices as $d):
                    $pct = (int) round(((int) $d['matches'] / $maxDev) * 100);
                ?>
                    <li>
                        <div class="flex items-baseline justify-between gap-2 mb-1">
                            <p class="text-sm font-semibold text-navy dark:text-slate-100 truncate"><?= e((string) $d['name']) ?> <span class="text-[10px] text-slate-400 font-mono">· <?= e((string) $d['code']) ?></span></p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 tabular-nums shrink-0">
                                <?= (int) $d['matches'] ?> · <?= (int) round(((int) $d['minutes']) / 60) ?>u
                            </p>
                        </div>
                        <div class="h-1.5 rounded bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full bg-navy" style="width: <?= $pct ?>%"></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<!-- Groei: nieuwe gebruikers per week -->
<?php if (!empty($growth)): ?>
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card">
        <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-3">Nieuwe gebruikers per week</h2>
        <?php $maxG = max(1, max(array_map(fn ($r) => (int) $r['c'], $growth))); ?>
        <div class="flex items-end gap-2 h-24">
            <?php foreach ($growth as $r):
                $pct = (int) round(((int) $r['c'] / $maxG) * 100);
            ?>
                <div class="flex-1 flex flex-col items-center gap-1">
                    <div class="w-full bg-brand rounded-t" style="height: <?= max(4, $pct) ?>%"
                         title="<?= e((string) $r['week_start']) ?> — <?= (int) $r['c'] ?>"></div>
                    <span class="text-[9px] text-slate-500 dark:text-slate-400"><?= e(date('d-m', strtotime((string) $r['week_start']))) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
