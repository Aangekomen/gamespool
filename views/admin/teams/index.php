<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php $title = 'Teams (admin)'; /** @var array $teams */ ?>

<div class="mb-4 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-navy dark:text-slate-100">Teams</h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm"><?= count($teams) ?> teams in totaal.</p>
    </div>
    <a href="<?= e(url('/admin')) ?>" class="text-sm text-slate-500 dark:text-slate-400 hover:text-navy">← admin</a>
</div>

<?php if (empty($teams)): ?>
    <p class="text-slate-500 dark:text-slate-400 text-center py-8">Nog geen teams aangemaakt.</p>
<?php else: ?>
    <ul class="space-y-2">
        <?php foreach ($teams as $t): ?>
            <li class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-card">
                <a href="<?= e(url('/admin/teams/' . (int) $t['id'] . '/edit')) ?>"
                   class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/60">
                    <div class="w-10 h-10 rounded-lg bg-brand-light text-brand-dark flex items-center justify-center font-bold shrink-0 overflow-hidden">
                        <?php if (!empty($t['logo_path'])): ?>
                            <img src="<?= e(url('/uploads/logos/' . $t['logo_path'])) ?>" alt="" class="w-full h-full object-cover">
                        <?php else: ?>
                            <?= e(strtoupper(mb_substr($t['name'], 0, 1))) ?>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-navy dark:text-slate-100 truncate"><?= e($t['name']) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            <?= (int) $t['member_count'] ?> leden
                            <?php if ((int) $t['pending_count'] > 0): ?>
                                · <span class="text-amber-700"><?= (int) $t['pending_count'] ?> wacht</span>
                            <?php endif; ?>
                            <?php if (!empty($t['captain_name'])): ?>
                                · captain: <?= e($t['captain_name']) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <span class="font-mono text-xs font-bold tracking-wider text-brand-dark bg-brand-light px-2 py-1 rounded shrink-0">
                        <?= e((string) $t['join_code']) ?>
                    </span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
