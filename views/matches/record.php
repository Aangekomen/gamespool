<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var array $match */
/** @var array $game */
/** @var array $participants */
use GamesPool\Models\Game;
$title = 'Uitslag invoeren';
$type  = $game['score_type'];
?>

<div class="max-w-lg mx-auto">
    <h1 class="text-2xl font-bold mb-1">Uitslag invoeren</h1>
    <p class="text-slate-400 text-sm mb-6">
        <?= e($game['name']) ?> · <?= e(Game::scoreTypeLabel($type)) ?>
        <?php if ($match['label']): ?> · <?= e($match['label']) ?><?php endif; ?>
    </p>

    <form method="post" action="<?= e(url('/matches/' . $match['id'] . '/record')) ?>" class="space-y-3">
        <?= csrf_field() ?>

        <?php foreach ($participants as $p):
            $name = $p['display_name'] ?: ($p['guest_name'] ?: 'Onbekend');
        ?>
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-3 flex items-center gap-3">
                <div class="flex-1 min-w-0">
                    <p class="font-semibold truncate"><?= e($name) ?></p>
                    <?php if (!$p['user_id']): ?>
                        <p class="text-xs text-slate-500">gast</p>
                    <?php endif; ?>
                </div>

                <?php if ($type === 'points_per_match'): ?>
                    <input type="number" name="p[<?= e((string) $p['id']) ?>][raw_score]"
                           value="<?= e((string) ($p['raw_score'] ?? '')) ?>" min="0" step="1" required
                           class="w-24 rounded-lg bg-slate-950 border border-slate-700 px-3 py-2.5 text-base text-right"
                           placeholder="0">
                <?php else: ?>
                    <select name="p[<?= e((string) $p['id']) ?>][result]" required
                            class="rounded-lg bg-slate-950 border border-slate-700 px-3 py-2.5 text-base">
                        <option value="">—</option>
                        <option value="win">Winst</option>
                        <option value="draw">Gelijk</option>
                        <option value="loss">Verlies</option>
                    </select>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <button class="w-full rounded-lg bg-emerald-500 text-slate-950 font-semibold px-4 py-3 hover:bg-emerald-400">
            Opslaan
        </button>
    </form>

    <form method="post" action="<?= e(url('/matches/' . $match['id'] . '/cancel')) ?>"
          onsubmit="return confirm('Match annuleren?');" class="mt-3">
        <?= csrf_field() ?>
        <button type="submit" class="w-full px-4 py-3 rounded-lg bg-slate-800 hover:bg-red-900/60 text-slate-300">Match annuleren</button>
    </form>
</div>
