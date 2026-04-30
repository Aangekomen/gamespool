<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var array $user */
/** @var array $stats */
/** @var array $teams */
/** @var array $recentMatches */
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

<!-- Win/Loss breakdown -->
<div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-4">
    <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-3">Resultaten</h2>
    <div class="grid grid-cols-3 gap-2 text-center">
        <div class="rounded-lg bg-brand-light text-brand-dark py-3">
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

<!-- My teams -->
<?php if (!empty($teams)): ?>
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-4">
        <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-3">Mijn teams</h2>
        <ul class="space-y-1">
            <?php foreach ($teams as $t): ?>
                <li class="flex items-center justify-between py-1">
                    <span class="text-sm text-slate-700 dark:text-slate-200"><?= e($t['name']) ?></span>
                    <span class="text-[10px] uppercase tracking-wide font-semibold <?= $t['role'] === 'captain' ? 'text-brand-dark bg-brand-light' : 'text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800' ?> px-2 py-0.5 rounded-full">
                        <?= e($t['role']) ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Logout -->
<form method="post" action="<?= e(url('/logout')) ?>" class="mb-4">
    <?= csrf_field() ?>
    <button type="submit"
            class="w-full min-h-[48px] rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 font-semibold hover:bg-red-50 hover:text-red-700 hover:border-red-200">
        Uitloggen
    </button>
</form>

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
                            <?= $m['state'] === 'in_progress' ? 'bg-amber-100 text-amber-800' : ($m['state'] === 'completed' ? 'bg-brand-light text-brand-dark' : 'bg-slate-100 text-slate-600') ?>">
                            <?= $m['state'] === 'in_progress' ? 'Bezig' : ($m['state'] === 'completed' ? 'Klaar' : '×') ?>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
