<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var array $user */
/** @var array $stats */
/** @var array $teams */
/** @var array $recentMatches */
/** @var array $errors */
$title = 'Profiel';
$inputCls = 'w-full rounded-lg bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 px-3 py-2.5 text-base text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand';
$avatarSrc = !empty($user['avatar_path'])
    ? url('/uploads/avatars/' . $user['avatar_path']) . '?v=' . substr((string) ($user['avatar_path']), 0, 6)
    : null;
$displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['display_name'] ?: $user['email']);
?>

<!-- Header card -->
<div class="rounded-2xl bg-navy text-white p-5 shadow-card mb-4 flex items-center gap-4">
    <div class="w-16 h-16 rounded-full bg-brand-light text-brand-dark flex items-center justify-center text-2xl font-bold shrink-0 overflow-hidden">
        <?php if ($avatarSrc): ?>
            <img src="<?= e($avatarSrc) ?>" alt="" class="w-full h-full object-cover">
        <?php else: ?>
            <?= e(strtoupper(mb_substr($displayName, 0, 1))) ?>
        <?php endif; ?>
    </div>
    <div class="flex-1 min-w-0">
        <h1 class="text-xl font-bold truncate"><?= e($displayName) ?></h1>
        <p class="text-sm text-white/70 truncate"><?= e($user['email']) ?></p>
        <?php if (!empty($user['company_name'])): ?>
            <p class="text-xs text-white/50 truncate"><?= e($user['company_name']) ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Stats grid -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
    <?php foreach ([
        ['label' => 'Matches',     'value' => $stats['matches']],
        ['label' => 'Win-ratio',   'value' => $stats['win_rate'] . '%'],
        ['label' => 'Punten',      'value' => $stats['total_points']],
        ['label' => 'Rang',        'value' => $stats['rank'] !== null ? '#' . $stats['rank'] : '–'],
    ] as $s): ?>
        <div class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-3 shadow-card">
            <p class="text-2xl font-bold text-navy dark:text-slate-100 tabular-nums"><?= e((string) $s['value']) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium"><?= e($s['label']) ?></p>
        </div>
    <?php endforeach; ?>
</div>

<!-- My teams -->
<?php if (!empty($teams)): ?>
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-4">
        <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-3">Mijn teams</h2>
        <ul class="space-y-1">
            <?php foreach ($teams as $t): ?>
                <li class="flex items-center justify-between py-1">
                    <span class="text-sm text-slate-700 dark:text-slate-200"><?= e($t['name']) ?></span>
                    <span class="text-[10px] uppercase tracking-wide font-semibold <?= $t['role'] === 'captain' ? 'text-brand-dark bg-brand-light' : 'text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800' ?> px-2 py-0.5 rounded-full">
                        <?= e($t['role']) ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Recent matches -->
<div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-4">
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-sm font-bold text-navy dark:text-slate-100">Recente matches</h2>
        <a href="<?= e(url('/matches')) ?>" class="text-xs text-brand-dark font-semibold hover:underline">Alle →</a>
    </div>
    <?php if (empty($recentMatches)): ?>
        <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">Nog geen matches gespeeld.</p>
    <?php else: ?>
        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($recentMatches as $m): ?>
                <li>
                    <a href="<?= e(url('/matches/' . $m['id'])) ?>" class="flex items-center justify-between py-2 hover:bg-slate-50 dark:hover:bg-slate-800 -mx-2 px-2 rounded-md">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-navy dark:text-slate-100 truncate"><?= e($m['game_name']) ?><?= $m['label'] ? ' · ' . e($m['label']) : '' ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400"><?= e(date('d-m H:i', strtotime((string) $m['started_at']))) ?></p>
                        </div>
                        <span class="text-[11px] font-medium px-2 py-0.5 rounded-full
                            <?= $m['state'] === 'in_progress' ? 'bg-amber-100 text-amber-800' : ($m['state'] === 'completed' ? 'bg-brand-light text-brand-dark' : 'bg-slate-100 text-slate-600') ?>">
                            <?= $m['state'] === 'in_progress' ? 'Bezig' : ($m['state'] === 'completed' ? 'Klaar' : '×') ?>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<!-- Edit info -->
<details class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-3">
    <summary class="text-sm font-bold text-navy dark:text-slate-100 cursor-pointer select-none">Persoonsgegevens bewerken</summary>
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
    <summary class="text-sm font-bold text-navy dark:text-slate-100 cursor-pointer select-none">Profielfoto wijzigen</summary>
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
