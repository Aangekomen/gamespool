<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var array $device */
/** @var ?array $game */
/** @var string $qrUrl */
$title = $device['name'];
?>

<div class="max-w-md mx-auto">
    <div class="rounded-2xl bg-navy text-white p-5 shadow-card">
        <p class="text-xs uppercase tracking-wide text-white/60 font-semibold">Apparaat</p>
        <h1 class="text-2xl font-bold"><?= e($device['name']) ?></h1>
        <p class="text-sm text-white/70 mt-0.5">
            <?= e($game['name'] ?? 'geen spel gekoppeld') ?>
            <?php if ($device['location']): ?> · <?= e($device['location']) ?><?php endif; ?>
        </p>
        <p class="mt-3 font-mono text-2xl tracking-[0.3em] font-bold text-brand">
            <?= e($device['code']) ?>
        </p>
    </div>

    <div class="mt-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-card text-center">
        <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-3">QR-code</h2>
        <div class="bg-white dark:bg-slate-900 inline-block rounded-xl border border-slate-200 dark:border-slate-800 p-2">
            <img src="<?= e(url('/qr.svg?text=' . urlencode($qrUrl) . '&size=240')) ?>"
                 alt="QR voor <?= e($device['name']) ?>" width="240" height="240" class="block">
        </div>
        <p class="mt-3 text-xs font-mono text-slate-500 dark:text-slate-400 break-all"><?= e($qrUrl) ?></p>
        <a href="<?= e(url('/admin/devices/' . $device['id'] . '/print')) ?>" target="_blank"
           class="inline-block mt-4 px-4 py-2 rounded-lg bg-brand text-white font-semibold hover:bg-brand-dark">
            🖨 Printvriendelijk openen
        </a>
    </div>

    <div class="grid grid-cols-2 gap-2 mt-3">
        <a href="<?= e(url('/admin/devices/' . $device['id'] . '/edit')) ?>"
           class="text-center rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-4 py-2.5 text-navy dark:text-slate-100 hover:bg-slate-50">Bewerken</a>
        <form method="post" action="<?= e(url('/admin/devices/' . $device['id'])) ?>"
              onsubmit="return confirm('Apparaat verwijderen?');">
            <?= csrf_field() ?>
            <input type="hidden" name="_method" value="DELETE">
            <button class="w-full rounded-lg bg-red-50 hover:bg-red-100 text-red-700 px-4 py-2.5 font-medium">Verwijder</button>
        </form>
    </div>

    <a href="<?= e(url('/admin/devices')) ?>" class="inline-block mt-6 text-sm text-slate-500 dark:text-slate-400 hover:text-navy">← terug</a>
</div>
