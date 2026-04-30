<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var bool $guest */
/** @var array|null $stats */
/** @var array|null $games */
/** @var array|null $recentMatches */
/** @var bool|null $hasTeam */
$title = 'GamesPool';
?>

<?php if ($guest): ?>
    <section class="text-center pt-6 pb-10">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-navy mb-4 relative">
            <div class="absolute inset-3 rounded-lg bg-brand"></div>
        </div>
        <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-navy dark:text-slate-100">Houd je scores bij voor elk spel</h1>
        <p class="mt-3 text-slate-600 dark:text-slate-300 max-w-xl mx-auto">
            Pool, darts, en alles wat je in de bar speelt. Maak teams, start competities, scan een QR en je doet mee.
        </p>
        <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
            <a href="<?= e(url('/register')) ?>" class="inline-flex justify-center items-center px-5 py-3 rounded-lg bg-brand text-white font-semibold hover:bg-brand-dark">Account aanmaken</a>
            <a href="<?= e(url('/login')) ?>" class="inline-flex justify-center items-center px-5 py-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-navy dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-800">Inloggen</a>
        </div>
    </section>

<?php else:
    $hour    = (int) date('G');
    $greet   = $hour < 6 ? 'Goedenacht' : ($hour < 12 ? 'Goedemorgen' : ($hour < 18 ? 'Goedemiddag' : 'Goedenavond'));
    $name    = user()['display_name'];
    $dateNl  = (function () {
        $days = ['Sunday'=>'zondag','Monday'=>'maandag','Tuesday'=>'dinsdag','Wednesday'=>'woensdag','Thursday'=>'donderdag','Friday'=>'vrijdag','Saturday'=>'zaterdag'];
        $months = ['Jan'=>'jan','Feb'=>'feb','Mar'=>'mrt','Apr'=>'apr','May'=>'mei','Jun'=>'jun','Jul'=>'jul','Aug'=>'aug','Sep'=>'sep','Oct'=>'okt','Nov'=>'nov','Dec'=>'dec'];
        return $days[date('l')] . ', ' . date('j ') . $months[date('M')];
    })();
?>
    <!-- Hero stat card met begroeting -->
    <?php $me = user(); ?>
    <div class="rounded-2xl bg-navy text-white p-5 shadow-card mb-4 relative overflow-hidden">
        <div class="absolute -top-12 -right-12 w-40 h-40 rounded-full bg-brand/10 pointer-events-none"></div>

        <div class="flex items-start justify-between gap-3 relative">
            <a href="<?= e(url('/profile')) ?>" class="flex items-center gap-3 min-w-0 group">
                <span class="w-12 h-12 rounded-full bg-white/15 flex items-center justify-center font-bold text-lg shrink-0 overflow-hidden ring-2 ring-white/20 group-hover:ring-brand transition">
                    <?php if (!empty($me['avatar_path'])): ?>
                        <img src="<?= e(url('/uploads/avatars/' . $me['avatar_path'])) ?>" alt="<?= e($name) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <?= e(strtoupper(mb_substr((string) $name, 0, 1))) ?>
                    <?php endif; ?>
                </span>
                <div class="min-w-0">
                    <p class="text-xs text-white/60"><?= e($greet) ?>, <span class="inline-block">👋</span></p>
                    <h1 class="text-lg font-bold truncate"><?= e($name) ?></h1>
                </div>
            </a>
            <span class="text-xs text-white/60 shrink-0 mt-1"><?= e($dateNl) ?></span>
        </div>

        <p class="text-white/70 text-sm mt-3">Speel een potje. Klim in de ranglijst.</p>

        <div class="grid grid-cols-3 gap-1 sm:gap-3 mt-5 relative">
            <?php
            $cells = [
                ['label' => 'Gespeeld',   'value' => $stats['today_matches'], 'sub' => 'matches vandaag',           'color' => '#35b782'],
                ['label' => 'Winstratio', 'value' => $stats['win_rate'] . '%', 'sub' => $stats['wins'] . ' winst · ' . $stats['losses'] . ' verlies', 'color' => '#a78bfa'],
                ['label' => 'Jouw punten','value' => $stats['total_points'], 'sub' => 'totaal verdiend',            'color' => '#60a5fa'],
            ];
            foreach ($cells as $c):
                // For the circular ring: percentage just decorative for visual consistency
                $pct = is_numeric(str_replace('%','',(string) $c['value'])) ? min(100, (int) preg_replace('/\D/','',(string)$c['value'])) : 0;
                $circ = 2 * M_PI * 28; // radius 28
                $offset = $circ - ($circ * $pct / 100);
            ?>
                <div class="text-center min-w-0">
                    <div class="relative w-16 h-16 sm:w-20 sm:h-20 mx-auto">
                        <svg viewBox="0 0 64 64" class="w-full h-full -rotate-90">
                            <circle cx="32" cy="32" r="28" stroke="rgba(255,255,255,0.08)" stroke-width="6" fill="none"></circle>
                            <circle cx="32" cy="32" r="28" stroke="<?= $c['color'] ?>" stroke-width="6" fill="none"
                                    stroke-linecap="round"
                                    stroke-dasharray="<?= $circ ?>" stroke-dashoffset="<?= $offset ?>"></circle>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <p class="text-base sm:text-lg font-bold leading-none"><?= e((string) $c['value']) ?></p>
                        </div>
                    </div>
                    <p class="text-[11px] text-white/70 mt-1.5 font-medium truncate"><?= e($c['label']) ?></p>
                    <p class="text-[10px] text-white/40 truncate"><?= e((string) $c['sub']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Now playing -->
    <?php if (!empty($activeMatches)): ?>
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-bold text-navy dark:text-slate-100 flex items-center gap-2">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-brand"></span>
                    </span>
                    Now playing
                </h2>
                <span class="text-xs text-slate-500 dark:text-slate-400"><?= count($activeMatches) ?> actief</span>
            </div>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                <?php foreach ($activeMatches as $am):
                    $names = $am['participant_names'] ?? [];
                    $href  = $am['state'] === 'waiting'
                        ? url('/m/' . $am['join_token'])
                        : url('/matches/' . $am['id']);
                ?>
                    <li>
                        <a href="<?= e($href) ?>" class="flex items-center justify-between py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800 -mx-2 px-2 rounded-md gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-navy dark:text-slate-100 truncate">
                                    <?= e($am['game_name']) ?><?= $am['label'] ? ' · ' . e($am['label']) : '' ?>
                                </p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                    <?php if (!empty($names)): ?>
                                        <?= e(implode(' vs ', $names)) ?>
                                    <?php else: ?>
                                        — geen deelnemers —
                                    <?php endif; ?>
                                    <?php if ($am['state'] === 'waiting'): ?> · wacht op tegenstander<?php endif; ?>
                                </p>
                            </div>
                            <span class="shrink-0 text-[11px] font-medium px-2 py-0.5 rounded-full
                                <?= $am['state'] === 'waiting' ? 'bg-amber-100 text-amber-800' : 'bg-brand-light text-brand-dark' ?>">
                                <?= $am['state'] === 'waiting' ? 'Wacht' : 'Bezig' ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Primary CTA: nieuwe matches starten via QR-scan -->
    <a href="<?= e(url('/scan')) ?>"
       class="block text-center w-full rounded-xl bg-brand text-white text-base font-bold px-4 py-4 hover:bg-brand-dark shadow-card mb-4 flex items-center justify-center gap-2">
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2M7 7h4v4H7zM13 7h4v4h-4zM7 13h4v4H7zM13 13h2v2h-2zM17 13v2h-2M15 17h2v-2"/>
        </svg>
        Scan apparaat — start match
    </a>
    <p class="text-center text-xs text-slate-500 dark:text-slate-400 -mt-3 mb-4">
        Nieuwe matches start je door de QR op tafel te scannen.
    </p>

    <!-- Two stat cards -->
    <div class="grid grid-cols-2 gap-3 mb-4">
        <a href="<?= e(url('/leaderboard?period=week')) ?>"
           class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card hover:border-brand transition">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Deze week</p>
                    <p class="text-3xl font-bold text-navy dark:text-slate-100 mt-1"><?= (int) $stats['week_matches'] ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">matches</p>
                </div>
                <span class="w-9 h-9 rounded-lg bg-brand-light flex items-center justify-center text-brand-dark">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12h18M3 6h18M3 18h18"/></svg>
                </span>
            </div>
        </a>
        <a href="<?= e(url('/leaderboard')) ?>"
           class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card hover:border-brand transition">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Mijn rang</p>
                    <p class="text-3xl font-bold text-navy dark:text-slate-100 mt-1">
                        <?= $stats['rank'] !== null ? '#' . (int) $stats['rank'] : '–' ?>
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        <?= $stats['rank'] !== null ? 'van ' . (int) $stats['total_ranked'] : 'speel een match' ?>
                    </p>
                </div>
                <span class="w-9 h-9 rounded-lg bg-brand-light flex items-center justify-center text-brand-dark">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 17l6-6 4 4 8-8"/></svg>
                </span>
            </div>
        </a>
    </div>

    <!-- Recent matches -->
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold text-navy dark:text-slate-100">Recente matches</h3>
            <a href="<?= e(url('/matches')) ?>" class="text-xs text-brand-dark font-semibold hover:underline">Alle →</a>
        </div>
        <?php if (empty($recentMatches)): ?>
            <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-6">Nog geen matches gespeeld.<br>Tik <em>+ Start nieuwe match</em> om te beginnen.</p>
        <?php else: ?>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                <?php foreach ($recentMatches as $m): ?>
                    <li>
                        <a href="<?= e(url('/matches/' . $m['id'])) ?>" class="flex items-center justify-between py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800 -mx-2 px-2 rounded-md">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-navy dark:text-slate-100 truncate"><?= e($m['game_name']) ?><?= $m['label'] ? ' · ' . e($m['label']) : '' ?></p>
                                <p class="text-xs text-slate-500 dark:text-slate-400"><?= e(date('d-m H:i', strtotime((string) $m['started_at']))) ?></p>
                            </div>
                            <span class="shrink-0 text-[11px] font-medium px-2 py-0.5 rounded-full
                                <?= $m['state'] === 'in_progress' ? 'bg-amber-100 text-amber-800' : ($m['state'] === 'completed' ? 'bg-brand-light text-brand-dark' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300') ?>">
                                <?= $m['state'] === 'in_progress' ? 'Bezig' : ($m['state'] === 'completed' ? 'Klaar' : 'Geannuleerd') ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (!($guest ?? true)): ?>
<!-- Eerste-keer tour: 3 stappen zodat nieuwe spelers weten wat ze kunnen doen -->
<div id="tourModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 px-4"
     role="dialog" aria-modal="true">
    <div class="w-full max-w-sm rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-card overflow-hidden">
        <div class="bg-navy text-white px-5 py-4">
            <p class="text-xs uppercase tracking-widest text-white/60 font-bold">Welkom bij FlexiComp</p>
            <h2 class="text-xl font-bold mt-1">Zo werkt het</h2>
        </div>
        <div id="tourSteps">
            <div class="tour-step px-5 py-6" data-step="1">
                <div class="text-5xl text-center mb-3">📷</div>
                <h3 class="text-base font-bold text-navy dark:text-slate-100 text-center mb-2">1. Scan een tafel</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300 text-center">
                    Tik op <strong>Scan apparaat</strong>, richt je camera op de QR op de tafel en je match start vanzelf.
                </p>
            </div>
            <div class="tour-step px-5 py-6 hidden" data-step="2">
                <div class="text-5xl text-center mb-3">🏆</div>
                <h3 class="text-base font-bold text-navy dark:text-slate-100 text-center mb-2">2. Speel & verdien punten</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300 text-center">
                    Voer de uitslag in op je telefoon. Win-streaks, badges en je seizoens-rang lopen vanzelf bij.
                </p>
            </div>
            <div class="tour-step px-5 py-6 hidden" data-step="3">
                <div class="text-5xl text-center mb-3">👥</div>
                <h3 class="text-base font-bold text-navy dark:text-slate-100 text-center mb-2">3. Sluit aan bij een team</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300 text-center">
                    Vraag je captain om een 6-cijferige code, of begin er zelf één. Teams hebben hun eigen leaderboard.
                </p>
            </div>
        </div>
        <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
            <button id="tourSkip" type="button"
                    class="text-xs text-slate-500 dark:text-slate-400 hover:text-navy">
                Overslaan
            </button>
            <div id="tourDots" class="flex items-center gap-1.5"></div>
            <button id="tourNext" type="button"
                    class="text-sm font-semibold text-white bg-brand hover:bg-brand-dark px-4 py-2 rounded-md">
                Volgende →
            </button>
        </div>
    </div>
</div>
<script>
(function () {
    const KEY = 'fc_tour_done';
    try { if (localStorage.getItem(KEY)) return; } catch (e) {}
    const modal = document.getElementById('tourModal');
    if (!modal) return;
    const steps = Array.from(document.querySelectorAll('.tour-step'));
    const dots = document.getElementById('tourDots');
    const nextBtn = document.getElementById('tourNext');
    const skipBtn = document.getElementById('tourSkip');
    let idx = 0;
    steps.forEach((_, i) => {
        const d = document.createElement('span');
        d.className = 'inline-block rounded-full transition ' + (i === 0 ? 'w-6 h-2 bg-brand' : 'w-2 h-2 bg-slate-300 dark:bg-slate-700');
        dots.appendChild(d);
    });
    function show(i) {
        steps[idx].classList.add('hidden');
        dots.children[idx].className = 'inline-block w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-700 transition';
        idx = i;
        steps[idx].classList.remove('hidden');
        dots.children[idx].className = 'inline-block w-6 h-2 rounded-full bg-brand transition';
        nextBtn.textContent = (idx === steps.length - 1) ? 'Aan de slag' : 'Volgende →';
    }
    function done() {
        try { localStorage.setItem(KEY, '1'); } catch (e) {}
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    nextBtn.addEventListener('click', () => idx === steps.length - 1 ? done() : show(idx + 1));
    skipBtn.addEventListener('click', done);
    modal.classList.remove('hidden');
    modal.classList.add('flex');
})();
</script>
<?php endif; ?>

<?php if (!($guest ?? true) && empty($hasTeam)): ?>
    <!-- Popup: speler heeft nog geen team -->
    <div id="noTeamModal"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 px-4"
         role="dialog" aria-modal="true" aria-labelledby="noTeamTitle">
        <div class="w-full max-w-sm rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-card p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-full bg-brand-light text-brand-dark flex items-center justify-center">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <h2 id="noTeamTitle" class="text-base font-bold text-navy dark:text-slate-100">Je zit nog in geen team</h2>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-300 mb-4">
                Sluit aan bij een team om mee te tellen voor team-leaderboards en samen punten op te sparen.
            </p>
            <div class="grid grid-cols-2 gap-2 mb-2">
                <a href="<?= e(url('/teams/join')) ?>"
                   class="block text-center rounded-lg bg-brand text-white font-semibold py-2.5 hover:bg-brand-dark">
                    Team joinen
                </a>
                <a href="<?= e(url('/teams/new')) ?>"
                   class="block text-center rounded-lg bg-slate-100 dark:bg-slate-800 text-navy dark:text-slate-100 font-semibold py-2.5 hover:bg-slate-200 dark:hover:bg-slate-700">
                    Team maken
                </a>
            </div>
            <button id="noTeamLater" type="button"
                    class="w-full text-center text-xs text-slate-500 dark:text-slate-400 hover:text-navy py-1">
                Later vragen
            </button>
        </div>
    </div>
    <script>
    (function () {
        const KEY = 'noTeamModalDismissed';
        let dismissed = 0;
        try { dismissed = parseInt(localStorage.getItem(KEY) || '0', 10); } catch (e) {}
        // Pas opnieuw tonen na 24 uur "later"-tap
        if (Date.now() - dismissed < 24 * 60 * 60 * 1000) return;
        const modal = document.getElementById('noTeamModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.getElementById('noTeamLater').addEventListener('click', () => {
            try { localStorage.setItem(KEY, String(Date.now())); } catch (e) {}
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });
    })();
    </script>
<?php endif; ?>
