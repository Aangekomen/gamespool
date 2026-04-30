<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var array $tournaments */
$title = 'Toernooien';
?>

<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-2xl font-bold text-navy dark:text-slate-100">Toernooien</h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm">Single-elimination competities — wie houdt het langst stand?</p>
    </div>
    <?php if (\GamesPool\Core\Admin::is()): ?>
        <a href="<?= e(url('/tournaments/new')) ?>"
           class="shrink-0 px-4 py-2 rounded-lg bg-brand text-white font-semibold hover:bg-brand-dark">
            + Nieuw
        </a>
    <?php endif; ?>
</div>

<?php if (empty($tournaments)): ?>
    <div class="rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-8 text-center">
        <p class="text-slate-500 dark:text-slate-400 text-sm">Nog geen toernooien.</p>
    </div>
<?php else: ?>
    <ul class="space-y-2">
        <?php foreach ($tournaments as $t):
            $stateBadge = match ($t['state']) {
                'open'      => ['Aanmelden open', 'bg-brand-light text-brand-dark'],
                'running'   => ['Bezig', 'bg-amber-100 text-amber-800'],
                'completed' => ['Afgerond', 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'],
                default     => ['Geannuleerd', 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'],
            };
        ?>
            <li>
                <a href="<?= e(url('/tournaments/' . (int) $t['id'])) ?>"
                   class="block rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3 hover:border-brand shadow-card">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-bold text-navy dark:text-slate-100 truncate"><?= e((string) $t['name']) ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                <?= e((string) $t['game_name']) ?> ·
                                <?= (int) $t['player_count'] ?> / <?= (int) $t['max_players'] ?> spelers
                                <?php if ($t['state'] === 'completed' && !empty($t['winner_name'])): ?>
                                    · 🏆 <?= e((string) $t['winner_name']) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <span class="shrink-0 text-xs px-2 py-1 rounded-full font-medium <?= $stateBadge[1] ?>">
                            <?= e($stateBadge[0]) ?>
                        </span>
                    </div>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
