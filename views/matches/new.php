<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var array $games */
/** @var ?array $lockedGame */
/** @var array $users */
/** @var array $errors */
$title = 'Nieuwe match';
$inputCls = 'w-full rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand';
?>

<div class="max-w-lg mx-auto">
    <h1 class="text-2xl font-bold text-navy dark:text-slate-100 mb-1">Nieuwe match</h1>
    <p class="text-slate-500 dark:text-slate-400 text-sm mb-6">Kies het spel en voeg deelnemers toe. Daarna voer je de uitslag in.</p>

    <form id="matchForm" method="post" action="<?= e(url('/matches')) ?>"
          class="space-y-4 bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-card">
        <?= csrf_field() ?>

        <div>
            <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5" for="game_id">Spel</label>
            <?php if ($lockedGame): ?>
                <input type="hidden" name="game_id" value="<?= e((string) $lockedGame['id']) ?>">
                <div class="flex items-center justify-between rounded-lg bg-brand-light border border-brand/30 px-3 py-2.5 text-navy dark:text-slate-100">
                    <span class="font-semibold"><?= e($lockedGame['name']) ?></span>
                    <span class="text-[10px] uppercase tracking-wide font-semibold text-brand-dark">vast</span>
                </div>
            <?php else: ?>
                <select id="game_id" name="game_id" required class="<?= $inputCls ?>">
                    <?php foreach ($games as $g): ?>
                        <option value="<?= e((string) $g['id']) ?>" <?= (int) old('game_id') === (int) $g['id'] ? 'selected' : '' ?>>
                            <?= e($g['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            <?php foreach (($errors['game_id'] ?? []) as $err): ?>
                <p class="text-red-600 text-sm mt-1"><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>

        <div>
            <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5" for="label">Label <span class="text-slate-400">(optioneel)</span></label>
            <input id="label" name="label" type="text" maxlength="150"
                   value="<?= e((string) old('label')) ?>"
                   placeholder="bijv. Tafel 1"
                   class="<?= $inputCls ?>">
        </div>

        <div>
            <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5" for="device_code">
                Apparaat-code <span class="text-slate-400">(optioneel)</span>
            </label>
            <input id="device_code" name="device_code" type="text" maxlength="20" autocomplete="off"
                   value="<?= e((string) old('device_code')) ?>"
                   placeholder="bv. K7FXMZ — sta je bij een vaste tafel?"
                   class="<?= $inputCls ?> font-mono uppercase tracking-wider">
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Vul de 6-letter code in van het apparaat (op de QR-print) zodat de match aan dat apparaat hangt.
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-2">Deelnemers</label>
            <div id="parts" class="space-y-2"></div>
            <button type="button" onclick="addRow()" class="mt-2 px-3 py-2 rounded-md bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 text-sm">
                + Deelnemer toevoegen
            </button>
            <p id="dupErr" class="hidden text-red-600 text-sm mt-2">Dezelfde speler staat dubbel in de lijst.</p>
            <?php foreach (($errors['participants'] ?? []) as $err): ?>
                <p class="text-red-600 text-sm mt-1"><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button class="flex-1 rounded-lg bg-brand text-white font-semibold px-4 py-3 hover:bg-brand-dark">
                Match starten
            </button>
            <a href="<?= e(url('/matches')) ?>" class="px-4 py-3 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">Annuleren</a>
        </div>
    </form>
</div>

<template id="rowTpl">
    <div class="flex items-center gap-2 part-row">
        <select name="participants[user_id][]" required
                class="part-user flex-1 rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2.5">
            <option value="">— kies speler —</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= e((string) $u['id']) ?>"><?= e($u['display_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="button" onclick="removeRow(this)"
                class="px-3 py-2 rounded-md bg-slate-100 dark:bg-slate-800 hover:bg-red-50 hover:text-red-700 text-slate-500 dark:text-slate-400" aria-label="Verwijder">×</button>
    </div>
</template>

<script>
    const $parts = document.getElementById('parts');
    const $form  = document.getElementById('matchForm');
    const $err   = document.getElementById('dupErr');

    function addRow() {
        const tpl = document.getElementById('rowTpl');
        $parts.appendChild(tpl.content.cloneNode(true));
        wireRow($parts.lastElementChild);
        refreshUserOptions();
    }
    function removeRow(btn) {
        btn.closest('.part-row').remove();
        refreshUserOptions();
    }
    function wireRow(row) {
        row.querySelector('.part-user').addEventListener('change', refreshUserOptions);
    }
    function refreshUserOptions() {
        const selects = [...document.querySelectorAll('.part-user')];
        const taken = new Set(selects.map(s => s.value).filter(Boolean));
        selects.forEach(sel => {
            const own = sel.value;
            [...sel.options].forEach(opt => {
                if (!opt.value) return;
                opt.disabled = (opt.value !== own && taken.has(opt.value));
            });
        });
        $err.classList.add('hidden');
    }
    function checkDuplicates() {
        const users = [...document.querySelectorAll('.part-user')].map(s => s.value).filter(Boolean);
        return new Set(users).size !== users.length;
    }
    $form.addEventListener('submit', (ev) => {
        if (checkDuplicates()) {
            ev.preventDefault();
            $err.classList.remove('hidden');
            $err.scrollIntoView({behavior: 'smooth', block: 'center'});
        }
    });

    // Start with two rows; pre-select current user in first row if logged in
    addRow(); addRow();
    <?php if ($me = user()): ?>
        document.querySelector('.part-user').value = '<?= (int) $me['id'] ?>';
        refreshUserOptions();
    <?php endif; ?>
</script>
