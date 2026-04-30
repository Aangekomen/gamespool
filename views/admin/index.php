<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php $title = 'Admin'; /** @var array $counts */ ?>

<div class="mb-4">
    <h1 class="text-2xl font-bold text-navy dark:text-slate-100">Admin</h1>
    <p class="text-slate-500 dark:text-slate-400 text-sm">Beheer apparaten, spellen en gebruikers.</p>
</div>

<div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-6">
    <?php foreach ([
        ['label' => 'Gebruikers', 'value' => $counts['users'],   'href' => url('/admin/users')],
        ['label' => 'Spellen',    'value' => $counts['games'],   'href' => url('/games')],
        ['label' => 'Apparaten',  'value' => $counts['devices'], 'href' => url('/admin/devices')],
        ['label' => 'Teams',      'value' => $counts['teams'],   'href' => url('/admin/teams')],
        ['label' => 'Matches',    'value' => $counts['matches'], 'href' => url('/admin/matches')],
    ] as $stat): ?>
        <a href="<?= e($stat['href']) ?>"
           class="block rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card hover:border-brand transition">
            <p class="text-3xl font-bold text-navy dark:text-slate-100"><?= (int) $stat['value'] ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-1"><?= e($stat['label']) ?></p>
        </a>
    <?php endforeach; ?>
</div>

<div class="space-y-2">
    <a href="<?= e(url('/admin/devices')) ?>"
       class="flex items-center justify-between rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card hover:border-brand">
        <div>
            <p class="font-semibold text-navy dark:text-slate-100">Apparaten (QR-codes)</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Maak QR-codes voor pooltafels, dartborden, etc.</p>
        </div>
        <span class="text-brand-dark">→</span>
    </a>
    <a href="<?= e(url('/games')) ?>"
       class="flex items-center justify-between rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card hover:border-brand">
        <div>
            <p class="font-semibold text-navy dark:text-slate-100">Spellen</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Voeg spellen toe en stel scoresystemen in.</p>
        </div>
        <span class="text-brand-dark">→</span>
    </a>
    <a href="<?= e(url('/admin/users')) ?>"
       class="flex items-center justify-between rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card hover:border-brand">
        <div>
            <p class="font-semibold text-navy dark:text-slate-100">Gebruikers</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Bekijk wie zich heeft geregistreerd.</p>
        </div>
        <span class="text-brand-dark">→</span>
    </a>
    <a href="<?= e(url('/admin/teams')) ?>"
       class="flex items-center justify-between rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card hover:border-brand">
        <div>
            <p class="font-semibold text-navy dark:text-slate-100">Teams</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Alle teams beheren — naam, code, verwijderen.</p>
        </div>
        <span class="text-brand-dark">→</span>
    </a>
    <a href="<?= e(url('/admin/matches')) ?>"
       class="flex items-center justify-between rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card hover:border-brand">
        <div>
            <p class="font-semibold text-navy dark:text-slate-100">Matches</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Match-label aanpassen of een match verwijderen.</p>
        </div>
        <span class="text-brand-dark">→</span>
    </a>
    <a href="<?= e(url('/tv')) ?>" target="_blank" rel="noopener"
       class="flex items-center justify-between rounded-xl bg-navy text-white border border-navy-soft p-4 shadow-card hover:bg-navy-soft">
        <div>
            <p class="font-semibold">📺 TV-scherm</p>
            <p class="text-xs text-white/70">Open de publieke kiosk-view (verticaal HD) in een nieuw tabblad.</p>
        </div>
        <span class="text-brand">→</span>
    </a>
</div>
