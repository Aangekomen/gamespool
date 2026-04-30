<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var array $match */
/** @var ?array $game */
/** @var ?array $device */
/** @var array $participants */
/** @var bool $isHost */
/** @var bool $isParticipant */
/** @var string $shareUrl */

$title = 'Match-uitnodiging';
$shareText = 'Speel je een potje ' . ($game['name'] ?? '') . ' met me? ' . $shareUrl;
?>

<div class="max-w-md mx-auto">
    <div class="rounded-2xl bg-navy text-white p-5 shadow-card">
        <p class="text-xs uppercase tracking-wide text-white/60 font-semibold">
            <?= $device ? 'Apparaat' : 'Match' ?>
        </p>
        <h1 class="text-2xl font-bold">
            <?= e($device['name'] ?? ($game['name'] ?? 'Match')) ?>
        </h1>
        <?php if ($game): ?>
            <p class="text-sm text-white/70 mt-0.5"><?= e($game['name']) ?></p>
        <?php endif; ?>
    </div>

    <!-- Participants -->
    <div class="mt-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card">
        <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-3">Deelnemers</h2>
        <ul class="space-y-2">
            <?php foreach ($participants as $i => $p):
                $name = $p['display_name'] ?? 'Onbekend';
            ?>
                <li class="flex items-center gap-3 rounded-lg bg-surface dark:bg-slate-950 px-3 py-2.5">
                    <div class="w-9 h-9 rounded-full bg-brand-light text-brand-dark flex items-center justify-center font-bold shrink-0 overflow-hidden">
                        <?php if (!empty($p['avatar_path'])): ?>
                            <img src="<?= e(url('/uploads/avatars/' . $p['avatar_path'])) ?>" alt="" class="w-full h-full object-cover">
                        <?php else: ?>
                            <?= e(strtoupper(mb_substr($name, 0, 1))) ?>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-navy dark:text-slate-100 truncate"><?= e($name) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400"><?= $i === 0 ? 'host' : 'tegenstander' ?></p>
                    </div>
                    <span class="text-[10px] uppercase tracking-wide font-semibold text-brand-dark bg-brand-light px-2 py-0.5 rounded-full">✓</span>
                </li>
            <?php endforeach; ?>

            <?php if (count($participants) < 2): ?>
                <li class="flex items-center gap-3 rounded-lg border-2 border-dashed border-slate-200 dark:border-slate-800 px-3 py-3">
                    <div class="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 shrink-0">?</div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Wachten op tegenstander…</p>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <?php if (!$isParticipant): ?>
        <!-- Visitor: accept the invite -->
        <form method="post" action="<?= e(url('/m/' . $match['join_token'] . '/accept')) ?>" class="mt-4">
            <?= csrf_field() ?>
            <button class="w-full rounded-xl bg-brand text-white text-base font-bold px-4 py-4 hover:bg-brand-dark shadow-card">
                ✓ Ik doe mee
            </button>
        </form>
        <p class="text-xs text-slate-500 dark:text-slate-400 text-center mt-2">
            Door mee te doen wordt de match vergrendeld en starten we direct.
        </p>

    <?php elseif ($isHost && count($participants) < 2): ?>
        <!-- Host waiting: share screen -->
        <div class="mt-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-card text-center">
            <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-2">Deel deze link</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Of laat de tegenstander de QR-code scannen.</p>

            <div class="bg-white dark:bg-slate-900 inline-block rounded-xl border border-slate-200 dark:border-slate-800 p-2 mb-3">
                <img src="<?= e(url('/qr.svg?text=' . urlencode($shareUrl) . '&size=240')) ?>"
                     alt="QR" width="220" height="220" class="block">
            </div>

            <input type="text" readonly id="shareLink" value="<?= e($shareUrl) ?>"
                   class="w-full rounded-lg bg-surface dark:bg-slate-950 border border-slate-300 dark:border-slate-700 px-3 py-2.5 text-sm font-mono text-navy dark:text-slate-100 text-center mb-3"
                   onclick="this.select()">

            <button type="button" id="shareBtn"
                    class="hidden w-full mb-2 rounded-lg bg-brand text-white font-semibold py-3 hover:bg-brand-dark">
                Delen via apparaat
            </button>
            <div class="grid grid-cols-3 gap-2">
                <a href="https://wa.me/?text=<?= e(urlencode($shareText)) ?>" target="_blank" rel="noopener"
                   class="min-h-[44px] rounded-lg bg-[#25D366] text-white font-semibold hover:opacity-90 flex items-center justify-center text-sm">
                    WhatsApp
                </a>
                <a href="mailto:?subject=<?= e(urlencode('Speel je mee?')) ?>&body=<?= e(urlencode($shareText)) ?>"
                   class="min-h-[44px] rounded-lg bg-slate-100 dark:bg-slate-800 text-navy dark:text-slate-100 font-semibold hover:bg-slate-200 flex items-center justify-center text-sm">
                    Mail
                </a>
                <button type="button" id="copyBtn"
                        class="min-h-[44px] rounded-lg bg-slate-100 dark:bg-slate-800 text-navy dark:text-slate-100 font-semibold hover:bg-slate-200 text-sm">
                    Kopieer
                </button>
            </div>
        </div>

        <form method="post" action="<?= e(url('/matches/' . $match['id'] . '/cancel')) ?>"
              onsubmit="return confirm('Match annuleren?');" class="mt-3">
            <?= csrf_field() ?>
            <button class="w-full px-4 py-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-red-50 hover:text-red-700 text-slate-600 dark:text-slate-300">
                Annuleer match
            </button>
        </form>
    <?php endif; ?>
</div>

<script>
    (function () {
        const url   = <?= json_encode($shareUrl) ?>;
        const text  = <?= json_encode($shareText) ?>;

        const shareBtn = document.getElementById('shareBtn');
        if (shareBtn && navigator.share) {
            shareBtn.classList.remove('hidden');
            shareBtn.addEventListener('click', () => {
                navigator.share({ title: 'FlexiComp match', text, url }).catch(() => {});
            });
        }

        const copyBtn = document.getElementById('copyBtn');
        if (copyBtn) {
            copyBtn.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(url);
                    copyBtn.textContent = 'Gekopieerd ✓';
                    setTimeout(() => copyBtn.textContent = 'Kopieer', 1500);
                } catch {
                    document.getElementById('shareLink').select();
                    document.execCommand('copy');
                }
            });
        }

        // Lichte JSON-poll: alleen herladen wanneer er echt iets verandert.
        // Geen volledig page-refresh elke paar seconden meer.
        <?php if ($isHost && count($participants) < 2): ?>
            const initial = <?= (int) count($participants) ?>;
            const stateUrl = <?= json_encode(url('/m/' . $match['join_token'] . '/state.json')) ?>;
            let stops = 0;
            const poll = async () => {
                try {
                    const res = await fetch(stateUrl, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) throw new Error('bad status');
                    const j = await res.json();
                    if (j.ok && (j.state !== 'waiting' || (j.participant_count|0) > initial)) {
                        location.reload();
                        return;
                    }
                    stops = 0;
                } catch (e) {
                    if (++stops > 5) return; // give up after a while
                }
                setTimeout(poll, 5000);
            };
            setTimeout(poll, 5000);
        <?php endif; ?>
    })();
</script>
