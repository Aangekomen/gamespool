<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php /** @var array $poules */ $title = 'Poules'; ?>

<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-2xl font-bold text-navy dark:text-slate-100">Poules</h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm">Round-robin: iedereen tegen iedereen, klassieke stand.</p>
    </div>
    <?php if (\GamesPool\Core\Admin::is()): ?>
        <a href="<?= e(url('/poules/new')) ?>"
           class="shrink-0 px-4 py-2 rounded-lg bg-brand text-white font-semibold hover:bg-brand-dark">+ Nieuw</a>
    <?php endif; ?>
</div>

<?php if (empty($poules)): ?>
    <div class="rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-8 text-center">
        <p class="text-slate-500 dark:text-slate-400 text-sm">Nog geen poules.</p>
    </div>
<?php else: ?>
    <ul class="space-y-2">
        <?php foreach ($poules as $p):
            $stateBadge = match ($p['state']) {
                'open'      => ['Aanmelden open', 'bg-brand-light dark:bg-brand-dark/25 text-brand-dark dark:text-brand-light'],
                'running'   => ['Bezig', 'bg-amber-100 text-amber-800'],
                'completed' => ['Afgerond', 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'],
                default     => ['Geannuleerd', 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'],
            };
        ?>
            <li class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-card overflow-hidden">
                <div class="flex items-center">
                    <a href="<?= e(url('/poules/' . (int) $p['id'])) ?>"
                       class="flex-1 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/40 min-w-0">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-bold text-navy dark:text-slate-100 truncate"><?= e((string) $p['name']) ?></p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                    <?= e((string) $p['game_name']) ?> · <?= (int) $p['player_count'] ?> spelers
                                    <?php if (!empty($p['starts_at'])): ?>
                                        · 🗓️ <?= e(date('d-m H:i', strtotime((string) $p['starts_at']))) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <span class="shrink-0 text-xs px-2 py-1 rounded-full font-medium <?= $stateBadge[1] ?>">
                                <?= e($stateBadge[0]) ?>
                            </span>
                        </div>
                    </a>
                    <?php if (\GamesPool\Core\Admin::is()): ?>
                        <form method="post" action="<?= e(url('/poules/' . (int) $p['id'])) ?>"
                              onsubmit="return confirm('Verwijder deze poule?');"
                              class="border-l border-slate-200 dark:border-slate-800">
                            <?= csrf_field() ?>
                            <input type="hidden" name="_method" value="DELETE">
                            <button class="px-3 py-3 text-slate-400 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950/40" aria-label="Verwijder">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                </svg>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
