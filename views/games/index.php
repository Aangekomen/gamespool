<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php $title = 'Spellen'; /** @var array $games */ ?>
<?php use GamesPool\Models\Game; ?>

<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-2xl font-bold">Spellen</h1>
        <p class="text-slate-400 text-sm">Configureer per spel hoe scores worden geteld.</p>
    </div>
    <a href="<?= e(url('/games/new')) ?>"
       class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-500 text-slate-950 font-semibold hover:bg-emerald-400">
        + Nieuw spel
    </a>
</div>

<?php if (empty($games)): ?>
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-8 text-center">
        <p class="text-slate-300">Nog geen spellen toegevoegd.</p>
        <a href="<?= e(url('/games/new')) ?>" class="inline-block mt-4 px-4 py-2 rounded-lg bg-emerald-500 text-slate-950 font-semibold">Voeg je eerste spel toe</a>
    </div>
<?php else: ?>
    <ul class="space-y-2">
        <?php foreach ($games as $g): ?>
            <li class="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-900 px-4 py-3">
                <div>
                    <p class="font-semibold"><?= e($g['name']) ?></p>
                    <p class="text-xs text-slate-400"><?= e(Game::scoreTypeLabel($g['score_type'])) ?></p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="<?= e(url('/games/' . $g['slug'] . '/edit')) ?>"
                       class="px-3 py-1.5 rounded-md bg-slate-800 hover:bg-slate-700 text-sm">Bewerken</a>
                    <form method="post" action="<?= e(url('/games/' . $g['slug'])) ?>"
                          onsubmit="return confirm('Spel <?= e($g['name']) ?> verwijderen?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="_method" value="DELETE">
                        <button class="px-3 py-1.5 rounded-md bg-red-900/60 hover:bg-red-800 text-sm">Verwijder</button>
                    </form>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
