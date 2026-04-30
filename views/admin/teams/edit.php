<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var array $team */
/** @var array $members */
/** @var array $errors */
$title = 'Team bewerken';
$inputCls = 'w-full rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand';
?>

<div class="max-w-md mx-auto">
    <a href="<?= e(url('/admin/teams')) ?>" class="text-sm text-slate-500 dark:text-slate-400 hover:text-navy">← teams</a>
    <h1 class="text-2xl font-bold text-navy dark:text-slate-100 mt-2 mb-4">Team bewerken</h1>

    <form method="post" action="<?= e(url('/admin/teams/' . (int) $team['id'])) ?>"
          class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-card mb-4">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PATCH">
        <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5" for="name">Teamnaam</label>
        <input id="name" type="text" name="name" required minlength="2" maxlength="100"
               value="<?= e($team['name']) ?>" class="<?= $inputCls ?>">
        <?php foreach (($errors['name'] ?? []) as $err): ?>
            <p class="text-red-600 text-sm mt-1"><?= e($err) ?></p>
        <?php endforeach; ?>
        <button class="mt-3 w-full min-h-[44px] rounded-lg bg-brand text-white font-semibold hover:bg-brand-dark">Opslaan</button>
    </form>

    <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-card mb-4">
        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mb-1">Huidige join-code</p>
        <p class="font-mono text-2xl tracking-[0.3em] font-bold text-brand-dark"><?= e((string) $team['join_code']) ?></p>
        <form method="post" action="<?= e(url('/admin/teams/' . (int) $team['id'] . '/regenerate')) ?>"
              onsubmit="return confirm('Nieuwe code genereren? De huidige code werkt dan niet meer.');" class="mt-3">
            <?= csrf_field() ?>
            <button class="w-full min-h-[44px] rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 font-medium">
                Nieuwe code genereren
            </button>
        </form>
    </div>

    <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-card mb-4">
        <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-2"><?= count($members) ?> lid/leden</h2>
        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($members as $m):
                $name = trim(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? '')) ?: ($m['display_name'] ?? '–');
            ?>
                <li class="flex items-center justify-between py-2 text-sm">
                    <span class="text-slate-700 dark:text-slate-200 truncate"><?= e($name) ?></span>
                    <?php if ($m['role'] === 'captain'): ?>
                        <span class="text-[10px] uppercase tracking-wide text-brand-dark bg-brand-light px-1.5 py-0.5 rounded">captain</span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <form method="post" action="<?= e(url('/admin/teams/' . (int) $team['id'])) ?>"
          onsubmit="return confirm('Team \'<?= e($team['name']) ?>\' permanent verwijderen? Alle leden verliezen toegang.');"
          class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-red-200 dark:border-red-900/40 shadow-card">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="DELETE">
        <p class="text-sm text-slate-600 dark:text-slate-300 mb-3">Verwijder dit team permanent.</p>
        <button class="w-full min-h-[44px] rounded-lg bg-red-600 text-white font-semibold hover:bg-red-700">
            Verwijder team
        </button>
    </form>
</div>
