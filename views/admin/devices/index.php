<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php $title = 'Apparaten'; /** @var array $devices */ ?>

<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-2xl font-bold text-navy dark:text-slate-100">Apparaten</h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm">QR-codes voor fysieke spelopstellingen.</p>
    </div>
    <a href="<?= e(url('/admin/devices/new')) ?>"
       class="inline-flex items-center px-4 py-2 rounded-lg bg-brand text-white font-semibold hover:bg-brand-dark">
        + Nieuw
    </a>
</div>

<?php if (empty($devices)): ?>
    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-8 text-center shadow-card">
        <p class="text-slate-600 dark:text-slate-300">Nog geen apparaten. Maak er één aan en print de QR-code op je tafel.</p>
    </div>
<?php else: ?>
    <ul class="space-y-2">
        <?php foreach ($devices as $d): ?>
            <li>
                <a href="<?= e(url('/admin/devices/' . $d['id'])) ?>"
                   class="flex items-center justify-between rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3 hover:bg-slate-50 shadow-card">
                    <div class="min-w-0">
                        <p class="font-semibold text-navy dark:text-slate-100 truncate"><?= e($d['name']) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            <?= e($d['game_name'] ?? 'geen spel gekoppeld') ?>
                            <?php if ($d['location']): ?> · <?= e($d['location']) ?><?php endif; ?>
                        </p>
                    </div>
                    <span class="font-mono text-sm font-bold tracking-wider text-brand-dark bg-brand-light px-2 py-1 rounded">
                        <?= e($d['code']) ?>
                    </span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<a href="<?= e(url('/admin')) ?>" class="inline-block mt-6 text-sm text-slate-500 dark:text-slate-400 hover:text-navy">← terug naar admin</a>
