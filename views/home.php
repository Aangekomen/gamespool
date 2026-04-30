<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php $title = 'GamesPool — score tracker'; ?>

<section class="text-center pt-8 pb-12">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-emerald-500 text-slate-950 text-3xl font-black mb-4">G</div>
    <h1 class="text-3xl sm:text-4xl font-bold tracking-tight">Houd je scores bij voor elk spel</h1>
    <p class="mt-3 text-slate-400 max-w-xl mx-auto">
        Pool, darts, en alles wat je in de bar speelt. Maak teams, start competities, scan een QR en je doet mee.
    </p>

    <?php if (!user()): ?>
        <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
            <a href="<?= e(url('/register')) ?>" class="inline-flex justify-center items-center px-5 py-3 rounded-lg bg-emerald-500 text-slate-950 font-semibold hover:bg-emerald-400">Account aanmaken</a>
            <a href="<?= e(url('/login')) ?>" class="inline-flex justify-center items-center px-5 py-3 rounded-lg bg-slate-800 hover:bg-slate-700">Inloggen</a>
        </div>
    <?php else: ?>
        <p class="mt-6 text-slate-300">Welkom terug, <span class="text-emerald-400 font-medium"><?= e(user()['display_name']) ?></span>.</p>
        <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
            <a href="<?= e(url('/matches/new')) ?>" class="inline-flex justify-center items-center px-5 py-3 rounded-lg bg-emerald-500 text-slate-950 font-semibold hover:bg-emerald-400">+ Nieuwe match</a>
            <a href="<?= e(url('/leaderboard')) ?>" class="inline-flex justify-center items-center px-5 py-3 rounded-lg bg-slate-800 hover:bg-slate-700">Ranglijst</a>
            <a href="<?= e(url('/matches')) ?>" class="inline-flex justify-center items-center px-5 py-3 rounded-lg bg-slate-800 hover:bg-slate-700">Recente matches</a>
        </div>
    <?php endif; ?>
</section>
