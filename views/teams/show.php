<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var array $team */
/** @var array $members */
/** @var array $matches */
/** @var bool $isMember */
/** @var bool $isCaptain */
$title = $team['name'];
$myId = (int) (user()['id'] ?? 0);
$inviteUrl = url('/teams/join?code=' . urlencode((string) $team['join_code']));
?>

<div class="max-w-2xl mx-auto">
    <a href="<?= e(url('/teams')) ?>" class="text-sm text-slate-500 dark:text-slate-400 hover:text-navy">← teams</a>

    <!-- Header -->
    <div class="rounded-2xl bg-navy text-white p-5 shadow-card mt-2 mb-4 flex items-center gap-4">
        <div class="w-14 h-14 rounded-lg bg-brand-light text-brand-dark flex items-center justify-center text-2xl font-bold shrink-0 overflow-hidden">
            <?php if (!empty($team['logo_path'])): ?>
                <img src="<?= e(url('/uploads/logos/' . $team['logo_path'])) ?>" alt="" class="w-full h-full object-cover">
            <?php else: ?>
                <?= e(strtoupper(mb_substr($team['name'], 0, 1))) ?>
            <?php endif; ?>
        </div>
        <div class="flex-1 min-w-0">
            <h1 class="text-xl font-bold truncate"><?= e($team['name']) ?></h1>
            <p class="text-sm text-white/70">
                <?= count($members) ?> <?= count($members) === 1 ? 'lid' : 'leden' ?>
                <?php if ($isMember): ?>
                    · Code: <span class="font-mono font-semibold tracking-wider"><?= e((string) $team['join_code']) ?></span>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Invite QR (members only) -->
    <?php if ($isMember): ?>
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-4 text-center">
            <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-2">Nodig iemand uit</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Laat de scanner deze QR scannen — code wordt automatisch ingevuld.</p>
            <div class="bg-white dark:bg-white inline-block rounded-xl border border-slate-200 dark:border-slate-700 p-2">
                <img src="<?= e(url('/qr.svg?text=' . urlencode($inviteUrl) . '&size=200')) ?>"
                     alt="Invite QR" width="200" height="200" class="block">
            </div>
            <p class="mt-3 text-xs font-mono text-slate-500 dark:text-slate-400 break-all"><?= e($inviteUrl) ?></p>
        </div>
    <?php endif; ?>

    <!-- Members -->
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-4">
        <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-3">Leden</h2>
        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($members as $m):
                $name = trim(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? '')) ?: ($m['display_name'] ?? '–');
                $isMe = (int) $m['id'] === $myId;
                $isThemCaptain = $m['role'] === 'captain';
            ?>
                <li class="py-2.5">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-brand-light text-brand-dark flex items-center justify-center font-bold shrink-0 overflow-hidden">
                            <?php if (!empty($m['avatar_path'])): ?>
                                <img src="<?= e(url('/uploads/avatars/' . $m['avatar_path'])) ?>" alt="" class="w-full h-full object-cover">
                            <?php else: ?>
                                <?= e(strtoupper(mb_substr($name, 0, 1))) ?>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-navy dark:text-slate-100 truncate">
                                <?= e($name) ?>
                                <?php if (!empty($m['tag'])): ?>
                                    <span class="ml-1 text-[11px] font-mono font-medium text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">#<?= e((string) $m['tag']) ?></span>
                                <?php endif; ?>
                            </p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                <?= $isThemCaptain ? 'captain' : 'lid' ?>
                                · sinds <?= e(date('d-m-Y', strtotime((string) $m['joined_at']))) ?>
                            </p>
                        </div>
                        <?php if ($isCaptain): ?>
                            <button type="button" onclick="document.getElementById('tag-form-<?= (int) $m['id'] ?>').classList.toggle('hidden')"
                                    class="min-h-[32px] text-xs px-2 rounded-md bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-600 dark:text-slate-300">
                                tag
                            </button>
                            <?php if (!$isThemCaptain): ?>
                                <form method="post" action="<?= e(url('/teams/' . (int) $team['id'] . '/members/' . (int) $m['id'] . '/kick')) ?>"
                                      onsubmit="return confirm('<?= e($name) ?> uit het team verwijderen?');" class="contents">
                                    <?= csrf_field() ?>
                                    <button class="min-h-[32px] text-xs px-2 rounded-md bg-white dark:bg-slate-900 border border-red-200 dark:border-red-900/40 text-red-600 hover:bg-red-50">×</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($isCaptain): ?>
                        <form id="tag-form-<?= (int) $m['id'] ?>" method="post"
                              action="<?= e(url('/teams/' . (int) $team['id'] . '/members/' . (int) $m['id'] . '/tag')) ?>"
                              class="hidden mt-2 flex items-center gap-2 pl-12">
                            <?= csrf_field() ?>
                            <input type="text" name="tag" maxlength="20" placeholder="bv. Sniper"
                                   value="<?= e((string) ($m['tag'] ?? '')) ?>"
                                   class="flex-1 rounded-md bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 px-2 py-1.5 text-sm font-mono">
                            <button class="text-xs px-3 py-1.5 rounded-md bg-brand text-white font-semibold">Opslaan</button>
                        </form>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Matches -->
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-4">
        <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-3">Gespeelde matches</h2>
        <?php if (empty($matches)): ?>
            <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">Nog geen matches als team gespeeld.</p>
        <?php else: ?>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                <?php foreach ($matches as $m): ?>
                    <li>
                        <a href="<?= e(url('/matches/' . $m['id'])) ?>"
                           class="flex items-center justify-between py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800 -mx-2 px-2 rounded-md">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-navy dark:text-slate-100 truncate"><?= e($m['game_name']) ?><?= $m['label'] ? ' · ' . e($m['label']) : '' ?></p>
                                <p class="text-xs text-slate-500 dark:text-slate-400"><?= e(date('d-m-Y H:i', strtotime((string) $m['started_at']))) ?></p>
                            </div>
                            <span class="shrink-0 text-[11px] font-medium px-2 py-0.5 rounded-full
                                <?= $m['state'] === 'in_progress' ? 'bg-amber-100 text-amber-800' : ($m['state'] === 'completed' ? 'bg-brand-light text-brand-dark' : 'bg-slate-100 text-slate-600') ?>">
                                <?= $m['state'] === 'in_progress' ? 'Bezig' : ($m['state'] === 'completed' ? 'Klaar' : '×') ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <?php if ($isMember):
        $others = array_values(array_filter($members, fn($m) => (int) $m['id'] !== $myId));
        $hasOthers = !empty($others);
    ?>
        <?php if ($isCaptain && $hasOthers): ?>
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card">
                <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-2">Captainship overdragen & verlaten</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Als captain kun je niet zomaar weg — kies eerst een opvolger.</p>
                <form method="post" action="<?= e(url('/teams/' . (int) $team['id'] . '/transfer-leave')) ?>"
                      onsubmit="return confirm('Weet je het zeker? Je verliest je captain-rol en verlaat het team.');"
                      class="space-y-2">
                    <?= csrf_field() ?>
                    <select name="new_captain_id" required
                            class="w-full rounded-lg bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 px-3 py-2.5 text-base">
                        <option value="">— kies nieuwe captain —</option>
                        <?php foreach ($others as $o):
                            $oname = trim(($o['first_name'] ?? '') . ' ' . ($o['last_name'] ?? '')) ?: ($o['display_name'] ?? '–');
                        ?>
                            <option value="<?= (int) $o['id'] ?>"><?= e($oname) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="w-full min-h-[44px] rounded-lg bg-white dark:bg-slate-900 border border-red-200 dark:border-red-900/40 text-red-700 font-semibold hover:bg-red-50">
                        Overdragen en verlaten
                    </button>
                </form>
            </div>
        <?php else: ?>
            <form method="post" action="<?= e(url('/teams/' . (int) $team['id'] . '/leave')) ?>"
                  onsubmit="return confirm('Team <?= e($team['name']) ?> verlaten?');">
                <?= csrf_field() ?>
                <button class="w-full min-h-[44px] rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:bg-red-50 hover:text-red-700 hover:border-red-200">
                    Team verlaten
                </button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>
