<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php $title = 'Spellen'; /** @var array $games */ ?>
<?php use GamesPool\Core\Admin; use GamesPool\Models\Game; ?>

<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-2xl font-bold text-navy">Spellen</h1>
        <p class="text-slate-500 text-sm">Configureer per spel hoe scores worden geteld.</p>
    </div>
    <?php if (Admin::is()): ?>
        <a href="<?= e(url('/games/new')) ?>"
           class="inline-flex items-center px-4 py-2 rounded-lg bg-brand text-white font-semibold hover:bg-brand-dark">
            + Nieuw spel
        </a>
    <?php endif; ?>
</div>

<?php if (empty($games)): ?>
    <div class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-card">
        <p class="text-slate-600">Nog geen spellen toegevoegd.</p>
        <?php if (Admin::is()): ?>
            <a href="<?= e(url('/games/new')) ?>" class="inline-block mt-4 px-4 py-2 rounded-lg bg-brand text-white font-semibold hover:bg-brand-dark">Voeg je eerste spel toe</a>
        <?php else: ?>
            <p class="text-slate-500 text-sm mt-2">Vraag een admin om een spel toe te voegen.</p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <ul class="space-y-2">
        <?php foreach ($games as $g): ?>
            <li class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-card">
                <div>
                    <p class="font-semibold text-navy"><?= e($g['name']) ?></p>
                    <p class="text-xs text-slate-500"><?= e(Game::scoreTypeLabel($g['score_type'])) ?></p>
                </div>
                <?php if (Admin::is()): ?>
                    <div class="flex items-center gap-2">
                        <a href="<?= e(url('/games/' . $g['slug'] . '/edit')) ?>"
                           class="px-3 py-1.5 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm">Bewerken</a>
                        <form method="post" action="<?= e(url('/games/' . $g['slug'])) ?>"
                              onsubmit="return confirm('Spel <?= e($g['name']) ?> verwijderen?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="_method" value="DELETE">
                            <button class="px-3 py-1.5 rounded-md bg-red-50 hover:bg-red-100 text-red-700 text-sm">Verwijder</button>
                        </form>
                    </div>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
