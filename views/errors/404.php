<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php $title = 'Niet gevonden'; ?>

<div class="text-center py-20">
    <p class="text-6xl font-black text-brand">404</p>
    <h1 class="mt-3 text-xl font-semibold text-navy">Deze pagina bestaat niet</h1>
    <p class="mt-2 text-slate-500">Klopt het adres?</p>
    <a href="<?= e(url('/')) ?>" class="inline-block mt-6 px-4 py-2 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-navy">Naar home</a>
</div>
