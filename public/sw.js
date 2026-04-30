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
