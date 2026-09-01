const CACHE_NAME = 'app-cache-v1';

const FILES_TO_CACHE = [
    '/offline.html',
    '/js/network-status.js'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(FILES_TO_CACHE))
            .catch(err => console.error('Cache failed', err))
    );
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', event => {
    event.respondWith(
        fetch(event.request).catch(() =>
            caches.match(event.request).then(res => res || caches.match('/offline.html'))
        )
    );
});

self.addEventListener('push', function (event) {
    let data = {};

    if (event.data) {
        data = event.data.json();
    }

    const title = data.title || 'Test Notification';
    const options = {
        body: data.body || 'This is a test push message',
        icon: '/icon.png',
        badge: '/icon.png',
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});