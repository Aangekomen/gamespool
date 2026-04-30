<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var ?array $game */
/** @var array $errors */
use GamesPool\Models\Game;

$isEdit = $game !== null;
$title  = $isEdit ? 'Spel bewerken' : 'Nieuw spel';
$action = $isEdit ? url('/games/' . $game['slug']) : url('/games');
$config = $isEdit ? Game::decodeConfig($game) : Game::defaultConfig('win_loss');
$currentType = (string) old('score_type', $game['score_type'] ?? 'win_loss');

$inputCls = 'w-full rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand';
?>

<div class="max-w-lg mx-auto">
    <h1 class="text-2xl font-bold text-navy dark:text-slate-100 mb-1"><?= e($title) ?></h1>
    <p class="text-slate-500 dark:text-slate-400 text-sm mb-6">Geef het spel een naam en kies hoe je punten telt.</p>

    <form method="post" action="<?= e($action) ?>" class="space-y-4 bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-card">
        <?= csrf_field() ?>
        <?php if ($isEdit): ?><input type="hidden" name="_method" value="PATCH"><?php endif; ?>

        <div>
            <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5" for="name">Naam</label>
            <input id="name" name="name" type="text" required minlength="2" maxlength="100"
                   value="<?= e((string) old('name', $game['name'] ?? '')) ?>"
                   class="<?= $inputCls ?>"
                   placeholder="Poolbiljart, darten, sjoelen...">
            <?php foreach (($errors['name'] ?? []) as $err): ?>
                <p class="text-red-600 text-sm mt-1"><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>

        <div>
            <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5" for="score_type">Scoresysteem</label>
            <select id="score_type" name="score_type" class="<?= $inputCls ?>"
                    onchange="document.querySelectorAll('[data-config]').forEach(el=>el.classList.add('hidden')); var t=document.querySelector('[data-config=\''+this.value+'\']'); if(t) t.classList.remove('hidden');">
                <?php foreach (Game::SCORE_TYPES as $t): ?>
                    <option value="<?= e($t) ?>" <?= $t === $currentType ? 'selected' : '' ?>>
                        <?= e(Game::scoreTypeLabel($t)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php foreach (($errors['score_type'] ?? []) as $err): ?>
                <p class="text-red-600 text-sm mt-1"><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>

        <div data-config="win_loss" class="<?= $currentType === 'win_loss' ? '' : 'hidden' ?> space-y-3 rounded-lg border border-slate-200 dark:border-slate-800 bg-surface dark:bg-slate-950 p-4">
            <p class="text-sm text-slate-600 dark:text-slate-300">Vaste punten per uitslag (klassiek voetbal-style).</p>
            <div class="grid grid-cols-3 gap-3">
                <?php foreach (['win_points' => 'Winst', 'draw_points' => 'Gelijk', 'loss_points' => 'Verlies'] as $k => $label):
                    $val = (int) ($currentType === 'win_loss' ? ($config[$k] ?? Game::defaultConfig('win_loss')[$k]) : Game::defaultConfig('win_loss')[$k]); ?>
                    <label class="block">
                        <span class="block text-xs text-slate-500 dark:text-slate-400 mb-1"><?= e($label) ?></span>
                        <input type="number" name="score_config[<?= e($k) ?>]" value="<?= e((string) $val) ?>" min="0" max="100"
                               class="w-full rounded-md bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-2 py-2 text-base">
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div data-config="points_per_match" class="<?= $currentType === 'points_per_match' ? '' : 'hidden' ?> rounded-lg border border-slate-200 dark:border-slate-800 bg-surface dark:bg-slate-950 p-4">
            <p class="text-sm text-slate-600 dark:text-slate-300">De ingevoerde score per match telt rechtstreeks als punten (bijv. 7 ballen gepot = 7 punten).</p>
        </div>

        <div data-config="team_score" class="<?= $currentType === 'team_score' ? '' : 'hidden' ?> space-y-3 rounded-lg border border-slate-200 dark:border-slate-800 bg-surface dark:bg-slate-950 p-4">
            <p class="text-sm text-slate-600 dark:text-slate-300">Bv. tafelvoetbal of pingpong-dubbel: 2 teams, eindstand per team beslist over winst.</p>
            <div class="grid grid-cols-3 gap-3">
                <?php foreach (['win_points' => 'Winst', 'draw_points' => 'Gelijk', 'loss_points' => 'Verlies'] as $k => $label):
                    $val = (int) ($currentType === 'team_score' ? ($config[$k] ?? Game::defaultConfig('team_score')[$k]) : Game::defaultConfig('team_score')[$k]); ?>
                    <label class="block">
                        <span class="block text-xs text-slate-500 dark:text-slate-400 mb-1"><?= e($label) ?></span>
                        <input type="number" name="score_config[<?= e($k) ?>]" value="<?= e((string) $val) ?>" min="0" max="100"
                               class="w-full rounded-md bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-2 py-2 text-base">
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div data-config="elo" class="<?= $currentType === 'elo' ? '' : 'hidden' ?> space-y-3 rounded-lg border border-slate-200 dark:border-slate-800 bg-surface dark:bg-slate-950 p-4">
            <p class="text-sm text-slate-600 dark:text-slate-300">Elo-rating per speler. Sterkere tegenstander winnen levert meer op.</p>
            <div class="grid grid-cols-2 gap-3">
                <?php $elo = $currentType === 'elo' ? $config : Game::defaultConfig('elo'); ?>
                <label class="block">
                    <span class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Startrating</span>
                    <input type="number" name="score_config[start_rating]" value="<?= e((string) ($elo['start_rating'] ?? 1000)) ?>" min="100" max="3000"
                           class="w-full rounded-md bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-2 py-2 text-base">
                </label>
                <label class="block">
                    <span class="block text-xs text-slate-500 dark:text-slate-400 mb-1">K-factor</span>
                    <input type="number" name="score_config[k_factor]" value="<?= e((string) ($elo['k_factor'] ?? 24)) ?>" min="4" max="64"
                           class="w-full rounded-md bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-2 py-2 text-base">
                </label>
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button class="flex-1 rounded-lg bg-brand text-white font-semibold px-4 py-3 hover:bg-brand-dark">
                <?= $isEdit ? 'Opslaan' : 'Spel toevoegen' ?>
            </button>
            <a href="<?= e(url('/games')) ?>" class="px-4 py-3 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">Annuleren</a>
        </div>
    </form>
</div>
