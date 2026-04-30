<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php $title = 'GamesPool — score tracker'; ?>

<section class="text-center pt-6 pb-10">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-navy mb-4 relative">
        <div class="absolute inset-3 rounded-lg bg-brand"></div>
    </div>
    <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-navy">Houd je scores bij voor elk spel</h1>
    <p class="mt-3 text-slate-600 max-w-xl mx-auto">
        Pool, darts, en alles wat je in de bar speelt. Maak teams, start competities, scan een QR en je doet mee.
    </p>

    <?php if (!user()): ?>
        <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
            <a href="<?= e(url('/register')) ?>" class="inline-flex justify-center items-center px-5 py-3 rounded-lg bg-brand text-white font-semibold hover:bg-brand-dark">Account aanmaken</a>
            <a href="<?= e(url('/login')) ?>" class="inline-flex justify-center items-center px-5 py-3 rounded-lg bg-white border border-slate-200 text-navy hover:bg-slate-50">Inloggen</a>
        </div>
    <?php else: ?>
        <p class="mt-6 text-slate-600">Welkom terug, <span class="text-navy font-semibold"><?= e(user()['display_name']) ?></span>.</p>
        <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
            <a href="<?= e(url('/matches/new')) ?>" class="inline-flex justify-center items-center px-5 py-3 rounded-lg bg-brand text-white font-semibold hover:bg-brand-dark">+ Nieuwe match</a>
            <a href="<?= e(url('/leaderboard')) ?>" class="inline-flex justify-center items-center px-5 py-3 rounded-lg bg-white border border-slate-200 text-navy hover:bg-slate-50">Ranglijst</a>
            <a href="<?= e(url('/matches')) ?>" class="inline-flex justify-center items-center px-5 py-3 rounded-lg bg-white border border-slate-200 text-navy hover:bg-slate-50">Recente matches</a>
        </div>
    <?php endif; ?>
</section>
