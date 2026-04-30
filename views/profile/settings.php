<?php \GamesPool\Core\View::extend('layouts/app'); ?>
<?php
/** @var array $user */
/** @var array $errors */
$title = 'Instellingen';
$inputCls = 'w-full rounded-lg bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 px-3 py-2.5 text-base text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand';
$avatarSrc = !empty($user['avatar_path']) ? url('/uploads/avatars/' . $user['avatar_path']) : null;
$initial   = strtoupper(mb_substr((string) ($user['display_name'] ?? '?'), 0, 1));
?>

<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-2xl font-bold text-navy dark:text-slate-100">Instellingen</h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm">Beheer je account, foto en wachtwoord.</p>
    </div>
    <a href="<?= e(url('/profile')) ?>" class="text-sm text-slate-500 dark:text-slate-400 hover:text-navy">← profiel</a>
</div>

<!-- PWA install — verschijnt alleen als de browser de app kan installeren -->
<button id="installAppBtn" type="button"
        class="hidden w-full mb-3 rounded-2xl bg-brand text-white font-semibold py-3 px-4 hover:bg-brand-dark flex items-center justify-center gap-2 shadow-card">
    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m-4-4 4 4 4-4M5 21h14"/>
    </svg>
    Installeer FlexiComp op dit toestel
</button>

<!-- Account-overzicht header -->
<div class="rounded-2xl bg-navy text-white p-4 shadow-card mb-4 flex items-center gap-3">
    <div class="w-14 h-14 rounded-full bg-white/15 flex items-center justify-center text-xl font-bold shrink-0 overflow-hidden">
        <?php if ($avatarSrc): ?>
            <img src="<?= e($avatarSrc) ?>" alt="" class="w-full h-full object-cover">
        <?php else: ?>
            <?= e($initial) ?>
        <?php endif; ?>
    </div>
    <div class="min-w-0 flex-1">
        <p class="font-bold truncate"><?= e((string) $user['display_name']) ?></p>
        <p class="text-xs text-white/70 truncate"><?= e((string) $user['email']) ?></p>
        <p class="text-[11px] text-white/50 mt-0.5">
            <?php if (!empty($user['email_verified_at'])): ?>
                ✓ E-mail bevestigd
            <?php else: ?>
                ! E-mail nog niet bevestigd
            <?php endif; ?>
        </p>
    </div>
</div>

<!-- Sticky sectie-navigatie -->
<nav class="sticky top-14 z-20 -mx-4 px-4 py-2 bg-surface dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 mb-3 overflow-x-auto">
    <div class="flex items-center gap-2 text-sm whitespace-nowrap">
        <a href="#account" class="px-3 py-1.5 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-navy dark:text-slate-100 font-medium">Account</a>
        <a href="#foto" class="px-3 py-1.5 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-navy dark:text-slate-100 font-medium">Foto</a>
        <a href="#wachtwoord" class="px-3 py-1.5 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-navy dark:text-slate-100 font-medium">Wachtwoord</a>
        <a href="#voorkeuren" class="px-3 py-1.5 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-navy dark:text-slate-100 font-medium">Voorkeuren</a>
        <a href="#thema" class="px-3 py-1.5 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-navy dark:text-slate-100 font-medium">Thema</a>
        <a href="#gevarenzone" class="px-3 py-1.5 rounded-full bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/40 text-red-700 dark:text-red-300 font-medium">Gevarenzone</a>
    </div>
</nav>

<!-- Account / persoonsgegevens -->
<section id="account" class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-3 scroll-mt-32">
    <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-3 flex items-center gap-2">
        <svg class="w-4 h-4 text-brand-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path stroke-linecap="round" d="M4 21a8 8 0 0 1 16 0"/></svg>
        Persoonsgegevens
    </h2>
    <form method="post" action="<?= e(url('/profile')) ?>" class="space-y-3">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PATCH">
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Voornaam</label>
                <input type="text" name="first_name" required minlength="2" maxlength="80"
                       value="<?= e((string) $user['first_name']) ?>" class="<?= $inputCls ?>">
                <?php foreach (($errors['first_name'] ?? []) as $err): ?><p class="text-red-600 text-xs mt-1"><?= e($err) ?></p><?php endforeach; ?>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Achternaam</label>
                <input type="text" name="last_name" required minlength="2" maxlength="80"
                       value="<?= e((string) $user['last_name']) ?>" class="<?= $inputCls ?>">
                <?php foreach (($errors['last_name'] ?? []) as $err): ?><p class="text-red-600 text-xs mt-1"><?= e($err) ?></p><?php endforeach; ?>
            </div>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">E-mail</label>
            <input type="email" name="email" required maxlength="190"
                   value="<?= e($user['email']) ?>" class="<?= $inputCls ?>">
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Bij wijziging sturen we een nieuwe bevestigingsmail.</p>
            <?php foreach (($errors['email'] ?? []) as $err): ?><p class="text-red-600 text-xs mt-1"><?= e($err) ?></p><?php endforeach; ?>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Bedrijf <span class="text-slate-400">(optioneel)</span></label>
            <input type="text" name="company" maxlength="150"
                   value="<?= e((string) ($user['company_name'] ?? '')) ?>" class="<?= $inputCls ?>">
        </div>
        <button class="w-full min-h-[44px] rounded-lg bg-brand text-white font-semibold hover:bg-brand-dark">Gegevens opslaan</button>
    </form>
</section>

<!-- Profielfoto -->
<section id="foto" class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-3 scroll-mt-32">
    <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-3 flex items-center gap-2">
        <svg class="w-4 h-4 text-brand-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h3l2-3h6l2 3h3v12H4z"/><circle cx="12" cy="13" r="4"/></svg>
        Profielfoto
    </h2>
    <form method="post" action="<?= e(url('/profile/avatar')) ?>" enctype="multipart/form-data" class="space-y-3">
        <?= csrf_field() ?>
        <div class="flex items-center gap-3">
            <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xl font-bold text-slate-500 dark:text-slate-400 shrink-0 overflow-hidden">
                <?php if ($avatarSrc): ?>
                    <img id="avatarPreview" src="<?= e($avatarSrc) ?>" alt="" class="w-full h-full object-cover">
                <?php else: ?>
                    <span id="avatarPreview"><?= e($initial) ?></span>
                <?php endif; ?>
            </div>
            <!-- Native file input is verborgen; we triggeren hem via een eigen knop
                 zodat de browser-default tekst "Geen bestand gekozen" niet zichtbaar is -->
            <input type="file" name="avatar" id="avatarFile" accept="image/jpeg,image/png,image/webp" required class="sr-only">
            <div class="flex-1 min-w-0">
                <label for="avatarFile"
                       class="inline-flex items-center gap-2 cursor-pointer rounded-lg bg-brand-light dark:bg-brand-dark/25 text-brand-dark dark:text-brand-light font-semibold text-sm px-3 py-2 hover:bg-brand/20">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/>
                    </svg>
                    <span id="avatarBtnLabel"><?= $avatarSrc ? 'Vervang foto' : 'Kies een foto' ?></span>
                </label>
                <p id="avatarFileName" class="text-xs text-slate-500 dark:text-slate-400 mt-1 truncate">
                    <?= $avatarSrc ? 'Huidige foto wordt vervangen na upload' : 'Nog geen foto geselecteerd' ?>
                </p>
            </div>
        </div>
        <p class="text-[11px] text-slate-500 dark:text-slate-400">JPG, PNG of WebP, max ~10 MB. Wordt server-side bijgesneden tot 256×256.</p>
        <button class="w-full min-h-[44px] rounded-lg bg-brand text-white font-semibold hover:bg-brand-dark disabled:opacity-50 disabled:cursor-not-allowed" id="avatarUploadBtn" disabled>
            Kies eerst een foto
        </button>
    </form>
</section>

<!-- Wachtwoord -->
<section id="wachtwoord" class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-3 scroll-mt-32">
    <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-3 flex items-center gap-2">
        <svg class="w-4 h-4 text-brand-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 11c0-1.66 1.34-3 3-3s3 1.34 3 3v2H6v-2c0-1.66 1.34-3 3-3s3 1.34 3 3z M5 13h14v8H5z"/></svg>
        Wachtwoord wijzigen
    </h2>
    <form method="post" action="<?= e(url('/profile/password')) ?>" class="space-y-3">
        <?= csrf_field() ?>
        <div>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Huidig wachtwoord</label>
            <input type="password" name="current_password" autocomplete="current-password" required class="<?= $inputCls ?>">
            <?php foreach (($errors['current_password'] ?? []) as $err): ?><p class="text-red-600 text-xs mt-1"><?= e($err) ?></p><?php endforeach; ?>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Nieuw wachtwoord</label>
            <input id="newPwd" type="password" name="new_password" autocomplete="new-password" required minlength="8" class="<?= $inputCls ?>">
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Minimaal <strong>8 tekens</strong>. Mix van letters, cijfers en symbolen is sterker.</p>
            <div class="mt-1 h-1.5 rounded bg-slate-200 dark:bg-slate-700 overflow-hidden">
                <div id="pwdMeter" class="h-full w-0 bg-red-500 transition-all"></div>
            </div>
            <?php foreach (($errors['new_password'] ?? []) as $err): ?><p class="text-red-600 text-xs mt-1"><?= e($err) ?></p><?php endforeach; ?>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Herhaal nieuw wachtwoord</label>
            <input type="password" name="new_password_confirmation" autocomplete="new-password" required minlength="8" class="<?= $inputCls ?>">
        </div>
        <button class="w-full min-h-[44px] rounded-lg bg-brand text-white font-semibold hover:bg-brand-dark">Wijzig wachtwoord</button>
    </form>
</section>

<!-- Voorkeuren: spel-filter -->
<?php $_allGames = \GamesPool\Models\Game::all(); $_activeSlug = \GamesPool\Core\ActiveGame::slug(); ?>
<section id="voorkeuren" class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-3 scroll-mt-32">
    <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-1 flex items-center gap-2">
        <svg class="w-4 h-4 text-brand-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M6 12h12M10 18h4"/></svg>
        Spel-voorkeur
    </h2>
    <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">
        Filter de app op één spel. Je matches en stats blijven globaal — alleen de lijsten op
        home, /matches en /leaderboard worden gefilterd. Wissel later via deze plek.
    </p>
    <form method="post" action="<?= e(url('/active-game')) ?>" class="flex flex-wrap gap-2">
        <?= csrf_field() ?>
        <button name="game" value="" type="submit"
                class="text-sm px-3 py-2 rounded-full border <?= $_activeSlug === null
                    ? 'bg-navy text-white border-navy'
                    : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-brand' ?>">
            Alle spellen
        </button>
        <?php foreach ($_allGames as $g): $sel = $_activeSlug === $g['slug']; ?>
            <button name="game" value="<?= e($g['slug']) ?>" type="submit"
                    class="text-sm px-3 py-2 rounded-full border <?= $sel
                        ? 'bg-navy text-white border-navy'
                        : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-brand' ?>">
                <?= e($g['name']) ?>
            </button>
        <?php endforeach; ?>
    </form>
</section>

<!-- Notificaties (web-push) -->
<section id="notificaties" class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-3 scroll-mt-32">
    <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-3 flex items-center gap-2">
        <svg class="w-4 h-4 text-brand-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .53-.21 1.04-.59 1.41L4 17h5m6 0a3 3 0 1 1-6 0"/></svg>
        Notificaties
    </h2>
    <p class="text-sm text-slate-600 dark:text-slate-300 mb-3">
        Krijg een melding wanneer iemand je uitslag ter bevestiging stuurt, of als er een rematch klaarstaat.
        Je kan dit altijd uitzetten via je browser.
    </p>
    <div id="pushUnsupported" class="hidden text-xs text-slate-500 dark:text-slate-400 mb-2"></div>
    <div class="grid grid-cols-2 gap-2">
        <button id="pushSubBtn" type="button"
                class="hidden col-span-2 rounded-lg bg-brand text-white font-semibold py-2.5 hover:bg-brand-dark">
            Notificaties aanzetten
        </button>
        <button id="pushUnsubBtn" type="button"
                class="hidden rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-red-50 hover:text-red-700 text-slate-600 dark:text-slate-300 font-semibold py-2.5">
            Uitzetten
        </button>
        <form method="post" action="<?= e(url('/push/test')) ?>" id="pushTestForm" class="hidden">
            <?= csrf_field() ?>
            <button class="w-full rounded-lg bg-slate-100 dark:bg-slate-800 text-navy dark:text-slate-100 font-semibold py-2.5 hover:bg-slate-200 dark:hover:bg-slate-700">
                Stuur test
            </button>
        </form>
    </div>
</section>

<!-- Thema-keuze -->
<section id="thema" class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-card mb-3 scroll-mt-32">
    <h2 class="text-sm font-bold text-navy dark:text-slate-100 mb-3 flex items-center gap-2">
        <svg class="w-4 h-4 text-brand-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32l1.41-1.41"/></svg>
        Uiterlijk
    </h2>
    <div class="grid grid-cols-3 gap-2" id="themeChoices">
        <button type="button" data-theme="light"
                class="theme-opt rounded-lg border-2 border-slate-200 dark:border-slate-700 px-3 py-3 text-sm font-semibold text-navy dark:text-slate-100 bg-white dark:bg-slate-800 hover:border-brand">
            ☀️ Licht
        </button>
        <button type="button" data-theme="dark"
                class="theme-opt rounded-lg border-2 border-slate-200 dark:border-slate-700 px-3 py-3 text-sm font-semibold text-navy dark:text-slate-100 bg-white dark:bg-slate-800 hover:border-brand">
            🌙 Donker
        </button>
        <button type="button" data-theme="auto"
                class="theme-opt rounded-lg border-2 border-slate-200 dark:border-slate-700 px-3 py-3 text-sm font-semibold text-navy dark:text-slate-100 bg-white dark:bg-slate-800 hover:border-brand">
            🖥️ Auto
        </button>
    </div>
    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2">Wordt onthouden in deze browser.</p>
</section>

<!-- Gevarenzone -->
<section id="gevarenzone" class="rounded-2xl bg-white dark:bg-slate-900 border border-red-200 dark:border-red-900/40 p-4 shadow-card scroll-mt-32">
    <h2 class="text-sm font-bold text-red-700 mb-3 flex items-center gap-2">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4M12 17h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
        Account verwijderen
    </h2>
    <form method="post" action="<?= e(url('/profile/delete')) ?>" class="space-y-3"
          onsubmit="return confirm('Weet je dit echt zeker? Dit kan niet ongedaan worden gemaakt.');">
        <?= csrf_field() ?>
        <p class="text-sm text-slate-600 dark:text-slate-300">
            Hiermee verwijder je je account permanent. Je matches blijven anoniem in de geschiedenis staan; teams die jij hebt opgericht worden óók verwijderd.
            Type <strong>VERWIJDER</strong> ter bevestiging.
        </p>
        <input type="text" name="confirm" placeholder="VERWIJDER" required
               class="<?= $inputCls ?> uppercase tracking-widest">
        <button class="w-full min-h-[44px] rounded-lg bg-red-600 text-white font-semibold hover:bg-red-700">
            Verwijder mijn account
        </button>
    </form>
</section>

<script>
(function () {
    // Live preview van profielfoto + label/knop bijwerken op selectie
    const file       = document.getElementById('avatarFile');
    const preview    = document.getElementById('avatarPreview');
    const fileName   = document.getElementById('avatarFileName');
    const uploadBtn  = document.getElementById('avatarUploadBtn');
    const btnLabel   = document.getElementById('avatarBtnLabel');
    if (file) {
        file.addEventListener('change', () => {
            const f = file.files?.[0];
            if (!f) return;
            // Preview
            if (preview) {
                const url = URL.createObjectURL(f);
                if (preview.tagName === 'IMG') {
                    preview.src = url;
                } else {
                    const img = document.createElement('img');
                    img.id = 'avatarPreview';
                    img.src = url; img.className = 'w-full h-full object-cover';
                    preview.replaceWith(img);
                }
            }
            if (fileName)  fileName.textContent  = f.name + ' geselecteerd';
            if (btnLabel)  btnLabel.textContent  = 'Andere foto';
            if (uploadBtn) { uploadBtn.disabled = false; uploadBtn.textContent = 'Upload foto'; }
        });
    }

    // Wachtwoord-sterkte meter
    const pwd  = document.getElementById('newPwd');
    const meter = document.getElementById('pwdMeter');
    if (pwd && meter) {
        pwd.addEventListener('input', () => {
            const v = pwd.value;
            let s = 0;
            if (v.length >= 8) s++;
            if (v.length >= 12) s++;
            if (/[A-Z]/.test(v) && /[a-z]/.test(v)) s++;
            if (/\d/.test(v)) s++;
            if (/[^A-Za-z0-9]/.test(v)) s++;
            const pct = Math.min(100, s * 20);
            meter.style.width = pct + '%';
            meter.className = 'h-full transition-all ' +
                (s <= 1 ? 'bg-red-500' : s <= 2 ? 'bg-amber-500' : s <= 3 ? 'bg-amber-400' : 'bg-brand');
        });
    }

    // Web Push aan/uit-flow
    (async function () {
        const subBtn   = document.getElementById('pushSubBtn');
        const unsubBtn = document.getElementById('pushUnsubBtn');
        const testForm = document.getElementById('pushTestForm');
        const note     = document.getElementById('pushUnsupported');
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            note.textContent = 'Push-notificaties worden niet ondersteund door deze browser.';
            note.classList.remove('hidden');
            return;
        }
        const reg = await navigator.serviceWorker.ready;
        let existing = await reg.pushManager.getSubscription();

        function reflect() {
            subBtn.classList.toggle('hidden',  !!existing);
            unsubBtn.classList.toggle('hidden', !existing);
            testForm.classList.toggle('hidden', !existing);
        }
        reflect();

        function urlB64ToUint8Array(b64) {
            const padding = '='.repeat((4 - b64.length % 4) % 4);
            const base64 = (b64 + padding).replace(/-/g, '+').replace(/_/g, '/');
            const raw = atob(base64);
            const arr = new Uint8Array(raw.length);
            for (let i = 0; i < raw.length; i++) arr[i] = raw.charCodeAt(i);
            return arr;
        }

        subBtn.addEventListener('click', async () => {
            try {
                const keyRes = await fetch('/push/key');
                const { key } = await keyRes.json();
                if (!key) {
                    note.textContent = 'Push is nog niet geconfigureerd op de server (VAPID-keys ontbreken).';
                    note.classList.remove('hidden');
                    return;
                }
                const perm = await Notification.requestPermission();
                if (perm !== 'granted') return;
                const sub = await reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlB64ToUint8Array(key),
                });
                const csrf = document.querySelector('input[name=_csrf]').value;
                await fetch('/push/subscribe', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                    body: JSON.stringify(sub.toJSON()),
                });
                existing = sub; reflect();
                if (window.fc) fc.success();
            } catch (e) {
                note.textContent = 'Subscribe mislukt: ' + e.message;
                note.classList.remove('hidden');
            }
        });

        unsubBtn.addEventListener('click', async () => {
            if (!existing) return;
            const endpoint = existing.endpoint;
            try {
                await existing.unsubscribe();
                const csrf = document.querySelector('input[name=_csrf]').value;
                await fetch('/push/unsubscribe', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                    body: JSON.stringify({ endpoint }),
                });
                existing = null; reflect();
            } catch (e) {}
        });
    })();

    // Thema-keuze
    const opts = document.querySelectorAll('.theme-opt');
    function syncActive() {
        const cur = (function () {
            try {
                const stored = localStorage.getItem('theme');
                if (stored === 'dark' || stored === 'light') return stored;
            } catch (e) {}
            return 'auto';
        })();
        opts.forEach(b => {
            const active = b.dataset.theme === cur;
            b.classList.toggle('border-brand', active);
            b.classList.toggle('bg-brand-light', active);
            b.classList.toggle('text-brand-dark', active);
        });
    }
    opts.forEach(b => b.addEventListener('click', () => {
        const v = b.dataset.theme;
        try {
            if (v === 'auto') localStorage.removeItem('theme');
            else localStorage.setItem('theme', v);
        } catch (e) {}
        const dark = v === 'dark' || (v === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', dark);
        syncActive();
    }));
    syncActive();
})();
</script>
