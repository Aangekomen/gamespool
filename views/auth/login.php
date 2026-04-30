<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php $title = 'Inloggen'; /** @var array $errors */ ?>

<div class="max-w-sm mx-auto">
    <h1 class="text-2xl font-bold text-navy dark:text-slate-100 mb-1">Inloggen</h1>
    <p class="text-slate-500 dark:text-slate-400 text-sm mb-6">Welkom terug.</p>

    <form method="post" action="<?= e(url('/login')) ?>" class="space-y-4 bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-card" novalidate>
        <?= csrf_field() ?>

        <div>
            <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5" for="email">E-mail</label>
            <input id="email" name="email" type="email" inputmode="email" autocomplete="email"
                   value="<?= e((string) old('email')) ?>" required
                   class="w-full rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand">
            <?php foreach (($errors['email'] ?? []) as $err): ?>
                <p class="text-red-600 text-sm mt-1"><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>

        <div>
            <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5" for="password">Wachtwoord</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required
                   class="w-full rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand">
        </div>

        <button class="w-full rounded-lg bg-brand text-white font-semibold px-4 py-3 hover:bg-brand-dark">
            Inloggen
        </button>

        <p class="text-sm text-slate-500 dark:text-slate-400 text-center">
            Nog geen account? <a href="<?= e(url('/register')) ?>" class="text-brand-dark font-medium hover:underline">Aanmaken</a>
        </p>
    </form>
</div>
