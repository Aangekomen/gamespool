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
    <div class="mt-4 rounded-2xl bg-white border border-slate-200 p-4 shadow-card">
        <h2 class="text-sm font-bold text-navy mb-3">Deelnemers</h2>
        <ul class="space-y-2">
            <?php foreach ($participants as $i => $p):
                $name = $p['display_name'] ?: ($p['guest_name'] ?: 'Onbekend');
            ?>
                <li class="flex items-center gap-3 rounded-lg bg-surface px-3 py-2.5">
                    <div class="w-9 h-9 rounded-full bg-brand-light text-brand-dark flex items-center justify-center font-bold shrink-0">
                        <?= e(strtoupper(mb_substr($name, 0, 1))) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-navy truncate"><?= e($name) ?></p>
                        <p class="text-xs text-slate-500"><?= $i === 0 ? 'host' : 'tegenstander' ?></p>
                    </div>
                    <span class="text-[10px] uppercase tracking-wide font-semibold text-brand-dark bg-brand-light px-2 py-0.5 rounded-full">✓</span>
                </li>
            <?php endforeach; ?>

            <?php if (count($participants) < 2): ?>
                <li class="flex items-center gap-3 rounded-lg border-2 border-dashed border-slate-200 px-3 py-3">
                    <div class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 shrink-0">?</div>
                    <p class="text-sm text-slate-500">Wachten op tegenstander…</p>
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
        <p class="text-xs text-slate-500 text-center mt-2">
            Door mee te doen wordt de match vergrendeld en starten we direct.
        </p>

    <?php elseif ($isHost && count($participants) < 2): ?>
        <!-- Host waiting: share screen -->
        <div class="mt-4 rounded-2xl bg-white border border-slate-200 p-5 shadow-card text-center">
            <h2 class="text-sm font-bold text-navy mb-2">Deel deze link</h2>
            <p class="text-xs text-slate-500 mb-4">Of laat de tegenstander de QR-code scannen.</p>

            <div class="bg-white inline-block rounded-xl border border-slate-200 p-2 mb-3">
                <img src="<?= e(url('/qr.svg?text=' . urlencode($shareUrl) . '&size=240')) ?>"
                     alt="QR" width="220" height="220" class="block">
            </div>

            <input type="text" readonly id="shareLink" value="<?= e($shareUrl) ?>"
                   class="w-full rounded-lg bg-surface border border-slate-300 px-3 py-2.5 text-sm font-mono text-navy text-center mb-3"
                   onclick="this.select()">

            <div class="grid grid-cols-3 gap-2">
                <button type="button" id="shareBtn" class="hidden rounded-lg bg-brand text-white font-semibold py-2.5 hover:bg-brand-dark">
                    Delen
                </button>
                <a href="https://wa.me/?text=<?= e(urlencode($shareText)) ?>" target="_blank" rel="noopener"
                   class="rounded-lg bg-[#25D366] text-white font-semibold py-2.5 hover:opacity-90 flex items-center justify-center gap-1 text-sm">
                    WhatsApp
                </a>
                <a href="mailto:?subject=<?= e(urlencode('Speel je mee?')) ?>&body=<?= e(urlencode($shareText)) ?>"
                   class="rounded-lg bg-slate-100 text-navy font-semibold py-2.5 hover:bg-slate-200 text-sm">
                    Mail
                </a>
                <button type="button" id="copyBtn"
                        class="rounded-lg bg-slate-100 text-navy font-semibold py-2.5 hover:bg-slate-200 text-sm">
                    Kopieer
                </button>
            </div>
        </div>

        <form method="post" action="<?= e(url('/matches/' . $match['id'] . '/cancel')) ?>"
              onsubmit="return confirm('Match annuleren?');" class="mt-3">
            <?= csrf_field() ?>
            <button class="w-full px-4 py-3 rounded-lg bg-white border border-slate-200 hover:bg-red-50 hover:text-red-700 text-slate-600">
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

        // Auto-refresh while waiting (poll every 4s)
        <?php if ($isHost && count($participants) < 2): ?>
            setInterval(() => location.reload(), 4000);
        <?php endif; ?>
    })();
</script>
