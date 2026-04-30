<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var array $teams */
/** @var array $pendingMine */
/** @var array $pendingPerTeam */
use GamesPool\Models\Team;
$title = 'Teams';
?>

<div class="mb-4">
    <h1 class="text-2xl font-bold text-navy dark:text-slate-100">Teams</h1>
    <p class="text-slate-500 dark:text-slate-400 text-sm">Sluit aan bij een bestaand team of begin er zelf een.</p>
</div>

<!-- Two big choice cards -->
<div class="grid sm:grid-cols-2 gap-3 mb-6">
    <a href="<?= e(url('/teams/join')) ?>"
       class="block rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-card hover:border-brand transition">
        <div class="w-10 h-10 rounded-lg bg-brand-light text-brand-dark flex items-center justify-center mb-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 11l2 2-2 2m-7-2h9M5 21V5a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v3"/></svg>
        </div>
        <p class="font-bold text-navy dark:text-slate-100 text-base">Word lid van een bestaand team</p>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Vraag de captain om de 6-cijferige code en vul hem in.</p>
    </a>

    <a href="<?= e(url('/teams/new')) ?>"
       class="block rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-card hover:border-brand transition">
        <div class="w-10 h-10 rounded-lg bg-brand-light text-brand-dark flex items-center justify-center mb-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
        </div>
        <p class="font-bold text-navy dark:text-slate-100 text-base">Maak een nieuw team aan</p>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Start zelf een team — anderen sluiten aan met jouw code.</p>
    </a>
</div>

<!-- Pending requests for ME (waiting on a captain) -->
<?php if (!empty($pendingMine)): ?>
    <div class="rounded-2xl bg-amber-50 border border-amber-200 p-4 mb-4">
        <h2 class="text-sm font-bold text-amber-900 mb-2">Wacht op goedkeuring</h2>
        <ul class="space-y-1">
            <?php foreach ($pendingMine as $t): ?>
                <li class="flex items-center justify-between text-sm">
                    <span class="text-amber-900 font-medium"><?= e($t['name']) ?></span>
                    <span class="text-xs text-amber-700">verzonden <?= e(date('d-m H:i', strtotime((string) $t['requested_at']))) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- My teams -->
<div>
    <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-2 px-1">Mijn teams</h2>
    <?php if (empty($teams)): ?>
        <div class="rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-8 text-center">
            <p class="text-slate-500 dark:text-slate-400 text-sm">Je zit nog in geen enkel team.</p>
        </div>
    <?php else: ?>
        <ul class="space-y-3">
            <?php foreach ($teams as $t):
                $count = Team::memberCount((int) $t['id']);
                $isCaptain = $t['role'] === 'captain';
                $pending = $pendingPerTeam[(int) $t['id']] ?? [];
            ?>
                <li class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-card overflow-hidden">
                    <a href="<?= e(url('/teams/' . (int) $t['id'])) ?>"
                       class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/60">
                        <div class="w-10 h-10 rounded-lg bg-brand-light text-brand-dark flex items-center justify-center font-bold shrink-0 overflow-hidden">
                            <?php if (!empty($t['logo_path'])): ?>
                                <img src="<?= e(url('/uploads/logos/' . $t['logo_path'])) ?>" alt="" class="w-full h-full object-cover">
                            <?php else: ?>
                                <?= e(strtoupper(mb_substr($t['name'], 0, 1))) ?>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-navy dark:text-slate-100 truncate"><?= e($t['name']) ?>
                                <?php if ($isCaptain): ?>
                                    <span class="ml-1 text-[10px] uppercase tracking-wide text-brand-dark bg-brand-light px-1.5 py-0.5 rounded">captain</span>
                                <?php endif; ?>
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                <?= $count ?> <?= $count === 1 ? 'lid' : 'leden' ?>
                                · Code: <span class="font-mono font-semibold tracking-wider text-navy dark:text-slate-100"><?= e((string) $t['join_code']) ?></span>
                            </p>
                        </div>
                        <span class="text-slate-400 dark:text-slate-500 shrink-0">›</span>
                    </a>

                    <?php if ($isCaptain && !empty($pending)): ?>
                        <div class="bg-amber-50 border-t border-amber-200 px-4 py-3">
                            <p class="text-xs font-bold text-amber-900 mb-2">
                                <?= count($pending) ?> verzoek<?= count($pending) === 1 ? '' : 'en' ?> in afwachting
                            </p>
                            <ul class="space-y-2">
                                <?php foreach ($pending as $req): ?>
                                    <li class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-white dark:bg-slate-900 text-amber-900 flex items-center justify-center text-sm font-bold shrink-0">
                                            <?= e(strtoupper(mb_substr((string) $req['display_name'], 0, 1))) ?>
                                        </div>
                                        <p class="flex-1 text-sm text-amber-900 truncate font-medium"><?= e($req['display_name']) ?></p>
                                        <form method="post" action="<?= e(url('/teams/' . (int) $t['id'] . '/members/' . (int) $req['user_id'] . '/approve')) ?>" class="contents">
                                            <?= csrf_field() ?>
                                            <button class="min-h-[32px] text-xs px-3 rounded-md bg-brand text-white font-semibold hover:bg-brand-dark">Toelaten</button>
                                        </form>
                                        <form method="post" action="<?= e(url('/teams/' . (int) $t['id'] . '/members/' . (int) $req['user_id'] . '/reject')) ?>" class="contents">
                                            <?= csrf_field() ?>
                                            <button class="min-h-[32px] text-xs px-3 rounded-md bg-white dark:bg-slate-900 border border-amber-300 text-amber-900 hover:bg-amber-100" aria-label="Afwijzen">×</button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
