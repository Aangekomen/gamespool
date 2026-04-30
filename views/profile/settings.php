<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var array $user */
/** @var array $errors */
$title = 'Instellingen';
$inputCls = 'w-full rounded-lg bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 px-3 py-2.5 text-base text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand';
?>

<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-2xl font-bold text-navy dark:text-slate-100">Instellingen</h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm">Pas je gegevens, foto, wachtwoord aan.</p>
    </div>
    <a href="<?= e(url('/profile')) ?>" class="text-sm text-slate-500 dark:text-slate-400 hover:text-navy">← profiel</a>
</div>

<!-- Edit info -->
<details open class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-3">
    <summary class="text-sm font-bold text-navy dark:text-slate-100 cursor-pointer select-none">Persoonsgegevens</summary>
    <form method="post" action="<?= e(url('/profile')) ?>" class="mt-4 space-y-3">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PATCH">
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Voornaam</label>
                <input type="text" name="first_name" required minlength="2" maxlength="80"
                       value="<?= e((string) $user['first_name']) ?>" class="<?= $inputCls ?>">
                <?php foreach (($errors['first_name'] ?? []) as $err): ?><p class="text-red-600 text-xs mt-1"><?= e($err) ?></p><?php endforeach; ?>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Achternaam</label>
                <input type="text" name="last_name" required minlength="2" maxlength="80"
                       value="<?= e((string) $user['last_name']) ?>" class="<?= $inputCls ?>">
                <?php foreach (($errors['last_name'] ?? []) as $err): ?><p class="text-red-600 text-xs mt-1"><?= e($err) ?></p><?php endforeach; ?>
            </div>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">E-mail</label>
            <input type="email" name="email" required maxlength="190"
                   value="<?= e($user['email']) ?>" class="<?= $inputCls ?>">
            <?php foreach (($errors['email'] ?? []) as $err): ?><p class="text-red-600 text-xs mt-1"><?= e($err) ?></p><?php endforeach; ?>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Bedrijf <span class="text-slate-400">(optioneel)</span></label>
            <input type="text" name="company" maxlength="150"
                   value="<?= e((string) ($user['company_name'] ?? '')) ?>" class="<?= $inputCls ?>">
        </div>
        <button class="w-full min-h-[44px] rounded-lg bg-brand text-white font-semibold hover:bg-brand-dark">Opslaan</button>
    </form>
</details>

<!-- Avatar upload -->
<details class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-3">
    <summary class="text-sm font-bold text-navy dark:text-slate-100 cursor-pointer select-none">Profielfoto</summary>
    <form method="post" action="<?= e(url('/profile/avatar')) ?>" enctype="multipart/form-data" class="mt-4 space-y-3">
        <?= csrf_field() ?>
        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required
               class="w-full text-sm text-slate-700 dark:text-slate-300 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-brand-light file:text-brand-dark file:font-semibold hover:file:bg-brand/20">
        <p class="text-xs text-slate-500 dark:text-slate-400">JPG, PNG of WebP — wordt vierkant bijgesneden naar 256×256.</p>
        <button class="w-full min-h-[44px] rounded-lg bg-brand text-white font-semibold hover:bg-brand-dark">Upload</button>
    </form>
</details>

<!-- Change password -->
<details class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-3">
    <summary class="text-sm font-bold text-navy dark:text-slate-100 cursor-pointer select-none">Wachtwoord wijzigen</summary>
    <form method="post" action="<?= e(url('/profile/password')) ?>" class="mt-4 space-y-3">
        <?= csrf_field() ?>
        <div>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Huidig wachtwoord</label>
            <input type="password" name="current_password" autocomplete="current-password" required class="<?= $inputCls ?>">
            <?php foreach (($errors['current_password'] ?? []) as $err): ?><p class="text-red-600 text-xs mt-1"><?= e($err) ?></p><?php endforeach; ?>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Nieuw wachtwoord</label>
            <input type="password" name="new_password" autocomplete="new-password" required minlength="8" class="<?= $inputCls ?>">
            <?php foreach (($errors['new_password'] ?? []) as $err): ?><p class="text-red-600 text-xs mt-1"><?= e($err) ?></p><?php endforeach; ?>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Herhaal nieuw wachtwoord</label>
            <input type="password" name="new_password_confirmation" autocomplete="new-password" required minlength="8" class="<?= $inputCls ?>">
        </div>
        <button class="w-full min-h-[44px] rounded-lg bg-brand text-white font-semibold hover:bg-brand-dark">Wijzig wachtwoord</button>
    </form>
</details>

<!-- Delete account -->
<details class="rounded-2xl bg-white dark:bg-slate-900 border border-red-200 dark:border-red-900/40 p-4 shadow-card">
    <summary class="text-sm font-bold text-red-700 cursor-pointer select-none">Account verwijderen</summary>
    <form method="post" action="<?= e(url('/profile/delete')) ?>" class="mt-4 space-y-3"
          onsubmit="return confirm('Weet je dit echt zeker? Dit kan niet ongedaan worden gemaakt.');">
        <?= csrf_field() ?>
        <p class="text-sm text-slate-600 dark:text-slate-300">
            Hiermee verwijder je je account permanent. Je matches blijven anoniem
            in de geschiedenis staan, je teams die je hebt opgericht worden óók verwijderd.
            Type <strong>VERWIJDER</strong> ter bevestiging.
        </p>
        <input type="text" name="confirm" placeholder="VERWIJDER" required
               class="<?= $inputCls ?> uppercase tracking-widest">
        <button class="w-full min-h-[44px] rounded-lg bg-red-600 text-white font-semibold hover:bg-red-700">
            Verwijder mijn account
        </button>
    </form>
</details>
