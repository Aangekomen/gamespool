<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var string $token */
/** @var bool $valid */
/** @var array $errors */
$title = 'Nieuw wachtwoord instellen';
$inputCls = 'w-full rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand';
?>

<div class="max-w-sm mx-auto">
    <h1 class="text-2xl font-bold text-navy dark:text-slate-100 mb-1">Nieuw wachtwoord</h1>
    <p class="text-slate-500 dark:text-slate-400 text-sm mb-6">
        <?= $valid ? 'Stel een nieuw wachtwoord in.' : 'Deze resetlink is ongeldig of verlopen.' ?>
    </p>

    <?php if ($valid): ?>
        <form method="post" action="<?= e(url('/password/reset/' . $token)) ?>"
              class="space-y-4 bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-card">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5" for="new_password">Nieuw wachtwoord</label>
                <input id="new_password" name="new_password" type="password" autocomplete="new-password"
                       required minlength="8" class="<?= $inputCls ?>">
                <?php foreach (($errors['new_password'] ?? []) as $err): ?>
                    <p class="text-red-600 text-sm mt-1"><?= e($err) ?></p>
                <?php endforeach; ?>
            </div>
            <div>
                <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5" for="new_password_confirmation">Herhaal wachtwoord</label>
                <input id="new_password_confirmation" name="new_password_confirmation" type="password" autocomplete="new-password"
                       required minlength="8" class="<?= $inputCls ?>">
            </div>
            <button class="w-full min-h-[48px] rounded-lg bg-brand text-white font-semibold hover:bg-brand-dark">
                Opslaan
            </button>
        </form>
    <?php else: ?>
        <a href="<?= e(url('/login')) ?>" class="block text-center text-sm text-brand-dark font-semibold hover:underline">← naar inloggen</a>
    <?php endif; ?>
</div>
