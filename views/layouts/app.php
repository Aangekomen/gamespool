<?php /** @var array<string,string> $sections */ ?>
<?php
$nav = [
    'home'    => ['label' => 'Home',      'url' => url('/'),            'icon' => 'home',     'match' => ['/']],
    'matches' => ['label' => 'Matches',   'url' => url('/matches'),     'icon' => 'matches',  'match' => ['/matches', '/matches/']],
    'teams'   => ['label' => 'Teams',     'url' => url('/teams'),       'icon' => 'teams',    'match' => ['/teams']],
    'rank'    => ['label' => 'Ranglijst', 'url' => url('/leaderboard'), 'icon' => 'rank',     'match' => ['/leaderboard']],
    'profile' => ['label' => 'Profiel',   'url' => url('/profile'),     'icon' => 'profile',  'match' => ['/profile']],
];
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isActive = function (array $matches) use ($path): bool {
    foreach ($matches as $m) {
        if ($m === '/') { if ($path === '/') return true; continue; }
        if (str_starts_with($path, $m)) return true;
    }
    return false;
};
$icon = function (string $name): string {
    return match ($name) {
        'home'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3 11l9-8 9 8v9a2 2 0 0 1-2 2h-4v-7H9v7H5a2 2 0 0 1-2-2v-9z"/></svg>',
        'matches' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg>',
        'teams'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'rank'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h4V10H3v11zM10 21h4V3h-4v18zM17 21h4V14h-4v7z"/></svg>',
        'profile' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><circle cx="12" cy="8" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 21a8 8 0 0 1 16 0"/></svg>',
        default   => '',
    };
};
?>
<!doctype html>
<html lang="nl" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#171a56" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0f172a" media="(prefers-color-scheme: dark)">
    <title><?= e($title ?? 'FlexiComp') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        // Apply theme before paint to avoid flash
        (function () {
            try {
                const stored = localStorage.getItem('theme');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const dark = stored ? stored === 'dark' : prefersDark;
                if (dark) document.documentElement.classList.add('dark');
            } catch (e) {}
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
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
        @supports (padding: env(safe-area-inset-bottom)) {
            .pb-safe { padding-bottom: calc(env(safe-area-inset-bottom) + 0.5rem); }
        }
        html.dark { color-scheme: dark; }
    </style>
</head>
<body class="min-h-full flex flex-col bg-surface text-slate-800 dark:bg-slate-950 dark:text-slate-200">
    <header class="sticky top-0 z-30 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800">
        <div class="max-w-3xl mx-auto px-4 h-14 flex items-center justify-between">
            <a href="<?= e(url('/')) ?>" class="flex items-center gap-2 font-bold tracking-tight text-navy dark:text-slate-100">
                <span class="inline-block w-6 h-6 rounded-md bg-navy dark:bg-slate-700 relative">
                    <span class="absolute inset-1 rounded-sm bg-brand"></span>
                </span>
                FlexiComp
            </a>
            <nav class="flex items-center gap-1 text-sm">
                <button id="themeToggle" type="button" aria-label="Wissel donker / licht thema"
                        class="w-9 h-9 rounded-md text-slate-500 hover:text-navy dark:text-slate-400 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center">
                    <!-- Sun (shown in dark) -->
                    <svg class="w-5 h-5 hidden dark:inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32l1.41-1.41"/></svg>
                    <!-- Moon (shown in light) -->
                    <svg class="w-5 h-5 inline dark:hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </button>
                <?php if (user()): ?>
                    <?php if (\GamesPool\Core\Admin::is()): ?>
                        <a href="<?= e(url('/admin')) ?>" class="px-3 py-1.5 rounded-md text-brand-dark bg-brand-light hover:bg-brand/20 font-medium">Admin</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?= e(url('/login')) ?>" class="px-3 py-1.5 rounded-md text-slate-600 dark:text-slate-300 hover:text-navy dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800">Inloggen</a>
                    <a href="<?= e(url('/register')) ?>" class="px-3 py-1.5 rounded-md bg-brand hover:bg-brand-dark text-white font-medium">Account</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="flex-1 pb-24">
        <div class="max-w-3xl mx-auto px-4 py-6 sm:py-8">
            <?php if (user() && empty(user()['email_verified_at'])): ?>
                <div class="mb-4 rounded-md bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/40 px-4 py-3 text-amber-900 dark:text-amber-200 text-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <span>Bevestig je e-mailadres — we hebben je een mail gestuurd.</span>
                    <form method="post" action="<?= e(url('/verify/resend')) ?>" class="shrink-0">
                        <?= csrf_field() ?>
                        <button class="text-xs font-semibold underline hover:no-underline">Opnieuw versturen</button>
                    </form>
                </div>
            <?php endif; ?>
            <?php if ($msg = flash('success')): ?>
                <div data-flash class="mb-4 rounded-md bg-brand-light border border-brand/30 px-4 py-3 text-brand-dark text-sm font-medium transition-opacity duration-500"><?= e((string) $msg) ?></div>
            <?php endif; ?>
            <?php if ($msg = flash('error')): ?>
                <div data-flash class="mb-4 rounded-md bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/40 px-4 py-3 text-red-700 dark:text-red-300 text-sm transition-opacity duration-500"><?= e((string) $msg) ?></div>
            <?php endif; ?>
            <?= $sections['content'] ?? '' ?>
        </div>
    </main>

    <?php if (user()): ?>
        <nav class="fixed bottom-0 inset-x-0 z-30 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 pb-safe shadow-[0_-1px_4px_rgba(15,23,42,0.04)]">
            <div class="max-w-3xl mx-auto grid grid-cols-5">
                <?php foreach ($nav as $key => $item): $active = $isActive($item['match']); ?>
                    <a href="<?= e($item['url']) ?>"
                       class="flex flex-col items-center gap-0.5 py-2.5 text-[11px] font-medium transition <?= $active ? 'text-brand-dark' : 'text-slate-500 dark:text-slate-400 hover:text-navy dark:hover:text-slate-100' ?>">
                        <?= $icon($item['icon']) ?>
                        <span><?= e($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </nav>
    <?php endif; ?>

    <script>
        (function () {
            const btn  = document.getElementById('themeToggle');
            if (btn) {
                btn.addEventListener('click', () => {
                    const isDark = document.documentElement.classList.toggle('dark');
                    try { localStorage.setItem('theme', isDark ? 'dark' : 'light'); } catch (e) {}
                });
            }

            // Auto-dismiss flash messages after 10s
            document.querySelectorAll('[data-flash]').forEach(el => {
                setTimeout(() => {
                    el.style.opacity = '0';
                    setTimeout(() => el.remove(), 600);
                }, 10000);
            });
        })();
    </script>
</body>
</html>
