<?php /** @var array<string,string> $sections */ ?>
<!doctype html>
<html lang="nl" class="h-full bg-white text-slate-800">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#171a56">
    <title><?= e($title ?? 'GamesPool') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand:      { DEFAULT: '#35b782', dark: '#2ba16f', light: '#e8f6f0' },
                        navy:       { DEFAULT: '#171a56', soft: '#3a3d7a' },
                        ink:        '#2b2b2b',
                        surface:    '#f6f6f6',
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'Segoe UI', 'sans-serif'],
                    },
                    boxShadow: {
                        card: '0 1px 2px rgba(15,23,42,0.04), 0 1px 3px rgba(15,23,42,0.06)',
                    },
                },
            },
        };
    </script>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><rect width='64' height='64' rx='14' fill='%23171a56'/><circle cx='32' cy='32' r='14' fill='%2335b782'/></svg>">
    <style>
        body { -webkit-tap-highlight-color: transparent; font-family: 'Inter', system-ui, sans-serif; }
    </style>
</head>
<body class="min-h-full flex flex-col bg-surface">
    <header class="sticky top-0 z-30 bg-white border-b border-slate-200">
        <div class="max-w-3xl mx-auto px-4 h-14 flex items-center justify-between">
            <a href="<?= e(url('/')) ?>" class="flex items-center gap-2 font-bold tracking-tight text-navy">
                <span class="inline-block w-6 h-6 rounded-md bg-navy relative">
                    <span class="absolute inset-1 rounded-sm bg-brand"></span>
                </span>
                GamesPool
            </a>
            <nav class="flex items-center gap-1 sm:gap-2 text-sm">
                <?php if (user()): ?>
                    <a href="<?= e(url('/leaderboard')) ?>" class="px-3 py-1.5 rounded-md text-slate-600 hover:text-navy hover:bg-slate-100">Ranglijst</a>
                    <a href="<?= e(url('/matches')) ?>" class="px-3 py-1.5 rounded-md text-slate-600 hover:text-navy hover:bg-slate-100">Matches</a>
                    <a href="<?= e(url('/games')) ?>" class="px-3 py-1.5 rounded-md text-slate-600 hover:text-navy hover:bg-slate-100">Spellen</a>
                    <form method="post" action="<?= e(url('/logout')) ?>">
                        <?= csrf_field() ?>
                        <button class="px-3 py-1.5 rounded-md text-slate-500 hover:text-navy hover:bg-slate-100">Uitloggen</button>
                    </form>
                <?php else: ?>
                    <a href="<?= e(url('/login')) ?>" class="px-3 py-1.5 rounded-md text-slate-600 hover:text-navy hover:bg-slate-100">Inloggen</a>
                    <a href="<?= e(url('/register')) ?>" class="px-3 py-1.5 rounded-md bg-brand hover:bg-brand-dark text-white font-medium">Account</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="flex-1">
        <div class="max-w-3xl mx-auto px-4 py-6 sm:py-8">
            <?php if ($msg = flash('success')): ?>
                <div class="mb-4 rounded-md bg-brand-light border border-brand/30 px-4 py-3 text-brand-dark text-sm font-medium"><?= e((string) $msg) ?></div>
            <?php endif; ?>
            <?php if ($msg = flash('error')): ?>
                <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-red-700 text-sm"><?= e((string) $msg) ?></div>
            <?php endif; ?>
            <?= $sections['content'] ?? '' ?>
        </div>
    </main>

    <footer class="text-center text-xs text-slate-400 py-6">
        GamesPool · <?= date('Y') ?>
    </footer>
</body>
</html>
