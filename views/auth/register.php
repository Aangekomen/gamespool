<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php $title = 'Account aanmaken'; /** @var array $errors */ ?>

<div class="max-w-sm mx-auto">
    <h1 class="text-2xl font-bold text-navy mb-1">Account aanmaken</h1>
    <p class="text-slate-500 text-sm mb-6">Doe mee aan competities en houd je scores bij.</p>

    <form method="post" action="<?= e(url('/register')) ?>" class="space-y-4 bg-white p-5 rounded-xl border border-slate-200 shadow-card" novalidate>
        <?= csrf_field() ?>

        <div>
            <label class="block text-sm font-medium text-navy mb-1.5" for="display_name">Naam</label>
            <input id="display_name" name="display_name" type="text" inputmode="text" autocomplete="name"
                   value="<?= e((string) old('display_name')) ?>" required minlength="2" maxlength="80"
                   class="w-full rounded-lg bg-white border border-slate-300 px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand">
            <?php foreach (($errors['display_name'] ?? []) as $err): ?>
                <p class="text-red-600 text-sm mt-1"><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>

        <div>
            <label class="block text-sm font-medium text-navy mb-1.5" for="email">E-mail</label>
            <input id="email" name="email" type="email" inputmode="email" autocomplete="email"
                   value="<?= e((string) old('email')) ?>" required maxlength="190"
                   class="w-full rounded-lg bg-white border border-slate-300 px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand">
            <?php foreach (($errors['email'] ?? []) as $err): ?>
                <p class="text-red-600 text-sm mt-1"><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>

        <div>
            <label class="block text-sm font-medium text-navy mb-1.5" for="password">Wachtwoord</label>
            <input id="password" name="password" type="password" autocomplete="new-password"
                   required minlength="8"
                   class="w-full rounded-lg bg-white border border-slate-300 px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand">
            <?php foreach (($errors['password'] ?? []) as $err): ?>
                <p class="text-red-600 text-sm mt-1"><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>

        <div>
            <label class="block text-sm font-medium text-navy mb-1.5" for="password_confirmation">Wachtwoord herhalen</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                   required minlength="8"
                   class="w-full rounded-lg bg-white border border-slate-300 px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand">
        </div>

        <button class="w-full rounded-lg bg-brand text-white font-semibold px-4 py-3 hover:bg-brand-dark">
            Account aanmaken
        </button>

        <p class="text-sm text-slate-500 text-center">
            Al een account? <a href="<?= e(url('/login')) ?>" class="text-brand-dark font-medium hover:underline">Inloggen</a>
        </p>
    </form>
</div>
