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
            <h1 class="text-2xl font-bold text-navy"><?= e($game['name'] ?? 'Match') ?></h1>
            <p class="text-slate-500 text-sm">
                <?= e(date('d-m-Y H:i', strtotime((string) $match['started_at']))) ?>
                · <?= e(Game::scoreTypeLabel($type)) ?>
                <?php if ($match['label']): ?> · <?= e($match['label']) ?><?php endif; ?>
            </p>
        </div>
        <span class="text-xs px-2 py-1 rounded-full font-medium
            <?= $match['state'] === 'in_progress' ? 'bg-amber-100 text-amber-800' : ($match['state'] === 'completed' ? 'bg-brand-light text-brand-dark' : 'bg-slate-100 text-slate-600') ?>">
            <?= $match['state'] === 'in_progress' ? 'Bezig' : ($match['state'] === 'completed' ? 'Afgerond' : 'Geannuleerd') ?>
        </span>
    </div>

    <ul class="space-y-2">
        <?php foreach ($participants as $p):
            $name = $p['display_name'] ?: ($p['guest_name'] ?: 'Onbekend');
            $isWinner = ($p['result'] ?? null) === 'win';
        ?>
            <li class="rounded-xl border <?= $isWinner ? 'border-brand bg-brand-light' : 'border-slate-200 bg-white' ?> p-3 flex items-center gap-3 shadow-card">
                <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-sm font-bold text-slate-500 shrink-0">
                    <?= e(strtoupper(mb_substr($name, 0, 1))) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-navy truncate"><?= e($name) ?></p>
                    <?php if ($match['state'] === 'completed' && $p['result']): ?>
                        <p class="text-xs text-slate-500">
                            <?= match ($p['result']) { 'win' => 'Winst', 'draw' => 'Gelijk', 'loss' => 'Verlies', default => '' } ?>
                            <?php if ($p['raw_score'] !== null): ?> · score <?= e((string) $p['raw_score']) ?><?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
                <?php if ($match['state'] === 'completed'): ?>
                    <?php if ($type === 'elo' && $p['rating_after'] !== null): ?>
                        <div class="text-right">
                            <p class="text-lg font-bold text-navy"><?= e((string) $p['rating_after']) ?></p>
                            <?php $delta = (int) $p['points_awarded']; ?>
                            <p class="text-xs <?= $delta >= 0 ? 'text-brand-dark' : 'text-red-600' ?>">
                                <?= $delta >= 0 ? '+' : '' ?><?= e((string) $delta) ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <span class="text-lg font-bold tabular-nums text-navy"><?= e((string) (int) $p['points_awarded']) ?></span>
                    <?php endif; ?>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if ($match['state'] === 'in_progress'): ?>
        <a href="<?= e(url('/matches/' . $match['id'] . '/record')) ?>"
           class="block text-center mt-4 w-full rounded-lg bg-brand text-white font-semibold px-4 py-3 hover:bg-brand-dark">
            Uitslag invoeren
        </a>
    <?php endif; ?>

    <div class="mt-6 text-center">
        <a href="<?= e(url('/matches')) ?>" class="text-sm text-slate-500 hover:text-navy">← terug naar matches</a>
    </div>
</div>
