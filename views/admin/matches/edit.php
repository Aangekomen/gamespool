<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var array $match */
/** @var ?array $game */
/** @var array $participants */
$title = 'Match bewerken';
$inputCls = 'w-full rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand';
?>

<div class="max-w-md mx-auto">
    <a href="<?= e(url('/admin/matches')) ?>" class="text-sm text-slate-500 dark:text-slate-400 hover:text-navy">← matches</a>
    <h1 class="text-2xl font-bold text-navy dark:text-slate-100 mt-2 mb-1">Match bewerken</h1>
    <p class="text-slate-500 dark:text-slate-400 text-sm mb-4">
        <?= e($game['name'] ?? '–') ?> ·
        <?= e(date('d-m-Y H:i', strtotime((string) $match['started_at']))) ?>
    </p>

    <form method="post" action="<?= e(url('/admin/matches/' . (int) $match['id'])) ?>"
          class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-card mb-4">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PATCH">
        <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5" for="label">Label</label>
        <input id="label" type="text" name="label" maxlength="150"
               value="<?= e((string) ($match['label'] ?? '')) ?>"
               placeholder="bijv. Tafel 1"
               class="<?= $inputCls ?>">
        <button class="mt-3 w-full min-h-[44px] rounded-lg bg-brand text-white font-semibold hover:bg-brand-dark">Opslaan</button>
    </form>

    <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-card mb-4">
        <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-3">Deelnemers</h2>
        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($participants as $p): ?>
                <li class="flex items-center justify-between py-2 text-sm">
                    <span class="text-slate-700 dark:text-slate-200 truncate"><?= e($p['display_name'] ?? '–') ?></span>
                    <?php if ($p['result']): ?>
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full
                            <?= $p['result'] === 'win' ? 'bg-brand-light text-brand-dark' : ($p['result'] === 'draw' ? 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' : 'bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300') ?>">
                            <?= match ($p['result']) { 'win' => 'Winst', 'draw' => 'Gelijk', 'loss' => 'Verlies', default => '' } ?>
                            <?php if ($p['raw_score'] !== null): ?> · <?= e((string) $p['raw_score']) ?><?php endif; ?>
                        </span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <a href="<?= e(url('/matches/' . (int) $match['id'])) ?>"
           class="block mt-3 text-center text-xs text-brand-dark font-semibold hover:underline">Bekijk publieke match-pagina →</a>
    </div>

    <form method="post" action="<?= e(url('/admin/matches/' . (int) $match['id'])) ?>"
          onsubmit="return confirm('Match permanent verwijderen? Resultaten en punten zijn weg.');"
          class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-red-200 dark:border-red-900/40 shadow-card">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="DELETE">
        <p class="text-sm text-slate-600 dark:text-slate-300 mb-3">Verwijder deze match permanent.</p>
        <button class="w-full min-h-[44px] rounded-lg bg-red-600 text-white font-semibold hover:bg-red-700">
            Verwijder match
        </button>
    </form>
</div>
