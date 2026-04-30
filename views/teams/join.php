<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php /** @var array $errors */ $title = 'Word lid van een team'; ?>

<div class="max-w-md mx-auto">
    <a href="<?= e(url('/teams')) ?>" class="text-sm text-slate-500 dark:text-slate-400 hover:text-navy">← terug</a>
    <h1 class="text-2xl font-bold text-navy dark:text-slate-100 mt-2 mb-1">Word lid van een team</h1>
    <p class="text-slate-500 dark:text-slate-400 text-sm mb-4">
        Elk team heeft een unieke <strong class="text-navy dark:text-slate-100">6-cijferige code</strong>.
        Vraag de captain om die code en vul hem hieronder in. Daarna ziet de captain
        je verzoek en moet die je toelaten voordat je officieel meedoet.
    </p>

    <form method="post" action="<?= e(url('/teams/join')) ?>"
          class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-card">
        <?= csrf_field() ?>
        <label for="join_code" class="block text-sm font-medium text-navy dark:text-slate-100 mb-2">Join-code</label>
<?php $prefill = preg_replace('/\D/', '', (string) ($_GET['code'] ?? '')) ?? ''; if (strlen($prefill) > 6) $prefill = substr($prefill, 0, 6); ?>
        <input id="join_code" type="text" name="join_code" <?= $prefill === '' ? 'autofocus' : '' ?>
               inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="off" required
               value="<?= e($prefill) ?>"
               placeholder="000000"
               class="w-full rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-4 py-3 text-3xl tracking-[0.4em] text-center font-mono font-bold text-navy dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand">

        <?php foreach (($errors['join_code'] ?? []) as $err): ?>
            <p class="text-red-600 text-sm mt-2"><?= e($err) ?></p>
        <?php endforeach; ?>

        <button class="mt-4 w-full min-h-[48px] rounded-lg bg-brand text-white font-semibold hover:bg-brand-dark">
            Verzoek versturen
        </button>
    </form>
</div>
