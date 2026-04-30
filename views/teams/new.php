<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php /** @var array $errors */ $title = 'Nieuw team'; ?>

<div class="max-w-md mx-auto">
    <a href="<?= e(url('/teams')) ?>" class="text-sm text-slate-500 dark:text-slate-400 hover:text-navy">← terug</a>
    <h1 class="text-2xl font-bold text-navy dark:text-slate-100 mt-2 mb-1">Maak een nieuw team aan</h1>
    <p class="text-slate-500 dark:text-slate-400 text-sm mb-4">
        Geef je team een naam. Je krijgt automatisch een unieke
        <strong class="text-navy dark:text-slate-100">6-cijferige join-code</strong> waarmee anderen
        kunnen aansluiten. Jij wordt captain en kunt nieuwe leden toelaten of weigeren.
    </p>

    <form method="post" action="<?= e(url('/teams')) ?>"
          class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-card">
        <?= csrf_field() ?>
        <label for="name" class="block text-sm font-medium text-navy dark:text-slate-100 mb-2">Teamnaam</label>
        <input id="name" type="text" name="name" autofocus required minlength="2" maxlength="100"
               value="<?= e((string) old('name')) ?>"
               placeholder="Bijv. De Ballenbazen"
               class="w-full rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand">

        <?php foreach (($errors['name'] ?? []) as $err): ?>
            <p class="text-red-600 text-sm mt-2"><?= e($err) ?></p>
        <?php endforeach; ?>

        <button class="mt-4 w-full min-h-[48px] rounded-lg bg-brand text-white font-semibold hover:bg-brand-dark">
            Team aanmaken
        </button>
    </form>
</div>
