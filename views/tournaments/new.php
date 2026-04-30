<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var array $games */
/** @var array $errors */
$title = 'Nieuw toernooi';
$inputCls = 'w-full rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand';
?>

<div class="max-w-md mx-auto">
    <h1 class="text-2xl font-bold text-navy dark:text-slate-100 mb-1">Nieuw toernooi</h1>
    <p class="text-slate-500 dark:text-slate-400 text-sm mb-6">Single-elimination — winnaar gaat door, verliezer ligt eruit.</p>

    <form method="post" action="<?= e(url('/tournaments')) ?>"
          class="space-y-4 bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-card">
        <?= csrf_field() ?>

        <div>
            <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5" for="name">Naam</label>
            <input id="name" name="name" type="text" required minlength="2" maxlength="150"
                   value="<?= e((string) old('name')) ?>"
                   class="<?= $inputCls ?>"
                   placeholder="Vrijdag-cup, Pools Open, ...">
            <?php foreach (($errors['name'] ?? []) as $err): ?>
                <p class="text-red-600 text-sm mt-1"><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>

        <div>
            <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5" for="game_id">Spel</label>
            <select id="game_id" name="game_id" required class="<?= $inputCls ?>">
                <?php foreach ($games as $g): ?>
                    <option value="<?= e((string) $g['id']) ?>"><?= e($g['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php foreach (($errors['game_id'] ?? []) as $err): ?>
                <p class="text-red-600 text-sm mt-1"><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>

        <div>
            <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5" for="starts_at">
                Starttijd <span class="text-slate-400">(optioneel)</span>
            </label>
            <input id="starts_at" name="starts_at" type="datetime-local"
                   value="<?= e((string) old('starts_at')) ?>"
                   class="<?= $inputCls ?>">
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Wanneer het toernooi gepland staat. Leeg laten = open inschrijving zonder vaste tijd.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-navy dark:text-slate-100 mb-1.5">Aantal spelers</label>
            <div class="grid grid-cols-4 gap-2 mb-2">
                <?php foreach ([2, 4, 8, 16] as $n): ?>
                    <label class="flex items-center justify-center rounded-md border border-slate-200 dark:border-slate-700 py-2 cursor-pointer hover:border-brand has-[:checked]:bg-brand-light dark:has-[:checked]:bg-brand-dark/25 has-[:checked]:border-brand has-[:checked]:text-brand-dark dark:has-[:checked]:text-brand-light font-semibold quick-pick">
                        <input type="radio" name="max_players" value="<?= $n ?>" <?= $n === 8 ? 'checked' : '' ?> class="sr-only" data-quick="1"> <?= $n ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-xs text-slate-500 dark:text-slate-400 shrink-0" for="max_players_custom">Of custom:</label>
                <input id="max_players_custom" type="number" min="2" max="32" placeholder="bv. 6"
                       class="<?= $inputCls ?> w-24">
                <span class="text-xs text-slate-400">(2 t/m 32)</span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Lege plekken worden bye's; die spelers gaan automatisch een ronde door.</p>
            <?php foreach (($errors['max_players'] ?? []) as $err): ?>
                <p class="text-red-600 text-sm mt-1"><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>

        <script>
        // Custom-aantal vult een verborgen radio in zodat de form 1 waarde post.
        (function () {
            const customInput = document.getElementById('max_players_custom');
            const radios = document.querySelectorAll('input[name="max_players"]');
            customInput.addEventListener('input', () => {
                const v = parseInt(customInput.value, 10);
                if (!v || v < 2 || v > 32) return;
                // Maak/zoek hidden radio met deze waarde
                let hidden = document.querySelector('input[name="max_players"][data-quick="0"]');
                if (!hidden) {
                    hidden = document.createElement('input');
                    hidden.type = 'radio'; hidden.name = 'max_players';
                    hidden.dataset.quick = '0'; hidden.className = 'hidden';
                    customInput.parentElement.appendChild(hidden);
                }
                hidden.value = String(v); hidden.checked = true;
                document.querySelectorAll('.quick-pick').forEach(l => l.classList.remove('ring'));
            });
            radios.forEach(r => r.addEventListener('change', () => {
                if (r.dataset.quick === '1') customInput.value = '';
            }));
        })();
        </script>

        <div class="flex items-center gap-3 pt-2">
            <button class="flex-1 rounded-lg bg-brand text-white font-semibold px-4 py-3 hover:bg-brand-dark">Aanmaken</button>
            <a href="<?= e(url('/tournaments')) ?>" class="px-4 py-3 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">Annuleren</a>
        </div>
    </form>
</div>
