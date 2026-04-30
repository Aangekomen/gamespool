<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var array $poule */
/** @var ?array $game */
/** @var array $participants */
/** @var bool $isParticipant */
/** @var array $standings */
/** @var array $matches */
/** @var int $remaining */
$title = $poule['name'];
$canStart = $poule['state'] === 'open' && count($participants) >= 2 && \GamesPool\Core\Admin::is();
$totalMatches = count($participants) * (count($participants) - 1) / 2;
?>

<div class="flex items-start justify-between mb-2 gap-3">
    <div class="min-w-0">
        <h1 class="text-2xl font-bold text-navy dark:text-slate-100"><?= e((string) $poule['name']) ?></h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm">
            <?= e((string) ($game['name'] ?? '?')) ?>
            <?php if (!empty($poule['starts_at'])): ?>
                · 🗓️ <?= e(date('d-m-Y H:i', strtotime((string) $poule['starts_at']))) ?>
            <?php endif; ?>
            <?php if ($poule['state'] === 'running'): ?>
                · <?= (int) $remaining ?> wedstrijden te gaan
            <?php endif; ?>
        </p>
    </div>
    <a href="<?= e(url('/poules')) ?>" class="text-sm text-slate-500 dark:text-slate-400 hover:text-navy">← lijst</a>
</div>

<!-- Open: aanmelden -->
<?php if ($poule['state'] === 'open'): ?>
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-4">
        <p class="text-sm font-bold text-navy dark:text-slate-100 mb-2">
            Spelers (<?= count($participants) ?>)
        </p>
        <?php if (empty($participants)): ?>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Nog niemand aangemeld.</p>
        <?php else: ?>
            <ul class="grid grid-cols-2 gap-1.5 mb-3">
                <?php foreach ($participants as $p): ?>
                    <li class="flex items-center gap-2 rounded-md bg-surface dark:bg-slate-950 px-2 py-1.5">
                        <div class="w-7 h-7 rounded-full bg-brand-light dark:bg-brand-dark/25 text-brand-dark dark:text-brand-light flex items-center justify-center text-xs font-bold overflow-hidden shrink-0">
                            <?php if (!empty($p['avatar_path'])): ?>
                                <img src="<?= e(url('/uploads/avatars/' . $p['avatar_path'])) ?>" alt="" class="w-full h-full object-cover">
                            <?php else: ?>
                                <?= e(strtoupper(mb_substr((string) $p['display_name'], 0, 1))) ?>
                            <?php endif; ?>
                        </div>
                        <span class="text-xs font-semibold text-navy dark:text-slate-100 truncate"><?= e((string) $p['display_name']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <div class="grid grid-cols-1 gap-2">
            <?php if (!$isParticipant): ?>
                <form method="post" action="<?= e(url('/poules/' . (int) $poule['id'] . '/register')) ?>">
                    <?= csrf_field() ?>
                    <button class="w-full rounded-lg bg-brand text-white font-semibold py-2.5 hover:bg-brand-dark">Aanmelden</button>
                </form>
            <?php else: ?>
                <form method="post" action="<?= e(url('/poules/' . (int) $poule['id'] . '/leave')) ?>">
                    <?= csrf_field() ?>
                    <button class="w-full rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-red-50 hover:text-red-700 text-slate-600 dark:text-slate-300 font-semibold py-2.5">
                        Aanmelding intrekken
                    </button>
                </form>
            <?php endif; ?>
            <?php if ($canStart): ?>
                <form method="post" action="<?= e(url('/poules/' . (int) $poule['id'] . '/start')) ?>"
                      onsubmit="return confirm('Poule starten? Dit maakt <?= (int) $totalMatches ?> wedstrijden aan.');">
                    <?= csrf_field() ?>
                    <button class="w-full rounded-lg bg-navy text-white font-semibold py-2.5 hover:bg-navy-soft">
                        ▶ Poule starten (<?= (int) $totalMatches ?> wedstrijden)
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Stand -->
<?php if (!empty($standings)): ?>
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-card overflow-hidden mb-4">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h2 class="text-sm font-bold text-navy dark:text-slate-100">Stand</h2>
            <span class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400 font-bold">3 / 1 / 0 punten</span>
        </div>
        <table class="w-full text-sm">
            <thead class="text-[10px] uppercase tracking-wider text-slate-500 dark:text-slate-400 bg-surface dark:bg-slate-950">
                <tr>
                    <th class="text-left  font-semibold px-2 py-2">#</th>
                    <th class="text-left  font-semibold px-2 py-2">Speler</th>
                    <th class="text-right font-semibold px-2 py-2 tabular-nums">G</th>
                    <th class="text-right font-semibold px-2 py-2 tabular-nums">W</th>
                    <th class="text-right font-semibold px-2 py-2 tabular-nums">G</th>
                    <th class="text-right font-semibold px-2 py-2 tabular-nums">V</th>
                    <th class="text-right font-semibold px-2 py-2 tabular-nums hidden sm:table-cell">+/-</th>
                    <th class="text-right font-semibold px-2 py-2 tabular-nums">Pt</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <?php foreach ($standings as $i => $s):
                    $diff = (int) $s['goals_for'] - (int) $s['goals_against'];
                    $rankClr = $i === 0 ? 'text-amber-500' : ($i === 1 ? 'text-slate-400' : ($i === 2 ? 'text-amber-700' : 'text-slate-400'));
                ?>
                    <tr>
                        <td class="px-2 py-2 font-bold tabular-nums <?= $rankClr ?>"><?= $i + 1 ?></td>
                        <td class="px-2 py-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <div class="w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xs font-bold overflow-hidden shrink-0">
                                    <?php if (!empty($s['avatar_path'])): ?>
                                        <img src="<?= e(url('/uploads/avatars/' . $s['avatar_path'])) ?>" alt="" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <?= e(strtoupper(mb_substr((string) $s['display_name'], 0, 1))) ?>
                                    <?php endif; ?>
                                </div>
                                <span class="font-semibold text-navy dark:text-slate-100 truncate"><?= e((string) $s['display_name']) ?></span>
                            </div>
                        </td>
                        <td class="px-2 py-2 text-right tabular-nums text-slate-600 dark:text-slate-300"><?= (int) $s['played'] ?></td>
                        <td class="px-2 py-2 text-right tabular-nums text-brand-dark dark:text-brand-light font-semibold"><?= (int) $s['wins'] ?></td>
                        <td class="px-2 py-2 text-right tabular-nums text-slate-600 dark:text-slate-300"><?= (int) $s['draws'] ?></td>
                        <td class="px-2 py-2 text-right tabular-nums text-red-700 dark:text-red-400"><?= (int) $s['losses'] ?></td>
                        <td class="px-2 py-2 text-right tabular-nums text-slate-600 dark:text-slate-300 hidden sm:table-cell">
                            <?= ($diff > 0 ? '+' : '') . $diff ?>
                        </td>
                        <td class="px-2 py-2 text-right text-base font-extrabold tabular-nums text-navy dark:text-slate-100"><?= (int) $s['points'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Wedstrijdenlijst -->
<?php if (!empty($matches)): ?>
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card">
        <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-3">Wedstrijden</h2>
        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($matches as $m):
                $players = [];
                foreach (array_filter(explode('|', (string) $m['players'])) as $part) {
                    $bits = explode(':', $part, 4);
                    $players[] = ['name' => $bits[1] ?? '?', 'result' => $bits[2] ?? '', 'score' => $bits[3] ?? ''];
                }
                $stateBadge = match ($m['state']) {
                    'completed' => ['Klaar',  'bg-brand-light dark:bg-brand-dark/25 text-brand-dark dark:text-brand-light'],
                    'cancelled' => ['×',      'bg-slate-100 dark:bg-slate-800 text-slate-500'],
                    default     => ['Bezig',  'bg-amber-100 text-amber-800'],
                };
            ?>
                <li>
                    <a href="<?= e(url('/matches/' . (int) $m['id'])) ?>"
                       class="flex items-center justify-between gap-2 py-2 hover:bg-slate-50 dark:hover:bg-slate-800/40 -mx-2 px-2 rounded-md">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-navy dark:text-slate-100 truncate">
                                <?php foreach ($players as $i => $pl):
                                    $cls = $pl['result'] === 'win' ? 'text-brand-dark dark:text-brand-light font-bold' : '';
                                ?><span class="<?= $cls ?>"><?= e($pl['name']) ?></span><?php
                                    if ($pl['score'] !== '') echo ' <span class="tabular-nums">' . e($pl['score']) . '</span>';
                                    if ($i < count($players) - 1) echo ' <span class="text-slate-400">vs</span> ';
                                endforeach; ?>
                            </p>
                        </div>
                        <span class="shrink-0 text-[11px] font-medium px-2 py-0.5 rounded-full <?= $stateBadge[1] ?>">
                            <?= e($stateBadge[0]) ?>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
