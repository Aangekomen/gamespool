<?php /** @var array<string,string> $sections */ ?>
<!doctype html>
<html lang="nl" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <title><?= e($title ?? 'GamesPool') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><circle cx='32' cy='32' r='28' fill='%2322c55e'/><text x='50%25' y='55%25' text-anchor='middle' font-size='34' fill='white' font-family='Arial' font-weight='bold'>G</text></svg>">
    <style>
        body { -webkit-tap-highlight-color: transparent; }
    </style>
</head>
<body class="min-h-full flex flex-col">
    <header class="sticky top-0 z-30 bg-slate-900/90 backdrop-blur border-b border-slate-800">
        <div class="max-w-3xl mx-auto px-4 h-14 flex items-center justify-between">
            <a href="<?= e(url('/')) ?>" class="font-semibold tracking-tight text-emerald-400">GamesPool</a>
            <nav class="flex items-center gap-3 text-sm">
                <?php if (user()): ?>
                    <span class="hidden sm:inline text-slate-400">Hoi <?= e(user()['display_name']) ?></span>
                    <form method="post" action="<?= e(url('/logout')) ?>">
                        <?= csrf_field() ?>
                        <button class="px-3 py-1.5 rounded-md bg-slate-800 hover:bg-slate-700 active:bg-slate-700">Uitloggen</button>
                    </form>
                <?php else: ?>
                    <a href="<?= e(url('/login')) ?>" class="px-3 py-1.5 rounded-md hover:bg-slate-800">Inloggen</a>
                    <a href="<?= e(url('/register')) ?>" class="px-3 py-1.5 rounded-md bg-emerald-500 text-slate-950 font-medium hover:bg-emerald-400">Account</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="flex-1">
        <div class="max-w-3xl mx-auto px-4 py-6 sm:py-8">
            <?php if ($msg = flash('success')): ?>
                <div class="mb-4 rounded-md bg-emerald-900/40 border border-emerald-700 px-4 py-3 text-emerald-200 text-sm"><?= e((string) $msg) ?></div>
            <?php endif; ?>
            <?php if ($msg = flash('error')): ?>
                <div class="mb-4 rounded-md bg-red-900/40 border border-red-700 px-4 py-3 text-red-200 text-sm"><?= e((string) $msg) ?></div>
            <?php endif; ?>
            <?= $sections['content'] ?? '' ?>
        </div>
    </main>

    <footer class="text-center text-xs text-slate-500 py-6">
        GamesPool · <?= date('Y') ?>
    </footer>
</body>
</html>
