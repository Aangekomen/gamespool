<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var bool $guest */
/** @var array|null $stats */
/** @var array|null $games */
/** @var array|null $recentMatches */
$title = 'GamesPool';
?>

<?php if ($guest): ?>
    <section class="text-center pt-6 pb-10">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-navy mb-4 relative">
            <div class="absolute inset-3 rounded-lg bg-brand"></div>
        </div>
        <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-navy">Houd je scores bij voor elk spel</h1>
        <p class="mt-3 text-slate-600 max-w-xl mx-auto">
            Pool, darts, en alles wat je in de bar speelt. Maak teams, start competities, scan een QR en je doet mee.
        </p>
        <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
            <a href="<?= e(url('/register')) ?>" class="inline-flex justify-center items-center px-5 py-3 rounded-lg bg-brand text-white font-semibold hover:bg-brand-dark">Account aanmaken</a>
            <a href="<?= e(url('/login')) ?>" class="inline-flex justify-center items-center px-5 py-3 rounded-lg bg-white border border-slate-200 text-navy hover:bg-slate-50">Inloggen</a>
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
    <!-- Greeting -->
    <div class="mb-4">
        <p class="text-slate-500 text-sm"><?= e($greet) ?>, <span class="inline-block">👋</span></p>
        <h1 class="text-2xl font-bold text-navy"><?= e($name) ?></h1>
    </div>

    <!-- Hero stat card -->
    <div class="rounded-2xl bg-navy text-white p-5 shadow-card mb-4 relative overflow-hidden">
        <div class="absolute -top-12 -right-12 w-40 h-40 rounded-full bg-brand/10 pointer-events-none"></div>
        <div class="flex items-baseline justify-between relative">
            <h2 class="text-lg font-bold">Vandaag</h2>
            <span class="text-xs text-white/60"><?= e($dateNl) ?></span>
        </div>
        <p class="text-white/70 text-sm mt-0.5">Speel een potje. Klim in de ranglijst.</p>

        <div class="grid grid-cols-3 gap-1 sm:gap-3 mt-5 relative">
            <?php
            $cells = [
                ['label' => 'Vandaag',  'value' => $stats['today_matches'], 'sub' => 'matches',  'color' => '#35b782'],
                ['label' => 'Winst',    'value' => $stats['win_rate'] . '%', 'sub' => $stats['wins'] . ' ✕ ' . $stats['losses'], 'color' => '#a78bfa'],
                ['label' => 'Punten',   'value' => $stats['total_points'], 'sub' => 'lifetime', 'color' => '#60a5fa'],
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

    <!-- Primary CTA -->
    <a href="<?= e(url('/matches/new')) ?>"
       class="block text-center w-full rounded-xl bg-brand text-white text-base font-bold px-4 py-4 hover:bg-brand-dark shadow-card mb-4">
        + Start nieuwe match
    </a>

    <!-- Quick start per game -->
    <?php if (!empty($games)): ?>
        <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-card mb-4">
            <h3 class="text-sm font-bold text-navy mb-3">Snel starten</h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                <?php foreach ($games as $g): ?>
                    <a href="<?= e(url('/matches/new?game_id=' . (int) $g['id'])) ?>"
                       class="rounded-lg bg-surface border border-slate-200 px-3 py-2.5 text-sm font-medium text-navy hover:border-brand hover:bg-brand-light transition">
                        <?= e($g['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Two stat cards -->
    <div class="grid grid-cols-2 gap-3 mb-4">
        <a href="<?= e(url('/leaderboard?period=week')) ?>"
           class="rounded-2xl bg-white border border-slate-200 p-4 shadow-card hover:border-brand transition">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs text-slate-500 font-medium">Deze week</p>
                    <p class="text-3xl font-bold text-navy mt-1"><?= (int) $stats['week_matches'] ?></p>
                    <p class="text-xs text-slate-500">matches</p>
                </div>
                <span class="w-9 h-9 rounded-lg bg-brand-light flex items-center justify-center text-brand-dark">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12h18M3 6h18M3 18h18"/></svg>
                </span>
            </div>
        </a>
        <a href="<?= e(url('/leaderboard')) ?>"
           class="rounded-2xl bg-white border border-slate-200 p-4 shadow-card hover:border-brand transition">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs text-slate-500 font-medium">Mijn rang</p>
                    <p class="text-3xl font-bold text-navy mt-1">
                        <?= $stats['rank'] !== null ? '#' . (int) $stats['rank'] : '–' ?>
                    </p>
                    <p class="text-xs text-slate-500">
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
    <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-card">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold text-navy">Recente matches</h3>
            <a href="<?= e(url('/matches')) ?>" class="text-xs text-brand-dark font-semibold hover:underline">Alle →</a>
        </div>
        <?php if (empty($recentMatches)): ?>
            <p class="text-sm text-slate-500 text-center py-6">Nog geen matches gespeeld.<br>Tik <em>+ Start nieuwe match</em> om te beginnen.</p>
        <?php else: ?>
            <ul class="divide-y divide-slate-100">
                <?php foreach ($recentMatches as $m): ?>
                    <li>
                        <a href="<?= e(url('/matches/' . $m['id'])) ?>" class="flex items-center justify-between py-2.5 hover:bg-slate-50 -mx-2 px-2 rounded-md">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-navy truncate"><?= e($m['game_name']) ?><?= $m['label'] ? ' · ' . e($m['label']) : '' ?></p>
                                <p class="text-xs text-slate-500"><?= e(date('d-m H:i', strtotime((string) $m['started_at']))) ?></p>
                            </div>
                            <span class="shrink-0 text-[11px] font-medium px-2 py-0.5 rounded-full
                                <?= $m['state'] === 'in_progress' ? 'bg-amber-100 text-amber-800' : ($m['state'] === 'completed' ? 'bg-brand-light text-brand-dark' : 'bg-slate-100 text-slate-600') ?>">
                                <?= $m['state'] === 'in_progress' ? 'Bezig' : ($m['state'] === 'completed' ? 'Klaar' : 'Geannuleerd') ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php endif; ?>
