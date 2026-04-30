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

        <label class="flex items-start gap-2 text-sm text-slate-600 dark:text-slate-300 select-none">
            <input type="checkbox" name="remember" value="1" checked
                   class="mt-1 w-4 h-4 rounded border-slate-300 text-brand focus:ring-brand">
            <span>Ingelogd blijven op dit apparaat <span class="text-slate-400">(7 dagen)</span></span>
        </label>

        <button class="w-full rounded-lg bg-brand text-white font-semibold px-4 py-3 hover:bg-brand-dark">
            Inloggen
        </button>

        <p class="text-xs text-slate-500 dark:text-slate-400 text-center flex items-center justify-center gap-1">
            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 11c0-1.66 1.34-3 3-3s3 1.34 3 3v2H6v-2c0-1.66 1.34-3 3-3s3 1.34 3 3z M5 13h14v8H5z"/></svg>
            Veilig ingelogd via versleutelde verbinding
        </p>

        <p class="text-sm text-slate-500 dark:text-slate-400 text-center">
            Nog geen account? <a href="<?= e(url('/register')) ?>" class="text-brand-dark font-medium hover:underline">Account aanmaken</a>
        </p>
    </form>
</div>
