<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var array $games */
/** @var array $users */
/** @var array $errors */
$title = 'Nieuwe match';
?>

<div class="max-w-lg mx-auto">
    <h1 class="text-2xl font-bold mb-1">Nieuwe match</h1>
    <p class="text-slate-400 text-sm mb-6">Kies het spel en voeg deelnemers toe. Daarna voer je de uitslag in.</p>

    <form method="post" action="<?= e(url('/matches')) ?>" class="space-y-4">
        <?= csrf_field() ?>

        <div>
            <label class="block text-sm font-medium mb-1.5" for="game_id">Spel</label>
            <select id="game_id" name="game_id" required
                    class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <?php foreach ($games as $g): ?>
                    <option value="<?= e((string) $g['id']) ?>" <?= (int) old('game_id') === (int) $g['id'] ? 'selected' : '' ?>>
                        <?= e($g['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php foreach (($errors['game_id'] ?? []) as $err): ?>
                <p class="text-red-400 text-sm mt-1"><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1.5" for="label">Label <span class="text-slate-500">(optioneel)</span></label>
            <input id="label" name="label" type="text" maxlength="150"
                   value="<?= e((string) old('label')) ?>"
                   placeholder="bijv. Tafel 1"
                   class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-emerald-500">
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">Deelnemers</label>
            <div id="parts" class="space-y-2"></div>
            <button type="button" onclick="addRow()" class="mt-2 px-3 py-2 rounded-md bg-slate-800 hover:bg-slate-700 text-sm">
                + Deelnemer toevoegen
            </button>
            <?php foreach (($errors['participants'] ?? []) as $err): ?>
                <p class="text-red-400 text-sm mt-1"><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button class="flex-1 rounded-lg bg-emerald-500 text-slate-950 font-semibold px-4 py-3 hover:bg-emerald-400">
                Match starten
            </button>
            <a href="<?= e(url('/matches')) ?>" class="px-4 py-3 rounded-lg bg-slate-800 hover:bg-slate-700">Annuleren</a>
        </div>
    </form>
</div>

<template id="rowTpl">
    <div class="flex items-center gap-2">
        <select name="participants[user_id][]"
                class="flex-1 rounded-lg bg-slate-900 border border-slate-700 px-3 py-2.5">
            <option value="">— gast (naam invullen) —</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= e((string) $u['id']) ?>"><?= e($u['display_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="participants[guest_name][]" placeholder="Gastnaam"
               class="w-32 sm:w-40 rounded-lg bg-slate-900 border border-slate-700 px-3 py-2.5">
        <button type="button" onclick="this.parentElement.remove()"
                class="px-3 py-2 rounded-md bg-slate-800 hover:bg-red-900/60" aria-label="Verwijder">×</button>
    </div>
</template>

<script>
    function addRow() {
        const tpl = document.getElementById('rowTpl');
        document.getElementById('parts').appendChild(tpl.content.cloneNode(true));
    }
    // Start with two rows: current user pre-selected if possible
    addRow(); addRow();
    <?php if ($me = user()): ?>
        document.querySelector('#parts select').value = '<?= (int) $me['id'] ?>';
    <?php endif; ?>
</script>
