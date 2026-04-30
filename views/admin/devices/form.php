<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var ?array $device */
/** @var array $games */
/** @var array $errors */
$isEdit = $device !== null;
$title  = $isEdit ? 'Apparaat bewerken' : 'Nieuw apparaat';
$action = $isEdit ? url('/admin/devices/' . $device['id']) : url('/admin/devices');
$inputCls = 'w-full rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand';
?>

<div class="max-w-md mx-auto">
    <h1 class="text-2xl font-bold text-navy dark:text-slate-100 mb-1"><?= e($title) ?></h1>
    <p class="text-slate-500 dark:text-slate-400 text-sm mb-6">Geef het apparaat een naam en koppel het spel.</p>

    <form method="post" action="<?= e($action) ?>" class="space-y-4 bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-card">
        <?= csrf_field() ?>
        <?php if ($isEdit): ?><input type="hidden" name="_method" value="PATCH"><?php endif; ?>

        <div>
            <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5" for="name">Naam</label>
            <input id="name" name="name" type="text" required minlength="2" maxlength="120"
                   value="<?= e((string) old('name', $device['name'] ?? '')) ?>"
                   placeholder="Bijv. Pooltafel 1"
                   class="<?= $inputCls ?>">
            <?php foreach (($errors['name'] ?? []) as $err): ?>
                <p class="text-red-600 text-sm mt-1"><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>

        <div>
            <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5" for="game_id">Spel</label>
            <select id="game_id" name="game_id" class="<?= $inputCls ?>">
                <option value="">— geen koppeling —</option>
                <?php foreach ($games as $g): ?>
                    <option value="<?= e((string) $g['id']) ?>"
                            <?= (int) ($device['game_id'] ?? 0) === (int) $g['id'] ? 'selected' : '' ?>>
                        <?= e($g['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5" for="location">Locatie <span class="text-slate-400">(optioneel)</span></label>
            <input id="location" name="location" type="text" maxlength="120"
                   value="<?= e((string) old('location', $device['location'] ?? '')) ?>"
                   placeholder="Bijv. Achterzaal"
                   class="<?= $inputCls ?>">
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button class="flex-1 rounded-lg bg-brand text-white font-semibold px-4 py-3 hover:bg-brand-dark">
                <?= $isEdit ? 'Opslaan' : 'Toevoegen' ?>
            </button>
            <a href="<?= e(url('/admin/devices')) ?>" class="px-4 py-3 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">Annuleren</a>
        </div>
    </form>
</div>
