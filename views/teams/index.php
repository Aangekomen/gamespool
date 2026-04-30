<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var array $teams */
/** @var array $pendingMine */
/** @var array $pendingPerTeam */
/** @var array $errors */
use GamesPool\Models\Team;
$title = 'Teams';
$inputCls = 'w-full rounded-lg bg-white border border-slate-300 px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand';
?>

<div class="mb-4">
    <h1 class="text-2xl font-bold text-navy">Teams</h1>
    <p class="text-slate-500 text-sm">Sluit aan met een 6-cijferige code of maak een nieuw team.</p>
</div>

<!-- Join via code -->
<div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-card mb-4">
    <h2 class="text-sm font-bold text-navy mb-3">Aansluiten met code</h2>
    <form method="post" action="<?= e(url('/teams/join')) ?>" class="flex items-stretch gap-2">
        <?= csrf_field() ?>
        <input type="text" name="join_code"
               inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="off" required
               placeholder="000000"
               class="flex-1 rounded-lg bg-white border border-slate-300 px-4 py-3 text-2xl tracking-[0.4em] text-center font-mono font-bold text-navy focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand">
        <button class="px-5 rounded-lg bg-brand text-white font-semibold hover:bg-brand-dark">Vraag aan</button>
    </form>
    <?php foreach (($errors['join_code'] ?? []) as $err): ?>
        <p class="text-red-600 text-sm mt-2"><?= e($err) ?></p>
    <?php endforeach; ?>
    <p class="text-xs text-slate-500 mt-2">De captain van het team moet je toelaten voordat je officieel meedoet.</p>
</div>

<!-- Pending requests for ME (waiting on a captain) -->
<?php if (!empty($pendingMine)): ?>
    <div class="rounded-2xl bg-amber-50 border border-amber-200 p-4 mb-4">
        <h2 class="text-sm font-bold text-amber-900 mb-2">Wacht op goedkeuring</h2>
        <ul class="space-y-1">
            <?php foreach ($pendingMine as $t): ?>
                <li class="flex items-center justify-between text-sm">
                    <span class="text-amber-900 font-medium"><?= e($t['name']) ?></span>
                    <span class="text-xs text-amber-700">verzonden <?= e(date('d-m H:i', strtotime((string) $t['requested_at']))) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Create new team -->
<details class="rounded-2xl bg-white border border-slate-200 p-5 shadow-card mb-4">
    <summary class="text-sm font-bold text-navy cursor-pointer select-none">+ Nieuw team aanmaken</summary>
    <form method="post" action="<?= e(url('/teams')) ?>" class="mt-3 flex items-stretch gap-2">
        <?= csrf_field() ?>
        <input type="text" name="name" required minlength="2" maxlength="100"
               placeholder="Teamnaam"
               class="<?= $inputCls ?>">
        <button class="px-5 rounded-lg bg-navy text-white font-semibold hover:bg-navy-soft">Maak</button>
    </form>
    <?php foreach (($errors['name'] ?? []) as $err): ?>
        <p class="text-red-600 text-sm mt-2"><?= e($err) ?></p>
    <?php endforeach; ?>
</details>

<!-- My teams -->
<div>
    <h2 class="text-sm font-bold text-navy mb-2 px-1">Mijn teams</h2>
    <?php if (empty($teams)): ?>
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center">
            <p class="text-slate-500 text-sm">Je zit nog in geen enkel team.</p>
        </div>
    <?php else: ?>
        <ul class="space-y-3">
            <?php foreach ($teams as $t):
                $count = Team::memberCount((int) $t['id']);
                $isCaptain = $t['role'] === 'captain';
                $pending = $pendingPerTeam[(int) $t['id']] ?? [];
            ?>
                <li class="rounded-xl border border-slate-200 bg-white shadow-card overflow-hidden">
                    <div class="flex items-center gap-3 px-4 py-3">
                        <div class="w-10 h-10 rounded-lg bg-brand-light text-brand-dark flex items-center justify-center font-bold shrink-0 overflow-hidden">
                            <?php if (!empty($t['logo_path'])): ?>
                                <img src="<?= e(url('/uploads/logos/' . $t['logo_path'])) ?>" alt="" class="w-full h-full object-cover">
                            <?php else: ?>
                                <?= e(strtoupper(mb_substr($t['name'], 0, 1))) ?>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-navy truncate"><?= e($t['name']) ?>
                                <?php if ($isCaptain): ?>
                                    <span class="ml-1 text-[10px] uppercase tracking-wide text-brand-dark bg-brand-light px-1.5 py-0.5 rounded">captain</span>
                                <?php endif; ?>
                            </p>
                            <p class="text-xs text-slate-500">
                                <?= $count ?> <?= $count === 1 ? 'lid' : 'leden' ?>
                                · Code: <span class="font-mono font-semibold tracking-wider text-navy"><?= e((string) $t['join_code']) ?></span>
                            </p>
                        </div>
                        <form method="post" action="<?= e(url('/teams/' . (int) $t['id'] . '/leave')) ?>"
                              onsubmit="return confirm('Team <?= e($t['name']) ?> verlaten?');">
                            <?= csrf_field() ?>
                            <button class="text-xs px-3 py-1.5 rounded-md bg-slate-100 hover:bg-red-50 hover:text-red-700 text-slate-500">Verlaat</button>
                        </form>
                    </div>

                    <?php if ($isCaptain && !empty($pending)): ?>
                        <div class="bg-amber-50 border-t border-amber-200 px-4 py-3">
                            <p class="text-xs font-bold text-amber-900 mb-2">
                                <?= count($pending) ?> verzoek<?= count($pending) === 1 ? '' : 'en' ?> in afwachting
                            </p>
                            <ul class="space-y-2">
                                <?php foreach ($pending as $req): ?>
                                    <li class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-white text-amber-900 flex items-center justify-center text-sm font-bold shrink-0">
                                            <?= e(strtoupper(mb_substr((string) $req['display_name'], 0, 1))) ?>
                                        </div>
                                        <p class="flex-1 text-sm text-amber-900 truncate font-medium"><?= e($req['display_name']) ?></p>
                                        <form method="post" action="<?= e(url('/teams/' . (int) $t['id'] . '/members/' . (int) $req['user_id'] . '/approve')) ?>" class="contents">
                                            <?= csrf_field() ?>
                                            <button class="text-xs px-3 py-1 rounded-md bg-brand text-white font-semibold hover:bg-brand-dark">Toelaten</button>
                                        </form>
                                        <form method="post" action="<?= e(url('/teams/' . (int) $t['id'] . '/members/' . (int) $req['user_id'] . '/reject')) ?>" class="contents">
                                            <?= csrf_field() ?>
                                            <button class="text-xs px-3 py-1 rounded-md bg-white border border-amber-300 text-amber-900 hover:bg-amber-100">×</button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
