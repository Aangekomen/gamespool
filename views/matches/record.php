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
    <h1 class="text-2xl font-bold text-navy dark:text-slate-100 mb-1">Uitslag invoeren</h1>
    <p class="text-slate-500 dark:text-slate-400 text-sm mb-6">
        <?= e($game['name']) ?> · <?= e(Game::scoreTypeLabel($type)) ?>
        <?php if ($match['label']): ?> · <?= e($match['label']) ?><?php endif; ?>
    </p>

    <form method="post" action="<?= e(url('/matches/' . $match['id'] . '/record')) ?>"
          class="space-y-3 bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-card">
        <?= csrf_field() ?>

        <?php if ($type === 'points_per_match'): ?>
            <p class="text-sm text-slate-600 dark:text-slate-300 mb-1">Vul per speler de score in.</p>
            <?php foreach ($participants as $p):
                $name = $p['display_name'] ?? 'Onbekend';
            ?>
                <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-surface dark:bg-slate-950 p-3 flex items-center gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-navy dark:text-slate-100 truncate"><?= e($name) ?></p>
                    </div>
                    <input type="number" name="p[<?= e((string) $p['id']) ?>][raw_score]"
                           value="<?= e((string) ($p['raw_score'] ?? '')) ?>" min="0" step="1" required
                           class="w-24 rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2.5 text-base text-right focus:outline-none focus:ring-2 focus:ring-brand"
                           placeholder="0">
                </div>
            <?php endforeach; ?>

        <?php else: /* win_loss + elo: pick exactly one winner OR mark all as draw */ ?>
            <input type="hidden" name="outcome_mode" value="winner" id="outcomeMode">
            <input type="hidden" name="winner_id" value="" id="winnerId">

            <p class="text-sm text-slate-600 dark:text-slate-300 mb-1">Tik op de winnaar.</p>

            <?php foreach ($participants as $p):
                $name = $p['display_name'] ?? 'Onbekend';
            ?>
                <button type="button" data-pick="<?= e((string) $p['id']) ?>"
                        class="pick-btn w-full text-left rounded-xl border-2 border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 flex items-center gap-3 transition
                               hover:border-brand/50 active:border-brand">
                    <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-sm font-bold text-slate-500 dark:text-slate-400 shrink-0">
                        <?= e(strtoupper(mb_substr($name, 0, 1))) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-navy dark:text-slate-100 truncate"><?= e($name) ?></p>
                    </div>
                    <span class="pick-badge hidden text-xs font-semibold px-2 py-1 rounded-full bg-brand text-white">Winnaar</span>
                </button>
            <?php endforeach; ?>

            <button type="button" id="drawBtn"
                    class="w-full mt-1 px-4 py-2 rounded-md text-sm font-medium bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">
                Of: gelijkspel
            </button>
        <?php endif; ?>

        <button id="submitBtn" class="w-full mt-2 rounded-lg bg-brand text-white font-semibold px-4 py-3 hover:bg-brand-dark disabled:opacity-50 disabled:cursor-not-allowed"
                <?= $type !== 'points_per_match' ? 'disabled' : '' ?>>
            Opslaan
        </button>
    </form>

    <form method="post" action="<?= e(url('/matches/' . $match['id'] . '/cancel')) ?>"
          onsubmit="return confirm('Match annuleren?');" class="mt-3">
        <?= csrf_field() ?>
        <button type="submit" class="w-full px-4 py-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-red-50 hover:text-red-700 text-slate-600 dark:text-slate-300">
            Match annuleren
        </button>
    </form>
</div>

<?php if ($type !== 'points_per_match'): ?>
<script>
    const $picks    = document.querySelectorAll('.pick-btn');
    const $draw     = document.getElementById('drawBtn');
    const $submit   = document.getElementById('submitBtn');
    const $mode     = document.getElementById('outcomeMode');
    const $winner   = document.getElementById('winnerId');

    function clearPickStyles() {
        $picks.forEach(b => {
            b.classList.remove('border-brand', 'bg-brand-light');
            b.classList.add('border-slate-200', 'dark:border-slate-800', 'bg-white', 'dark:bg-slate-900');
            b.querySelector('.pick-badge').classList.add('hidden');
        });
        $draw.classList.remove('bg-brand', 'text-white', 'hover:bg-brand-dark');
        $draw.classList.add('bg-slate-100', 'dark:bg-slate-800', 'hover:bg-slate-200', 'text-slate-700', 'dark:text-slate-300');
    }

    $picks.forEach(btn => {
        btn.addEventListener('click', () => {
            clearPickStyles();
            btn.classList.remove('border-slate-200', 'dark:border-slate-800', 'bg-white', 'dark:bg-slate-900');
            btn.classList.add('border-brand', 'bg-brand-light');
            btn.querySelector('.pick-badge').classList.remove('hidden');
            $mode.value = 'winner';
            $winner.value = btn.dataset.pick;
            $submit.disabled = false;
        });
    });

    $draw.addEventListener('click', () => {
        clearPickStyles();
        $draw.classList.remove('bg-slate-100', 'dark:bg-slate-800', 'hover:bg-slate-200', 'text-slate-700', 'dark:text-slate-300');
        $draw.classList.add('bg-brand', 'text-white', 'hover:bg-brand-dark');
        $mode.value = 'draw';
        $winner.value = '';
        $submit.disabled = false;
    });
</script>
<?php endif; ?>
