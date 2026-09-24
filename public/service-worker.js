const VERSION = '20260924f';
const STATIC_CACHE = `tms-static-${VERSION}`;
const PRIVATE_CACHE = `tms-private-${VERSION}`;
const STATIC_ASSETS = [
    '/offline',
    '/manifest.webmanifest',
    '/assets/css/bootstrap.min.css',
    '/assets/css/main.css',
    '/assets/css/responsive.css',
    '/assets/js/jquery-3.5.1.min.js',
    '/assets/js/popper.min.js',
    '/assets/js/bootstrap.min.js',
    '/assets/js/main.js',
    '/assets/js/custom.js',
    '/assets/js/offline-workspace.js',
    '/assets/images/web-app-manifest-192x192.png'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => Promise.allSettled(STATIC_ASSETS.map((asset) => cache.add(asset))))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys
                .filter((key) => key.startsWith('tms-') && ![STATIC_CACHE, PRIVATE_CACHE].includes(key))
                .map((key) => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('message', (event) => {
    if (event.data?.type === 'CLEAR_PRIVATE_DATA') {
        event.waitUntil(caches.delete(PRIVATE_CACHE));
    }
    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    if (event.data?.type === 'CACHE_CURRENT_PAGE' && event.data.url) {
        event.waitUntil((async () => {
            const url = new URL(event.data.url);
            if (url.origin !== self.location.origin || !isOperationalPage(url)) return;
            const response = await fetch(url.toString(), { credentials: 'same-origin' });
            const contentType = response.headers.get('content-type') || '';
            if (response.ok && !response.redirected && contentType.includes('text/html')) {
                const cache = await caches.open(PRIVATE_CACHE);
                await cache.put(url.toString(), response);
            }
        })());
    }
    if (event.data?.type === 'PREPARE_OFFLINE_WORKSPACE' && Array.isArray(event.data.pages)) {
        event.waitUntil((async () => {
            const source = event.source;
            const pages = event.data.pages
                .map((page) => ({ label: String(page.label || ''), url: new URL(page.url, self.location.origin) }))
                .filter((page) => page.url.origin === self.location.origin && isOperationalPage(page.url));
            const cache = await caches.open(PRIVATE_CACHE);
            const failed = [];
            let completed = 0;

            source?.postMessage({
                type: 'OFFLINE_PREPARE_PROGRESS',
                status: 'preparing',
                completed,
                total: pages.length,
            });

            for (const page of pages) {
                try {
                    const response = await fetch(page.url.toString(), {
                        credentials: 'same-origin',
                        cache: 'no-store',
                    });
                    const contentType = response.headers.get('content-type') || '';
                    if (!response.ok || response.redirected || !contentType.includes('text/html')) {
                        throw new Error(`offline-page-${response.status}`);
                    }
                    page.url.hash = '';
                    await cache.put(page.url.toString(), response);
                } catch (error) {
                    failed.push({ label: page.label, url: page.url.toString() });
                }
                completed += 1;
                source?.postMessage({
                    type: 'OFFLINE_PREPARE_PROGRESS',
                    status: 'preparing',
                    completed,
                    total: pages.length,
                    current: page.label,
                });
            }

            source?.postMessage({
                type: 'OFFLINE_PREPARE_COMPLETE',
                status: failed.length ? 'partial' : 'ready',
                completed: pages.length - failed.length,
                total: pages.length,
                failed,
                version: event.data.version || VERSION,
            });
        })());
    }
});

const isOperationalPage = (url) => {
    if (url.pathname.startsWith('/administrator') || url.pathname.includes('logout')) return false;

    return url.pathname.startsWith('/admin/')
        || url.pathname === '/admin'
        || url.pathname.startsWith('/tailor/tailor-dashboard')
        || url.pathname.startsWith('/tailor/tailor-order-list');
};

const networkWithTimeout = (request, timeout = 4500) => Promise.race([
    fetch(request),
    new Promise((_, reject) => setTimeout(() => reject(new Error('network-timeout')), timeout))
]);

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET') return;

    if (request.mode === 'navigate' && url.origin === self.location.origin && isOperationalPage(url)) {
        event.respondWith((async () => {
            const cache = await caches.open(PRIVATE_CACHE);
            try {
                const response = await networkWithTimeout(request);
                const contentType = response.headers.get('content-type') || '';
                if (response.ok && !response.redirected && contentType.includes('text/html')) {
                    await cache.put(request, response.clone());
                }
                return response;
            } catch (error) {
                return (await cache.match(request))
                    || (await caches.match('/offline'))
                    || new Response('Offline', { status: 503, headers: { 'Content-Type': 'text/plain' } });
            }
        })());
        return;
    }

    if (url.origin === self.location.origin && url.pathname.startsWith('/assets/')) {
        event.respondWith((async () => {
            try {
                const response = await networkWithTimeout(request, 3000);
                if (response.ok) {
                    const cache = await caches.open(STATIC_CACHE);
                    await cache.put(url.pathname, response.clone());
                }
                return response;
            } catch (error) {
                return (await caches.match(request, { ignoreSearch: true }))
                    || new Response('', { status: 504, statusText: 'Offline asset unavailable' });
            }
        })());
    }
});

// Preserve the existing web-push behavior while this same worker also owns
// the authenticated offline workspace cache.
self.addEventListener('push', (event) => {
    if (!event.data) return;

    const notification = event.data.json();
    event.waitUntil(
        self.registration.showNotification(notification.title, {
            body: notification.body,
            icon: './assets/images/favicon.ico',
            data: { url: notification.url },
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    if (event.notification.data?.url) {
        event.waitUntil(clients.openWindow(event.notification.data.url));
    }
});
