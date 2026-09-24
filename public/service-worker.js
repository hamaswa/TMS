const VERSION = '20260924m';
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
    '/assets/js/jquery.dataTables.min.js',
    '/assets/js/main.js',
    '/assets/js/custom.js',
    '/assets/js/form-accessibility.js',
    '/assets/js/confirm-modal.js',
    '/assets/js/offline-workspace.js',
    '/assets/owlcarousel/owl.carousel.min.js',
    '/assets/images/favicon.ico',
    '/assets/images/web-app-manifest-192x192.png',
    '/assets/fonts/noto-nastaliq-urdu/NotoNastaliqUrdu-VariableFont_wght.woff2',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/webfonts/fa-solid-900.woff2',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/webfonts/fa-regular-400.woff2',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/webfonts/fa-brands-400.woff2',
    'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css',
    'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js',
    'https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/moment.min.js',
    'https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.min.js',
    'https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css'
];

const TRUSTED_STATIC_ORIGINS = new Set([
    'https://cdnjs.cloudflare.com',
    'https://cdn.jsdelivr.net',
]);

const cacheStaticAsset = async (cache, asset) => {
    const url = new URL(asset, self.location.origin);
    const response = await fetch(url.toString(), url.origin === self.location.origin
        ? { credentials: 'same-origin', cache: 'no-store' }
        : { mode: 'no-cors', cache: 'no-store' });
    if (response.ok || response.type === 'opaque') await cache.put(url.toString(), response);
};

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => Promise.allSettled(STATIC_ASSETS.map((asset) => cacheStaticAsset(cache, asset))))
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
                .map((page) => ({ label: String(page.label || ''), url: new URL(page.url, self.location.origin), seed: true }))
                .filter((page) => page.url.origin === self.location.origin && isOperationalPage(page.url));
            const cache = await caches.open(PRIVATE_CACHE);
            const failed = [];
            const seen = new Set(pages.map((page) => normalizedPageUrl(page.url)));
            let completed = 0;
            let cached = 0;
            let skipped = 0;

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
                    const html = await response.clone().text();
                    await cache.put(page.url.toString(), response);
                    cached += 1;

                    if (pages.length < 400) {
                        for (const discoveredUrl of discoverRecordLinks(html, page.url)) {
                            const normalized = normalizedPageUrl(discoveredUrl);
                            if (seen.has(normalized) || pages.length >= 400) continue;
                            seen.add(normalized);
                            pages.push({
                                label: discoveredUrl.pathname.replace(/^\/admin\//, ''),
                                url: discoveredUrl,
                                seed: false,
                            });
                        }
                    }
                } catch (error) {
                    if (page.seed) failed.push({ label: page.label, url: page.url.toString() });
                    else skipped += 1;
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
                completed: cached,
                total: pages.length,
                failed,
                skipped,
                inventory: event.data.inventory || null,
                version: event.data.version || VERSION,
            });
        })());
    }
});

const normalizedPageUrl = (value) => {
    const url = value instanceof URL ? new URL(value.toString()) : new URL(value, self.location.origin);
    url.hash = '';
    return url.toString();
};

const discoverRecordLinks = (html, baseUrl) => {
    const links = [];
    const pattern = /href\s*=\s*["']([^"']+)["']/gi;
    let match;
    while ((match = pattern.exec(html)) !== null) {
        try {
            const href = match[1].replace(/&amp;/g, '&');
            const url = new URL(href, baseUrl);
            url.hash = '';
            url.search = '';
            if (url.origin !== self.location.origin || !isOperationalPage(url)) continue;
            if (!url.pathname.split('/').some((segment) => /^\d+$/.test(segment))) continue;
            if (/\/(export|payment-evidence)(\/|$)/i.test(url.pathname)) continue;
            if (/\/design\/price\//i.test(url.pathname)) continue;
            links.push(url);
        } catch (error) {
            // Ignore malformed or unsupported links from a rendered page.
        }
    }
    return links;
};

const isOperationalPage = (url) => {
    if (url.pathname.startsWith('/administrator') || url.pathname.includes('logout')) return false;

    return url.pathname.startsWith('/admin/')
        || url.pathname === '/admin'
        || url.pathname.startsWith('/tailor/tailor-dashboard')
        || url.pathname.startsWith('/tailor/tailor-order-list');
};

const isPrivatePageAsset = (request, url) => url.origin === self.location.origin
    && ['image', 'font'].includes(request.destination)
    && (url.pathname.startsWith('/storage/') || url.pathname.startsWith('/images/'));

const isTrustedStaticAsset = (request, url) => TRUSTED_STATIC_ORIGINS.has(url.origin)
    && ['style', 'script', 'font'].includes(request.destination);

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
        return;
    }

    if (isPrivatePageAsset(request, url) || isTrustedStaticAsset(request, url)) {
        event.respondWith((async () => {
            const cache = await caches.open(isPrivatePageAsset(request, url) ? PRIVATE_CACHE : STATIC_CACHE);
            try {
                const response = await networkWithTimeout(request, 3000);
                if (response.ok || response.type === 'opaque') await cache.put(request, response.clone());
                return response;
            } catch (error) {
                return (await cache.match(request, { ignoreSearch: true }))
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
