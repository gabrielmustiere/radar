// Repli hors ligne de l'app installée, et rien d'autre : aucune page de Radar n'est mise en cache,
// pour ne jamais montrer un contenu périmé ni celui d'un autre compte.
// Tout changement de offline.html incrémente CACHE, sinon les apps installées gardent l'ancienne page.
const CACHE = 'radar-offline-v1';
const OFFLINE_URL = '/offline.html';

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((cache) => cache.add(new Request(OFFLINE_URL, { cache: 'reload' }))));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    if (event.request.mode !== 'navigate') {
        return;
    }

    event.respondWith(fetch(event.request).catch(() => caches.match(OFFLINE_URL)));
});
