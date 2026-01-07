/**
 * Service Worker for PWA
 * 
 * Provides offline support and caching
 * 
 * @author Danny Duong
 */

const CACHE_VERSION = 'v3.3';
const CACHE_NAME = `weba-${CACHE_VERSION}`;

const STATIC_ASSETS = [
    '/',
    '/about.php',
    '/writing.php',
    '/offline.html'
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(STATIC_ASSETS))
            .then(() => self.skipWaiting())
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => caches.delete(name))
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch event - Network First for CSS/JS, Cache First for pages
self.addEventListener('fetch', (event) => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Skip admin requests
    if (event.request.url.includes('/admin/')) {
        return;
    }

    const url = new URL(event.request.url);

    // Network-First for CSS, JS (always get fresh versions)
    if (url.pathname.match(/\.(css|js)$/)) {
        event.respondWith(
            fetch(event.request)
                .then((response) => {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseClone);
                    });
                    return response;
                })
                .catch(() => {
                    return caches.match(event.request);
                })
        );
        return;
    }
    caches.open(CACHE_NAME)
        .then((cache) => {
            cache.put(event.request, responseToCache);
        });
}

                        return response;
                    })
                    .catch (() => {
    // Return offline page if available
    if (event.request.mode === 'navigate') {
        return caches.match('/offline.html');
    }
});
            })
    );
});
