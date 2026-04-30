<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php $title = 'Account aanmaken'; /** @var array $errors */
$inputCls = 'w-full rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand';
?>

<div class="max-w-md mx-auto">
    <h1 class="text-2xl font-bold text-navy dark:text-slate-100 mb-1">Account aanmaken</h1>
    <p class="text-slate-500 dark:text-slate-400 text-sm mb-4">Doe mee aan competities en houd je scores bij. Gratis en in 30 seconden klaar.</p>

    <div class="mb-4 rounded-lg bg-brand-light border border-brand/30 px-3 py-2.5 text-brand-dark text-xs flex items-start gap-2">
        <svg class="w-4 h-4 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 11c0-1.66 1.34-3 3-3s3 1.34 3 3v2H6v-2c0-1.66 1.34-3 3-3s3 1.34 3 3z M5 13h14v8H5z"/>
        </svg>
        <span>
            <strong>Veilig opgeslagen.</strong> Je wachtwoord wordt versleuteld bewaard (bcrypt) en alleen via HTTPS verzonden. We delen je gegevens nooit.
        </span>
    </div>

    <form method="post" action="<?= e(url('/register')) ?>" class="space-y-4 bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-card" novalidate>
        <?= csrf_field() ?>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5" for="first_name">Voornaam</label>
                <input id="first_name" name="first_name" type="text" autocomplete="given-name"
                       value="<?= e((string) old('first_name')) ?>" required minlength="2" maxlength="80"
                       class="<?= $inputCls ?>">
                <?php foreach (($errors['first_name'] ?? []) as $err): ?>
                    <p class="text-red-600 text-sm mt-1"><?= e($err) ?></p>
                <?php endforeach; ?>
            </div>
            <div>
                <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5" for="last_name">Achternaam</label>
                <input id="last_name" name="last_name" type="text" autocomplete="family-name"
                       value="<?= e((string) old('last_name')) ?>" required minlength="2" maxlength="80"
                       class="<?= $inputCls ?>">
                <?php foreach (($errors['last_name'] ?? []) as $err): ?>
                    <p class="text-red-600 text-sm mt-1"><?= e($err) ?></p>
                <?php endforeach; ?>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5" for="email">E-mail</label>
            <input id="email" name="email" type="email" inputmode="email" autocomplete="email"
                   value="<?= e((string) old('email')) ?>" required maxlength="190"
                   class="<?= $inputCls ?>">
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">We sturen een bevestigingslink naar dit adres.</p>
            <?php foreach (($errors['email'] ?? []) as $err): ?>
                <p class="text-red-600 text-sm mt-1"><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>

        <div>
            <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5" for="company">
                Bedrijfsnaam <span class="text-slate-400">(optioneel)</span>
            </label>
            <input id="company" name="company" type="text" autocomplete="organization" maxlength="150"
                   value="<?= e((string) old('company')) ?>"
                   class="<?= $inputCls ?>">
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Bestaat het bedrijf al, dan word je aan dezelfde organisatie gekoppeld.</p>
            <?php foreach (($errors['company'] ?? []) as $err): ?>
                <p class="text-red-600 text-sm mt-1"><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>

        <div>
            <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5" for="password">Wachtwoord</label>
            <input id="password" name="password" type="password" autocomplete="new-password"
                   required minlength="8"
                   class="<?= $inputCls ?>">
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Minimaal <strong>8 tekens</strong>. Tip: gebruik een zin of een wachtwoordmanager.</p>
            <?php foreach (($errors['password'] ?? []) as $err): ?>
                <p class="text-red-600 text-sm mt-1"><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>

        <div>
            <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5" for="password_confirmation">Wachtwoord herhalen</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                   required minlength="8"
                   class="<?= $inputCls ?>">
        </div>

        <label class="flex items-start gap-2 text-sm text-slate-600 dark:text-slate-300 select-none">
            <input type="checkbox" name="remember" value="1" checked
                   class="mt-1 w-4 h-4 rounded border-slate-300 text-brand focus:ring-brand">
            <span>Ingelogd blijven op dit apparaat <span class="text-slate-400">(7 dagen)</span></span>
        </label>

        <button class="w-full rounded-lg bg-brand text-white font-semibold px-4 py-3 hover:bg-brand-dark">
            Account aanmaken
        </button>

        <p class="text-sm text-slate-500 dark:text-slate-400 text-center">
            Al een account? <a href="<?= e(url('/login')) ?>" class="text-brand-dark font-medium hover:underline">Inloggen</a>
        </p>
    </form>
</div>
