<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var array $games */
/** @var array $errors */
$title = 'Nieuw toernooi';
$inputCls = 'w-full rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand';
?>

<div class="max-w-md mx-auto">
    <h1 class="text-2xl font-bold text-navy dark:text-slate-100 mb-1">Nieuw toernooi</h1>
    <p class="text-slate-500 dark:text-slate-400 text-sm mb-6">Single-elimination — winnaar gaat door, verliezer ligt eruit.</p>

    <form method="post" action="<?= e(url('/tournaments')) ?>"
          class="space-y-4 bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-card">
        <?= csrf_field() ?>

        <div>
            <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5" for="name">Naam</label>
            <input id="name" name="name" type="text" required minlength="2" maxlength="150"
                   value="<?= e((string) old('name')) ?>"
                   class="<?= $inputCls ?>"
                   placeholder="Vrijdag-cup, Pools Open, ...">
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
            <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5">Aantal spelers</label>
            <div class="grid grid-cols-4 gap-2">
                <?php foreach ([2, 4, 8, 16] as $n): ?>
                    <label class="flex items-center justify-center rounded-md border border-slate-200 dark:border-slate-700 py-2 cursor-pointer hover:border-brand has-[:checked]:bg-brand-light has-[:checked]:border-brand has-[:checked]:text-brand-dark font-semibold">
                        <input type="radio" name="max_players" value="<?= $n ?>" <?= $n === 8 ? 'checked' : '' ?> class="sr-only"> <?= $n ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Lege plekken worden bye's; die spelers gaan automatisch een ronde door.</p>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button class="flex-1 rounded-lg bg-brand text-white font-semibold px-4 py-3 hover:bg-brand-dark">Aanmaken</button>
            <a href="<?= e(url('/tournaments')) ?>" class="px-4 py-3 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">Annuleren</a>
        </div>
    </form>
</div>
