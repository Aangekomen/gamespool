<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php $title = 'Admin'; /** @var array $counts */ ?>

<div class="mb-4">
    <h1 class="text-2xl font-bold text-navy">Admin</h1>
    <p class="text-slate-500 text-sm">Beheer apparaten, spellen en gebruikers.</p>
</div>

<div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-6">
    <?php foreach ([
        ['label' => 'Gebruikers', 'value' => $counts['users'],   'href' => url('/admin/users')],
        ['label' => 'Spellen',    'value' => $counts['games'],   'href' => url('/games')],
        ['label' => 'Apparaten',  'value' => $counts['devices'], 'href' => url('/admin/devices')],
        ['label' => 'Teams',      'value' => $counts['teams'],   'href' => url('/teams')],
        ['label' => 'Matches',    'value' => $counts['matches'], 'href' => url('/matches')],
    ] as $stat): ?>
        <a href="<?= e($stat['href']) ?>"
           class="block rounded-xl bg-white border border-slate-200 p-4 shadow-card hover:border-brand transition">
            <p class="text-3xl font-bold text-navy"><?= (int) $stat['value'] ?></p>
            <p class="text-xs text-slate-500 font-medium mt-1"><?= e($stat['label']) ?></p>
        </a>
    <?php endforeach; ?>
</div>

<div class="space-y-2">
    <a href="<?= e(url('/admin/devices')) ?>"
       class="flex items-center justify-between rounded-xl bg-white border border-slate-200 p-4 shadow-card hover:border-brand">
        <div>
            <p class="font-semibold text-navy">Apparaten (QR-codes)</p>
            <p class="text-xs text-slate-500">Maak QR-codes voor pooltafels, dartborden, etc.</p>
        </div>
        <span class="text-brand-dark">→</span>
    </a>
    <a href="<?= e(url('/games')) ?>"
       class="flex items-center justify-between rounded-xl bg-white border border-slate-200 p-4 shadow-card hover:border-brand">
        <div>
            <p class="font-semibold text-navy">Spellen</p>
            <p class="text-xs text-slate-500">Voeg spellen toe en stel scoresystemen in.</p>
        </div>
        <span class="text-brand-dark">→</span>
    </a>
    <a href="<?= e(url('/admin/users')) ?>"
       class="flex items-center justify-between rounded-xl bg-white border border-slate-200 p-4 shadow-card hover:border-brand">
        <div>
            <p class="font-semibold text-navy">Gebruikers</p>
            <p class="text-xs text-slate-500">Bekijk wie zich heeft geregistreerd.</p>
        </div>
        <span class="text-brand-dark">→</span>
    </a>
</div>
