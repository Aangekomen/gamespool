<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var array $match */
/** @var ?array $game */
/** @var array $participants */
use GamesPool\Models\Game;
$title = 'Match';
$type  = $game['score_type'] ?? 'win_loss';
?>

<div class="max-w-lg mx-auto">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-bold"><?= e($game['name'] ?? 'Match') ?></h1>
            <p class="text-slate-400 text-sm">
                <?= e(date('d-m-Y H:i', strtotime((string) $match['started_at']))) ?>
                · <?= e(Game::scoreTypeLabel($type)) ?>
                <?php if ($match['label']): ?> · <?= e($match['label']) ?><?php endif; ?>
            </p>
        </div>
        <span class="text-xs px-2 py-1 rounded-full
            <?= $match['state'] === 'in_progress' ? 'bg-amber-500/20 text-amber-300' : ($match['state'] === 'completed' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-700 text-slate-300') ?>">
            <?= $match['state'] === 'in_progress' ? 'Bezig' : ($match['state'] === 'completed' ? 'Afgerond' : 'Geannuleerd') ?>
        </span>
    </div>

    <ul class="space-y-2">
        <?php foreach ($participants as $p):
            $name = $p['display_name'] ?: ($p['guest_name'] ?: 'Onbekend');
        ?>
            <li class="rounded-xl border border-slate-800 bg-slate-900 p-3 flex items-center gap-3">
                <div class="flex-1 min-w-0">
                    <p class="font-semibold truncate"><?= e($name) ?></p>
                    <?php if ($match['state'] === 'completed' && $p['result']): ?>
                        <p class="text-xs text-slate-400">
                            <?= match ($p['result']) { 'win' => 'Winst', 'draw' => 'Gelijk', 'loss' => 'Verlies', default => '' } ?>
                            <?php if ($p['raw_score'] !== null): ?> · score <?= e((string) $p['raw_score']) ?><?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
                <?php if ($match['state'] === 'completed'): ?>
                    <?php if ($type === 'elo' && $p['rating_after'] !== null): ?>
                        <div class="text-right">
                            <p class="text-lg font-bold"><?= e((string) $p['rating_after']) ?></p>
                            <?php $delta = (int) $p['points_awarded']; ?>
                            <p class="text-xs <?= $delta >= 0 ? 'text-emerald-400' : 'text-red-400' ?>">
                                <?= $delta >= 0 ? '+' : '' ?><?= e((string) $delta) ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <span class="text-lg font-bold tabular-nums"><?= e((string) (int) $p['points_awarded']) ?></span>
                    <?php endif; ?>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if ($match['state'] === 'in_progress'): ?>
        <a href="<?= e(url('/matches/' . $match['id'] . '/record')) ?>"
           class="block text-center mt-4 w-full rounded-lg bg-emerald-500 text-slate-950 font-semibold px-4 py-3 hover:bg-emerald-400">
            Uitslag invoeren
        </a>
    <?php endif; ?>

    <div class="mt-6 text-center">
        <a href="<?= e(url('/matches')) ?>" class="text-sm text-slate-400 hover:text-slate-200">← terug naar matches</a>
    </div>
</div>
