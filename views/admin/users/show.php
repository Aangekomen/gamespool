<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var array $user */
/** @var array $teams */
/** @var array $availableTeams */
$name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['display_name'] ?: $user['email']);
$title = $name;
$isMe = (int) (\GamesPool\Core\Auth::id() ?? 0) === (int) $user['id'];
?>

<div class="max-w-2xl mx-auto">
    <a href="<?= e(url('/admin/users')) ?>" class="text-sm text-slate-500 dark:text-slate-400 hover:text-navy">← gebruikers</a>

    <!-- Header -->
    <div class="rounded-2xl bg-navy text-white p-5 shadow-card mt-2 mb-4 flex items-center gap-4">
        <div class="w-16 h-16 rounded-full bg-brand-light text-brand-dark flex items-center justify-center text-2xl font-bold shrink-0 overflow-hidden">
            <?php if (!empty($user['avatar_path'])): ?>
                <img src="<?= e(url('/uploads/avatars/' . $user['avatar_path'])) ?>" alt="" class="w-full h-full object-cover">
            <?php else: ?>
                <?= e(strtoupper(mb_substr((string) $name, 0, 1))) ?>
            <?php endif; ?>
        </div>
        <div class="flex-1 min-w-0">
            <h1 class="text-xl font-bold truncate">
                <?= e($name) ?>
                <?php if (!empty($user['is_admin'])): ?>
                    <span class="ml-1 text-[10px] uppercase tracking-wide text-brand-dark bg-brand-light px-1.5 py-0.5 rounded align-middle">admin</span>
                <?php endif; ?>
            </h1>
            <p class="text-sm text-white/70 truncate"><?= e($user['email']) ?></p>
            <?php if (!empty($user['company_name'])): ?>
                <p class="text-xs text-white/50 truncate"><?= e($user['company_name']) ?></p>
            <?php endif; ?>
            <p class="text-[11px] text-white/50 mt-1">
                <?= empty($user['email_verified_at']) ? '⚠️ niet bevestigd' : '✓ bevestigd' ?>
                · sinds <?= e(date('d-m-Y', strtotime((string) $user['created_at']))) ?>
            </p>
        </div>
    </div>

    <!-- Account-acties -->
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-4">
        <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-3">Account</h2>
        <div class="grid sm:grid-cols-2 gap-2">
            <form method="post" action="<?= e(url('/admin/users/' . (int) $user['id'] . '/send-reset')) ?>"
                  onsubmit="return confirm('Reset-link mailen naar <?= e($user['email']) ?>?');">
                <?= csrf_field() ?>
                <button class="w-full min-h-[44px] rounded-lg bg-brand text-white font-semibold hover:bg-brand-dark">
                    Stuur wachtwoord-reset
                </button>
            </form>
            <?php if (empty($user['email_verified_at'])): ?>
                <form method="post" action="<?= e(url('/admin/users/' . (int) $user['id'] . '/resend-verification')) ?>">
                    <?= csrf_field() ?>
                    <button class="w-full min-h-[44px] rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 font-medium">
                        Stuur bevestigingsmail
                    </button>
                </form>
            <?php endif; ?>
            <?php if (!$isMe): ?>
                <form method="post" action="<?= e(url('/admin/users/' . (int) $user['id'] . '/toggle-admin')) ?>"
                      onsubmit="return confirm('Admin-rol omzetten?');" class="sm:col-span-2">
                    <?= csrf_field() ?>
                    <button class="w-full min-h-[44px] rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 font-medium">
                        <?= !empty($user['is_admin']) ? 'Admin-rol intrekken' : 'Tot admin promoveren' ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Teams -->
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-4">
        <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-3">Teams</h2>
        <?php if (empty($teams)): ?>
            <p class="text-sm text-slate-500 dark:text-slate-400">Zit nog in geen enkel team.</p>
        <?php else: ?>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800 mb-3">
                <?php foreach ($teams as $t): ?>
                    <li class="flex items-center justify-between py-2">
                        <span class="text-sm text-slate-700 dark:text-slate-200 truncate"><?= e($t['name']) ?>
                            <span class="ml-1 text-[10px] uppercase tracking-wide font-semibold <?= $t['role'] === 'captain' ? 'text-brand-dark bg-brand-light' : 'text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800' ?> px-1.5 py-0.5 rounded">
                                <?= e((string) $t['role']) ?>
                            </span>
                        </span>
                        <form method="post" action="<?= e(url('/admin/users/' . (int) $user['id'] . '/teams/' . (int) $t['id'] . '/remove')) ?>"
                              onsubmit="return confirm('Verwijderen uit <?= e($t['name']) ?>?');">
                            <?= csrf_field() ?>
                            <button class="text-xs px-3 py-1.5 rounded-md bg-white dark:bg-slate-900 border border-red-200 dark:border-red-900/40 text-red-600 hover:bg-red-50">verwijderen</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (!empty($availableTeams)): ?>
            <form method="post" action="<?= e(url('/admin/users/' . (int) $user['id'] . '/teams')) ?>"
                  class="flex items-stretch gap-2">
                <?= csrf_field() ?>
                <select name="team_id" required
                        class="flex-1 rounded-lg bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 px-3 py-2.5 text-sm">
                    <option value="">— team kiezen —</option>
                    <?php foreach ($availableTeams as $t): ?>
                        <option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="role"
                        class="rounded-lg bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 px-3 py-2.5 text-sm">
                    <option value="member">member</option>
                    <option value="captain">captain</option>
                </select>
                <button class="px-4 rounded-lg bg-brand text-white font-semibold hover:bg-brand-dark text-sm">+ Toevoegen</button>
            </form>
        <?php else: ?>
            <p class="text-xs text-slate-500 dark:text-slate-400">Zit al in alle bestaande teams.</p>
        <?php endif; ?>
    </div>
</div>
