// FlexiComp service worker — minimaal, network-first met offline fallback
// voor de shell. Geen aggressieve caching: scores moeten vers zijn.

const CACHE_VERSION = 'flexicomp-v1';
const OFFLINE_URLS  = ['/offline.html'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_VERSION).then((cache) => cache.addAll(OFFLINE_URLS))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keys = await caches.keys();
        await Promise.all(keys.filter(k => k !== CACHE_VERSION).map(k => caches.delete(k)));
        await self.clients.claim();
    })());
});

// Web Push: toon notificatie zoals payload aangeeft
self.addEventListener('push', (event) => {
    let data = { title: 'FlexiComp', body: 'Er is iets gebeurd', url: '/' };
    if (event.data) {
        try { data = Object.assign(data, event.data.json()); } catch (e) {}
    }
    event.waitUntil(self.registration.showNotification(data.title, {
        body: data.body,
        icon: '/icon-192.svg',
        badge: '/icon-192.svg',
        data: { url: data.url || '/' },
        tag:  data.tag || 'flexicomp',
    }));
});

// Klik op notificatie: open de meegestuurde URL (of focus bestaande tab)
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = (event.notification.data && event.notification.data.url) || '/';
    event.waitUntil((async () => {
        const all = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        for (const c of all) {
            if (c.url.includes(target)) return c.focus();
        }
        return self.clients.openWindow(target);
    })());
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;

    const url = new URL(req.url);
    // Skip cross-origin and dynamic JSON endpoints
    if (url.origin !== self.location.origin) return;
    if (url.pathname.endsWith('.json') || url.pathname.includes('/state.json')) return;

    // Network-first voor HTML; val terug op offline-pagina
    if (req.headers.get('accept')?.includes('text/html')) {
        event.respondWith((async () => {
            try {
                return await fetch(req);
            } catch (e) {
                const cache = await caches.open(CACHE_VERSION);
                const offline = await cache.match('/offline.html');
                return offline || new Response('Offline', { status: 503 });
            }
        })());
        return;
    }
});
