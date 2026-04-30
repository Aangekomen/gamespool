<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var array $tournament */
/** @var ?array $game */
/** @var array $participants */
/** @var bool $isParticipant */
/** @var array $bracket */
$title = $tournament['name'];
$canStart = $tournament['state'] === 'open' && count($participants) >= 2 && \GamesPool\Core\Admin::is();
?>

<div class="flex items-start justify-between mb-2 gap-3">
    <div class="min-w-0">
        <h1 class="text-2xl font-bold text-navy dark:text-slate-100"><?= e((string) $tournament['name']) ?></h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm">
            <?= e((string) ($game['name'] ?? '?')) ?> · max <?= (int) $tournament['max_players'] ?> spelers
            <?php if (!empty($tournament['starts_at'])): ?>
                · 🗓️ <?= e(date('d-m-Y H:i', strtotime((string) $tournament['starts_at']))) ?>
            <?php endif; ?>
        </p>
    </div>
    <div class="flex items-center gap-2 shrink-0">
        <a href="<?= e(url('/tournaments')) ?>" class="text-sm text-slate-500 dark:text-slate-400 hover:text-navy">← lijst</a>
        <?php if (\GamesPool\Core\Admin::is()): ?>
            <form method="post" action="<?= e(url('/tournaments/' . (int) $tournament['id'])) ?>"
                  onsubmit="return confirm('Verwijder dit toernooi? De bracket gaat weg; gespeelde matches blijven in de historie.');">
                <?= csrf_field() ?>
                <input type="hidden" name="_method" value="DELETE">
                <button type="submit" aria-label="Verwijder toernooi"
                        class="text-sm text-red-700 hover:bg-red-50 px-2 py-1 rounded">Verwijder</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($tournament['state'] === 'completed' && !empty($tournament['winner_id'])):
    $winner = null;
    foreach ($participants as $p) if ((int) $p['id'] === (int) $tournament['winner_id']) $winner = $p;
?>
    <div class="rounded-2xl bg-navy text-white p-5 mb-4 shadow-card text-center">
        <p class="text-xs uppercase tracking-widest text-white/60 font-bold">Toernooi-winnaar</p>
        <p class="text-3xl font-extrabold mt-2">🏆 <?= e((string) ($winner['display_name'] ?? '?')) ?></p>
    </div>
<?php endif; ?>

<!-- Open: aanmelden -->
<?php if ($tournament['state'] === 'open'): ?>
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-4">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm font-bold text-navy dark:text-slate-100">Spelers</p>
            <p class="text-xs text-slate-500 dark:text-slate-400"><?= count($participants) ?> / <?= (int) $tournament['max_players'] ?></p>
        </div>
        <?php if (empty($participants)): ?>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Nog niemand aangemeld.</p>
        <?php else: ?>
            <ul class="grid grid-cols-2 gap-1.5 mb-3">
                <?php foreach ($participants as $p): ?>
                    <li class="flex items-center gap-2 rounded-md bg-surface dark:bg-slate-950 px-2 py-1.5">
                        <div class="w-7 h-7 rounded-full bg-brand-light text-brand-dark flex items-center justify-center text-xs font-bold overflow-hidden shrink-0">
                            <?php if (!empty($p['avatar_path'])): ?>
                                <img src="<?= e(url('/uploads/avatars/' . $p['avatar_path'])) ?>" alt="" class="w-full h-full object-cover">
                            <?php else: ?>
                                <?= e(strtoupper(mb_substr((string) $p['display_name'], 0, 1))) ?>
                            <?php endif; ?>
                        </div>
                        <span class="text-xs font-semibold text-navy dark:text-slate-100 truncate"><?= e((string) $p['display_name']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <div class="grid grid-cols-2 gap-2">
            <?php if (!$isParticipant): ?>
                <form method="post" action="<?= e(url('/tournaments/' . (int) $tournament['id'] . '/register')) ?>" class="col-span-2">
                    <?= csrf_field() ?>
                    <button class="w-full rounded-lg bg-brand text-white font-semibold py-2.5 hover:bg-brand-dark"
                            <?= count($participants) >= (int) $tournament['max_players'] ? 'disabled' : '' ?>>
                        Aanmelden
                    </button>
                </form>
            <?php else: ?>
                <form method="post" action="<?= e(url('/tournaments/' . (int) $tournament['id'] . '/leave')) ?>" class="col-span-2">
                    <?= csrf_field() ?>
                    <button class="w-full rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-red-50 hover:text-red-700 text-slate-600 dark:text-slate-300 font-semibold py-2.5">
                        Aanmelding intrekken
                    </button>
                </form>
            <?php endif; ?>
            <?php if ($canStart): ?>
                <form method="post" action="<?= e(url('/tournaments/' . (int) $tournament['id'] . '/start')) ?>" class="col-span-2">
                    <?= csrf_field() ?>
                    <button class="w-full rounded-lg bg-navy text-white font-semibold py-2.5 hover:bg-navy-soft">
                        ▶ Toernooi starten
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Bracket -->
<?php if (!empty($bracket)): ?>
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-3 shadow-card overflow-x-auto">
        <div class="flex gap-4 min-w-max">
            <?php
                ksort($bracket);
                $totalRounds = count($bracket);
                $roundLabels = [
                    1 => 'Ronde 1', 2 => 'Kwart', 3 => 'Halve', 4 => 'Finale'
                ];
                // Voor mooier centeren: spacing groeit per ronde
            ?>
            <?php foreach ($bracket as $round => $matches):
                $label = $roundLabels[$round] ?? ('R' . $round);
                if ($round === $totalRounds) $label = 'Finale';
                elseif ($round === $totalRounds - 1) $label = 'Halve';
                elseif ($round === $totalRounds - 2) $label = 'Kwart';
                $gap = max(8, (2 ** ($round - 1)) * 14);
            ?>
                <div class="flex flex-col" style="gap: <?= $gap ?>px; padding-top: <?= ($round - 1) * 24 ?>px">
                    <p class="text-[10px] uppercase tracking-widest font-bold text-slate-500 dark:text-slate-400 mb-1 text-center"><?= e($label) ?></p>
                    <?php foreach ($matches as $m):
                        $p1 = $m['players'][0] ?? null;
                        $p2 = $m['players'][1] ?? null;
                        $w1 = ($p1['result'] ?? '') === 'win';
                        $w2 = ($p2['result'] ?? '') === 'win';
                        $playable = $m['state'] === 'in_progress';
                    ?>
                        <a href="<?= e(url('/matches/' . $m['id'])) ?>"
                           class="block w-44 rounded-lg border <?= $playable ? 'border-amber-400' : 'border-slate-200 dark:border-slate-700' ?> bg-surface dark:bg-slate-950 overflow-hidden text-xs hover:border-brand">
                            <div class="px-2 py-1.5 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between gap-1 <?= $w1 ? 'bg-brand-light' : '' ?>">
                                <span class="font-semibold text-navy dark:text-slate-100 truncate"><?= e((string) ($p1['display_name'] ?? '— bye —')) ?></span>
                                <?php if ($w1): ?><span class="text-brand-dark">✓</span><?php endif; ?>
                            </div>
                            <div class="px-2 py-1.5 flex items-center justify-between gap-1 <?= $w2 ? 'bg-brand-light' : '' ?>">
                                <span class="font-semibold text-navy dark:text-slate-100 truncate"><?= e((string) ($p2['display_name'] ?? '— bye —')) ?></span>
                                <?php if ($w2): ?><span class="text-brand-dark">✓</span><?php endif; ?>
                            </div>
                            <?php if ($playable): ?>
                                <p class="text-[9px] uppercase tracking-widest font-bold text-amber-700 px-2 py-0.5 text-center bg-amber-50">Klik om te spelen</p>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
