<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php $title = 'Matches (admin)'; /** @var array $matches */ ?>

<div class="mb-4 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-navy dark:text-slate-100">Matches</h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm"><?= count($matches) ?> recent.</p>
    </div>
    <a href="<?= e(url('/admin')) ?>" class="text-sm text-slate-500 dark:text-slate-400 hover:text-navy">← admin</a>
</div>

<?php if (empty($matches)): ?>
    <p class="text-slate-500 dark:text-slate-400 text-center py-8">Nog geen matches gespeeld.</p>
<?php else: ?>
    <ul class="space-y-2">
        <?php foreach ($matches as $m): ?>
            <li>
                <a href="<?= e(url('/admin/matches/' . (int) $m['id'] . '/edit')) ?>"
                   class="block rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/60 shadow-card">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-navy dark:text-slate-100 truncate">
                                <?= e($m['game_name']) ?><?= $m['label'] ? ' — ' . e($m['label']) : '' ?>
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                <?= e(date('d-m-Y H:i', strtotime((string) $m['started_at']))) ?>
                                · <?= (int) $m['participant_count'] ?> deelnemers
                            </p>
                        </div>
                        <span class="shrink-0 text-[11px] font-medium px-2 py-0.5 rounded-full
                            <?= $m['state'] === 'in_progress' ? 'bg-amber-100 text-amber-800' : ($m['state'] === 'completed' ? 'bg-brand-light text-brand-dark' : ($m['state'] === 'waiting' ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-600')) ?>">
                            <?= match ($m['state']) { 'waiting' => 'Wacht', 'in_progress' => 'Bezig', 'completed' => 'Klaar', 'cancelled' => '×', default => $m['state'] } ?>
                        </span>
                    </div>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
