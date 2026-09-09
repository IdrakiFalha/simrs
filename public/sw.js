/**
 * public/sw.js — SIMRS Service Worker
 * ─────────────────────────────────────────────────────────────────────────────
 * Strategy: App Shell (Cache-First)
 *
 * INSTALL:  Pre-cache all App Shell assets (HTML, CSS, JS, icons).
 * ACTIVATE: Delete old caches from previous SW versions.
 * FETCH:
 *   • App Shell files  → Cache-First  (serve from cache, update in background)
 *   • /api/* requests  → Network-Only (never serve API from cache; offline
 *                         handling is done in app.js via IndexedDB in Tahap 4)
 *   • CDN assets       → Cache-First  (Bootstrap, Bootstrap Icons)
 *   • Everything else  → Network-First with cache fallback
 * ─────────────────────────────────────────────────────────────────────────────
 */

'use strict';

// ── Cache Configuration ───────────────────────────────────────────────────────
// ── Cache Configuration ───────────────────────────────────────────────────────
const CACHE_NAME    = 'simrs-app-shell-v9';
const CDN_CACHE     = 'simrs-cdn-v9';

/**
 * App Shell — files that MUST be available offline for the UI to open.
 * These are pre-cached during the INSTALL event.
 * Use relative paths so the SW works at any sub-path.
 */
const APP_SHELL = [
    './',
    './login.html',
    './dashboard.html',
    './admisi.html',
    './klinis.html',
    './css/app.css',
    './js/app.js',
    './js/sync.js',
    './manifest.json',
    './icons/icon-192.png',
    './icons/icon-512.png',
    '../assets/css/style.css',
    '../assets/js/simrs-core.js'
];

/**
 * CDN assets — cached on first use, then served from cache.
 * Pre-caching CDN in INSTALL avoids CORS issues on some servers.
 */
const CDN_ASSETS = [
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
];

// ── INSTALL: Pre-cache App Shell ──────────────────────────────────────────────
self.addEventListener('install', event => {
    console.log('[SW] Install — caching App Shell v5');

    event.waitUntil(
        (async () => {
            // Cache App Shell (individually to prevent atomic failure, compatible way)
            const shellCache = await caches.open(CACHE_NAME);
            await Promise.all(
                APP_SHELL.map(url => shellCache.add(url).catch(err => console.warn('[SW] Failed to cache:', url, err)))
            );

            // Cache CDN assets (best effort)
            const cdnCache = await caches.open(CDN_CACHE);
            await Promise.all(
                CDN_ASSETS.map(url => cdnCache.add(new Request(url, { mode: 'cors' })).catch(err => console.warn('[SW] Failed to cache CDN:', url, err)))
            );

            // Activate immediately without waiting for old SW to finish
            await self.skipWaiting();
        })()
    );
});

// ── ACTIVATE: Clean up old caches ─────────────────────────────────────────────
self.addEventListener('activate', event => {
    console.log('[SW] Activate — cleaning old caches');

    const CURRENT_CACHES = [CACHE_NAME, CDN_CACHE];

    event.waitUntil(
        (async () => {
            const keys = await caches.keys();
            await Promise.all(
                keys
                    .filter(key => !CURRENT_CACHES.includes(key))
                    .map(key => {
                        console.log('[SW] Deleting old cache:', key);
                        return caches.delete(key);
                    })
            );
            // Take control of all open clients immediately
            await self.clients.claim();
        })()
    );
});

// ── FETCH: Intercept requests ─────────────────────────────────────────────────
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // ── 1. Skip non-GET requests (POST, PATCH, etc.) → pass through to network
    //    API mutations are handled by the app; we never cache them.
    if (request.method !== 'GET') {
        return; // Let browser handle it normally
    }

    // ── 2. API calls (/api/*) → Network-Only
    //    Never serve API responses from cache. If offline, the fetch in app.js
    //    will throw and the JS error handler takes over (IndexedDB in Tahap 4).
    if (url.pathname.includes('/api/')) {
        event.respondWith(
            fetch(request).catch(() => {
                return new Response(
                    JSON.stringify({ ok: false, error: 'Offline', message: 'Tidak ada koneksi internet.' }),
                    { status: 503, headers: { 'Content-Type': 'application/json' } }
                );
            })
        );
        return;
    }

    // ── 3. CDN assets → Cache-First ──────────────────────────────────────────
    if (url.hostname.includes('jsdelivr.net') || url.hostname.includes('googleapis.com')) {
        event.respondWith(cacheFirst(request, CDN_CACHE));
        return;
    }

    // ── 4. App Shell & local assets → Cache-First ────────────────────────────
    //    HTML pages, CSS, JS, icons — served from cache for instant load.
    //    Background revalidation keeps cache up-to-date.
    event.respondWith(staleWhileRevalidate(request, CACHE_NAME));
});

// ── Cache Strategies ──────────────────────────────────────────────────────────

/**
 * Cache-First: Return cached version if available, otherwise fetch and cache.
 * Best for: CDN assets, fonts, icons — content that rarely changes.
 */
async function cacheFirst(request, cacheName) {
    const cache  = await caches.open(cacheName);
    const cached = await cache.match(request);
    if (cached) return cached;

    try {
        const response = await fetch(request);
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        return new Response('Resource not available offline.', { status: 503 });
    }
}

/**
 * Stale-While-Revalidate: Serve from cache immediately (if available),
 * then fetch in background and update cache for next time.
 * Best for: App Shell HTML/CSS/JS — fast load + stays fresh.
 */
async function staleWhileRevalidate(request, cacheName) {
    const cache  = await caches.open(cacheName);
    const cached = await cache.match(request);

    // Fetch in background regardless
    const fetchPromise = fetch(request)
        .then(response => {
            if (response.ok) {
                cache.put(request, response.clone());
            }
            return response;
        })
        .catch(() => null);

    // Return cached version instantly if available, else wait for network
    if (cached) {
        return cached;
    }

    const networkResponse = await fetchPromise;
    if (networkResponse) return networkResponse;

    // Ultimate fallback for HTML pages
    const accept = request.headers.get('accept');
    if (accept && accept.includes('text/html')) {
        const fallback = await cache.match('./login.html');
        if (fallback) return fallback;
    }

    return new Response('Halaman tidak tersedia saat offline.', {
        status: 503,
        headers: { 'Content-Type': 'text/plain; charset=utf-8' }
    });
}

// ── Message Handler (from app.js) ─────────────────────────────────────────────
/**
 * Allows app.js to communicate with the SW.
 * Currently handles: cache invalidation on app update.
 * Tahap 4 will add: SYNC_QUEUE message for Background Sync.
 */
self.addEventListener('message', event => {
    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    if (event.data?.type === 'CACHE_UPDATE') {
        caches.open(CACHE_NAME).then(cache => {
            cache.addAll(APP_SHELL).catch(console.warn);
        });
    }
});
