<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var array $user */
/** @var array $stats */
/** @var array $teams */
/** @var array $recentMatches */
/** @var array $streak */
/** @var array $badges */
/** @var ?array $nemesis */
/** @var ?array $favOpponent */
$title = 'Profiel';
$avatarSrc = !empty($user['avatar_path'])
    ? url('/uploads/avatars/' . $user['avatar_path']) . '?v=' . substr((string) ($user['avatar_path']), 0, 6)
    : null;
$displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['display_name'] ?: $user['email']);
?>

<!-- Header card -->
<div class="rounded-2xl bg-navy text-white p-5 shadow-card mb-4 flex items-center gap-4 relative">
    <a href="<?= e(url('/profile/settings')) ?>" aria-label="Instellingen"
       class="absolute top-3 right-3 w-9 h-9 rounded-md text-white/70 hover:text-white hover:bg-white/10 flex items-center justify-center">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="3"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.01a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.01a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
        </svg>
    </a>

    <div class="w-16 h-16 rounded-full bg-brand-light text-brand-dark flex items-center justify-center text-2xl font-bold shrink-0 overflow-hidden">
        <?php if ($avatarSrc): ?>
            <img src="<?= e($avatarSrc) ?>" alt="" class="w-full h-full object-cover">
        <?php else: ?>
            <?= e(strtoupper(mb_substr($displayName, 0, 1))) ?>
        <?php endif; ?>
    </div>
    <div class="flex-1 min-w-0 pr-10">
        <h1 class="text-xl font-bold truncate"><?= e($displayName) ?></h1>
        <p class="text-sm text-white/70 truncate"><?= e($user['email']) ?></p>
        <?php if (!empty($user['company_name'])): ?>
            <p class="text-xs text-white/50 truncate"><?= e($user['company_name']) ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Stats grid -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
    <?php foreach ([
        ['label' => 'Matches',     'value' => $stats['matches']],
        ['label' => 'Win-ratio',   'value' => $stats['win_rate'] . '%'],
        ['label' => 'Punten',      'value' => $stats['total_points']],
        ['label' => 'Rang',        'value' => $stats['rank'] !== null ? '#' . $stats['rank'] : '–'],
    ] as $s): ?>
        <div class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-3 shadow-card">
            <p class="text-2xl font-bold text-navy dark:text-slate-100 tabular-nums"><?= e((string) $s['value']) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium"><?= e($s['label']) ?></p>
        </div>
    <?php endforeach; ?>
</div>

<!-- Streak strip — alleen tonen als er momentum is -->
<?php if (($streak['count'] ?? 0) >= 2): ?>
    <?php
        $isWin = $streak['type'] === 'win';
        $box = $isWin
            ? 'bg-brand-light dark:bg-brand-dark/25 border-brand/30 dark:border-brand/40 text-brand-dark dark:text-brand-light'
            : 'bg-red-50 dark:bg-red-950/40 border-red-200 dark:border-red-900/40 text-red-700 dark:text-red-300';
        $icon = $isWin ? '🔥' : '💧';
        $label = $isWin ? 'win-streak' : 'loss-streak';
    ?>
    <div class="rounded-2xl border <?= $box ?> p-3 mb-4 flex items-center gap-3 shadow-card">
        <span class="text-2xl shrink-0"><?= $icon ?></span>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-bold">
                <?= (int) $streak['count'] ?>×
                <?= $isWin ? 'gewonnen' : 'verloren' ?> op rij
            </p>
            <p class="text-[11px] uppercase tracking-wide opacity-70"><?= $label ?> — beste ooit: <?= (int) ($streak['best_win_streak'] ?? 0) ?></p>
        </div>
    </div>
<?php endif; ?>

<!-- Win/Loss breakdown -->
<div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-4">
    <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-3">Resultaten</h2>
    <div class="grid grid-cols-3 gap-2 text-center">
        <div class="rounded-lg bg-brand-light dark:bg-brand-dark/25 text-brand-dark dark:text-brand-light py-3">
            <p class="text-xl font-bold tabular-nums"><?= (int) $stats['wins'] ?></p>
            <p class="text-[11px] uppercase tracking-wide font-semibold">Winst</p>
        </div>
        <div class="rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 py-3">
            <p class="text-xl font-bold tabular-nums"><?= (int) $stats['draws'] ?></p>
            <p class="text-[11px] uppercase tracking-wide font-semibold">Gelijk</p>
        </div>
        <div class="rounded-lg bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 py-3">
            <p class="text-xl font-bold tabular-nums"><?= (int) $stats['losses'] ?></p>
            <p class="text-[11px] uppercase tracking-wide font-semibold">Verlies</p>
        </div>
    </div>
</div>

<!-- Rivaliteit (laatste 90 dagen) -->
<?php if (!empty($nemesis) || !empty($favOpponent)): ?>
    <?php $rivalCard = function (array $opp, string $kind) {
        $isNemesis = $kind === 'nemesis';
        $emoji = $isNemesis ? '😈' : '🥷';
        $title = $isNemesis ? 'Nemesis' : 'Comfortabele tegenstander';
        $subtitle = $isNemesis
            ? 'Verslaat jou vaker dan andersom'
            : 'Tegen wie je het beste presteert';
        $box = $isNemesis
            ? 'bg-red-50 dark:bg-red-950/40 border-red-200 dark:border-red-900/40'
            : 'bg-brand-light dark:bg-brand-dark/25 border-brand/30 dark:border-brand/40';
        $accent = $isNemesis
            ? 'text-red-700 dark:text-red-300'
            : 'text-brand-dark dark:text-brand-light';
        $nameCls = $isNemesis
            ? 'text-navy dark:text-slate-100'
            : 'text-navy dark:text-white';
        $subCls = $isNemesis
            ? 'text-slate-600 dark:text-slate-300'
            : 'text-slate-700 dark:text-slate-200';
        $you = (int) $opp['your_wins'];
        $them = (int) $opp['their_wins'];
        $matches = (int) $opp['matches'];
        $name = (string) ($opp['display_name'] ?? '?');
        $avatar = !empty($opp['avatar_path']) ? url('/uploads/avatars/' . $opp['avatar_path']) : null;
    ?>
        <div class="rounded-2xl border <?= $box ?> p-3 flex items-center gap-3 shadow-card">
            <div class="w-10 h-10 rounded-full bg-white/60 dark:bg-slate-900/60 flex items-center justify-center text-lg shrink-0 overflow-hidden">
                <?php if ($avatar): ?>
                    <img src="<?= e($avatar) ?>" alt="" class="w-full h-full object-cover">
                <?php else: ?>
                    <span><?= $emoji ?></span>
                <?php endif; ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-[10px] uppercase tracking-wide font-bold <?= $accent ?>"><?= $emoji ?> <?= e($title) ?></p>
                <p class="font-bold truncate <?= $nameCls ?>"><?= e($name) ?></p>
                <p class="text-[11px] truncate <?= $subCls ?>">
                    <?= $you ?>–<?= $them ?> over <?= $matches ?> wedstrijden · <?= e($subtitle) ?>
                </p>
            </div>
        </div>
    <?php }; ?>
    <div class="grid <?= ($nemesis && $favOpponent) ? 'sm:grid-cols-2' : 'grid-cols-1' ?> gap-2 mb-4">
        <?php if (!empty($nemesis)) $rivalCard($nemesis, 'nemesis'); ?>
        <?php if (!empty($favOpponent)) $rivalCard($favOpponent, 'fav'); ?>
    </div>
<?php endif; ?>

<!-- Badges -->
<?php if (!empty($badges)):
    $earned = array_values(array_filter($badges, fn ($b) => !empty($b['earned'])));
    $upcoming = array_values(array_filter($badges, fn ($b) => empty($b['earned'])));
    // Toon de 3 dichtstbijzijnde nog-te-halen badges
    usort($upcoming, function ($a, $b) {
        $ar = ($a['progress'] ?? 0) / max(1, (int) ($a['goal'] ?? 1));
        $br = ($b['progress'] ?? 0) / max(1, (int) ($b['goal'] ?? 1));
        return $br <=> $ar;
    });
    $upcoming = array_slice($upcoming, 0, 3);
?>
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-bold text-navy dark:text-slate-100">Badges</h2>
            <span class="text-xs text-slate-500 dark:text-slate-400"><?= count($earned) ?> / <?= count($badges) ?> verdiend</span>
        </div>
        <?php if (!empty($earned)): ?>
            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2 mb-3">
                <?php foreach ($earned as $b): ?>
                    <div title="<?= e($b['description']) ?>"
                         class="rounded-xl bg-brand-light dark:bg-brand-dark/25 border border-brand/30 dark:border-brand/40 p-3 text-center">
                        <div class="text-2xl leading-none"><?= e($b['emoji']) ?></div>
                        <p class="mt-1 text-[11px] font-bold text-brand-dark dark:text-brand-light truncate"><?= e($b['label']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($upcoming)): ?>
            <p class="text-[11px] uppercase tracking-wide font-semibold text-slate-500 dark:text-slate-400 mb-2">Volgende doelen</p>
            <ul class="space-y-1.5">
                <?php foreach ($upcoming as $b):
                    $pct = (int) round((($b['progress'] ?? 0) / max(1, (int) ($b['goal'] ?? 1))) * 100);
                ?>
                    <li class="flex items-center gap-3">
                        <span class="text-lg shrink-0 grayscale opacity-60"><?= e($b['emoji']) ?></span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-baseline justify-between gap-2">
                                <p class="text-xs font-semibold text-navy dark:text-slate-100 truncate"><?= e($b['label']) ?></p>
                                <p class="text-[11px] tabular-nums text-slate-500 dark:text-slate-400 shrink-0">
                                    <?= (int) ($b['progress'] ?? 0) ?> / <?= (int) ($b['goal'] ?? 0) ?>
                                </p>
                            </div>
                            <div class="mt-1 h-1.5 rounded bg-slate-200 dark:bg-slate-700 overflow-hidden">
                                <div class="h-full bg-brand transition-all" style="width: <?= $pct ?>%"></div>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- My teams -->
<?php if (!empty($teams)): ?>
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-4">
        <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-3">Mijn teams</h2>
        <ul class="space-y-1">
            <?php foreach ($teams as $t): ?>
                <li class="flex items-center justify-between py-1">
                    <span class="text-sm text-slate-700 dark:text-slate-200"><?= e($t['name']) ?></span>
                    <span class="text-[10px] uppercase tracking-wide font-semibold <?= $t['role'] === 'captain' ? 'text-brand-dark dark:text-brand-light bg-brand-light dark:bg-brand-dark/25' : 'text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800' ?> px-2 py-0.5 rounded-full">
                        <?= e($t['role']) ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Recent matches -->
<div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-4">
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-sm font-bold text-navy dark:text-slate-100">Recente matches</h2>
        <a href="<?= e(url('/matches')) ?>" class="text-xs text-brand-dark font-semibold hover:underline">Alle →</a>
    </div>
    <?php if (empty($recentMatches)): ?>
        <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">Nog geen matches gespeeld.</p>
    <?php else: ?>
        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($recentMatches as $m): ?>
                <li>
                    <a href="<?= e(url('/matches/' . $m['id'])) ?>" class="flex items-center justify-between py-2 hover:bg-slate-50 dark:hover:bg-slate-800 -mx-2 px-2 rounded-md">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-navy dark:text-slate-100 truncate"><?= e($m['game_name']) ?><?= $m['label'] ? ' · ' . e($m['label']) : '' ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400"><?= e(date('d-m H:i', strtotime((string) $m['started_at']))) ?></p>
                        </div>
                        <span class="text-[11px] font-medium px-2 py-0.5 rounded-full
                            <?= $m['state'] === 'in_progress' ? 'bg-amber-100 text-amber-800' : ($m['state'] === 'completed' ? 'bg-brand-light dark:bg-brand-dark/25 text-brand-dark dark:text-brand-light' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300') ?>">
                            <?= $m['state'] === 'in_progress' ? 'Bezig' : ($m['state'] === 'completed' ? 'Klaar' : '×') ?>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<!-- Logout (always at bottom, red) -->
<form method="post" action="<?= e(url('/logout')) ?>" class="mt-4">
    <?= csrf_field() ?>
    <button type="submit"
            class="w-full min-h-[48px] rounded-xl bg-red-600 text-white font-semibold hover:bg-red-700">
        Uitloggen
    </button>
</form>
