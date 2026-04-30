<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php $title = 'Account aanmaken'; /** @var array $errors */ ?>

<div class="max-w-sm mx-auto">
    <h1 class="text-2xl font-bold mb-1">Account aanmaken</h1>
    <p class="text-slate-400 text-sm mb-6">Doe mee aan competities en houd je scores bij.</p>

    <form method="post" action="<?= e(url('/register')) ?>" class="space-y-4" novalidate>
        <?= csrf_field() ?>

        <div>
            <label class="block text-sm font-medium mb-1.5" for="display_name">Naam</label>
            <input id="display_name" name="display_name" type="text" inputmode="text" autocomplete="name"
                   value="<?= e((string) old('display_name')) ?>" required minlength="2" maxlength="80"
                   class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <?php foreach (($errors['display_name'] ?? []) as $err): ?>
                <p class="text-red-400 text-sm mt-1"><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1.5" for="email">E-mail</label>
            <input id="email" name="email" type="email" inputmode="email" autocomplete="email"
                   value="<?= e((string) old('email')) ?>" required maxlength="190"
                   class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <?php foreach (($errors['email'] ?? []) as $err): ?>
                <p class="text-red-400 text-sm mt-1"><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1.5" for="password">Wachtwoord</label>
            <input id="password" name="password" type="password" autocomplete="new-password"
                   required minlength="8"
                   class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <?php foreach (($errors['password'] ?? []) as $err): ?>
                <p class="text-red-400 text-sm mt-1"><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1.5" for="password_confirmation">Wachtwoord herhalen</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                   required minlength="8"
                   class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-emerald-500">
        </div>

        <button class="w-full rounded-lg bg-emerald-500 text-slate-950 font-semibold px-4 py-3 hover:bg-emerald-400 active:bg-emerald-400">
            Account aanmaken
        </button>

        <p class="text-sm text-slate-400 text-center">
            Al een account? <a href="<?= e(url('/login')) ?>" class="text-emerald-400 hover:underline">Inloggen</a>
        </p>
    </form>
</div>
