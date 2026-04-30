<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php $title = 'Matches'; /** @var array $matches */ ?>

<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-2xl font-bold">Matches</h1>
        <p class="text-slate-400 text-sm">Recent gespeelde en lopende potjes.</p>
    </div>
    <a href="<?= e(url('/matches/new')) ?>"
       class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-500 text-slate-950 font-semibold hover:bg-emerald-400">
        + Nieuwe match
    </a>
</div>

<?php if (empty($matches)): ?>
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-8 text-center">
        <p class="text-slate-300">Nog geen matches gespeeld.</p>
        <a href="<?= e(url('/matches/new')) ?>" class="inline-block mt-4 px-4 py-2 rounded-lg bg-emerald-500 text-slate-950 font-semibold">Start je eerste match</a>
    </div>
<?php else: ?>
    <ul class="space-y-2">
        <?php foreach ($matches as $m): ?>
            <li>
                <a href="<?= e(url('/matches/' . $m['id'])) ?>"
                   class="block rounded-xl border border-slate-800 bg-slate-900 px-4 py-3 hover:bg-slate-800/70">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold truncate"><?= e($m['game_name']) ?><?= $m['label'] ? ' — ' . e($m['label']) : '' ?></p>
                            <p class="text-xs text-slate-400">
                                <?= e(date('d-m-Y H:i', strtotime((string) $m['started_at']))) ?>
                            </p>
                        </div>
                        <span class="shrink-0 text-xs px-2 py-1 rounded-full
                            <?= $m['state'] === 'in_progress' ? 'bg-amber-500/20 text-amber-300' : ($m['state'] === 'completed' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-700 text-slate-300') ?>">
                            <?= $m['state'] === 'in_progress' ? 'Bezig' : ($m['state'] === 'completed' ? 'Afgerond' : 'Geannuleerd') ?>
                        </span>
                    </div>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
