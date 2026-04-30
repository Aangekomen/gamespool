<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var array $games */
/** @var array $errors */
$title = 'Nieuwe poule';
$inputCls = 'w-full rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand';
?>

<div class="max-w-md mx-auto">
    <h1 class="text-2xl font-bold text-navy dark:text-slate-100 mb-1">Nieuwe poule</h1>
    <p class="text-slate-500 dark:text-slate-400 text-sm mb-6">
        Round-robin: alle aangemelde spelers spelen 1× tegen elkaar. 3 punten per winst, 1 voor gelijk.
        Tiebreak op doelsaldo.
    </p>

    <form method="post" action="<?= e(url('/poules')) ?>"
          class="space-y-4 bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-card">
        <?= csrf_field() ?>

        <div>
            <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5" for="name">Naam</label>
            <input id="name" name="name" type="text" required minlength="2" maxlength="150"
                   value="<?= e((string) old('name')) ?>"
                   class="<?= $inputCls ?>"
                   placeholder="Tafelvoetbal-poule, Pingpong avond, ...">
            <?php foreach (($errors['name'] ?? []) as $err): ?>
                <p class="text-red-600 text-sm mt-1"><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>

        <div>
            <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5" for="game_id">Spel</label>
            <select id="game_id" name="game_id" required class="<?= $inputCls ?>">
                <?php foreach ($games as $g): ?>
                    <option value="<?= e((string) $g['id']) ?>"><?= e($g['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php foreach (($errors['game_id'] ?? []) as $err): ?>
                <p class="text-red-600 text-sm mt-1"><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>

        <div>
            <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5" for="starts_at">
                Starttijd <span class="text-slate-400">(optioneel)</span>
            </label>
            <input id="starts_at" name="starts_at" type="datetime-local"
                   value="<?= e((string) old('starts_at')) ?>"
                   class="<?= $inputCls ?>">
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button class="flex-1 rounded-lg bg-brand text-white font-semibold px-4 py-3 hover:bg-brand-dark">Aanmaken</button>
            <a href="<?= e(url('/poules')) ?>" class="px-4 py-3 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">Annuleren</a>
        </div>
    </form>
</div>
