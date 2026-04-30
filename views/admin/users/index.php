<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php $title = 'Gebruikers'; /** @var array $users */ ?>

<div class="mb-4 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-navy dark:text-slate-100">Gebruikers</h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm"><?= count($users) ?> account<?= count($users) === 1 ? '' : 's' ?> geregistreerd.</p>
    </div>
    <a href="<?= e(url('/admin')) ?>" class="text-sm text-slate-500 dark:text-slate-400 hover:text-navy">← admin</a>
</div>

<?php if (empty($users)): ?>
    <p class="text-slate-500 dark:text-slate-400 text-center py-8">Nog geen gebruikers.</p>
<?php else: ?>
    <ul class="space-y-2">
        <?php foreach ($users as $u):
            $name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?: $u['display_name'] ?: $u['email'];
        ?>
            <li class="flex items-center gap-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3 shadow-card">
                <div class="w-10 h-10 rounded-full bg-brand-light text-brand-dark flex items-center justify-center font-bold shrink-0">
                    <?= e(strtoupper(mb_substr((string) $name, 0, 1))) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-navy dark:text-slate-100 truncate">
                        <?= e($name) ?>
                        <?php if (!empty($u['is_admin'])): ?>
                            <span class="ml-1 text-[10px] uppercase tracking-wide text-brand-dark bg-brand-light px-1.5 py-0.5 rounded">admin</span>
                        <?php endif; ?>
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate"><?= e((string) $u['email']) ?>
                        <?php if (!empty($u['company_name'])): ?>
                            · <?= e($u['company_name']) ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-sm font-bold text-navy dark:text-slate-100 tabular-nums"><?= (int) $u['matches_played'] ?></p>
                    <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold">Matches</p>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
