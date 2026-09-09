/**
 * public/js/app.js
 * ─────────────────────────────────────────────────────────────────────────────
 * SIMRS PWA — Shared Application Core
 *
 * Provides:
 *  - API base URL helper:  SIMRS_API(path)
 *  - App.apiFetch()        — authenticated fetch with CSRF + JSON headers
 *  - App.toast()           — bottom-right toast notifications
 *  - App.setUser() / App.getUser()  — in-memory + sessionStorage user cache
 *  - App.setCsrf() / App.getCsrf()  — CSRF token management
 *  - App.requireAuth()     — redirect to login if not authenticated
 *  - App.logout()          — call logout API + clear state + redirect
 *  - App.renderSidebar()   — inject sidebar HTML based on current user role
 *  - App.tickClock()       — live clock in topbar
 * ─────────────────────────────────────────────────────────────────────────────
 */

'use strict';

/* ── Theme Initialization ────────────────────────────────────────────────────
 * Runs immediately to prevent Flash of Unstyled Content (FOUC)
 */
(function initTheme() {
    const saved = localStorage.getItem('simrs_theme');
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (saved === 'dark' || (!saved && prefersDark)) {
        document.documentElement.setAttribute('data-theme', 'dark');
    } else {
        document.documentElement.setAttribute('data-theme', 'light');
    }
})();

/* ── Base URL ────────────────────────────────────────────────────────────────
 * Detects whether we're running under /simrs/ sub-path or at root.
 * Adjust SIMRS_BASE if your Laragon vhost uses a different path.
 * Examples:
 *   http://localhost/simrs/public/admisi.html → base = /simrs
 *   http://simrs.test/public/admisi.html      → base = ''
 */
const SIMRS_BASE = (function () {
    const path = window.location.pathname; // e.g. /simrs/public/admisi.html
    const idx  = path.indexOf('/public/');
    return idx > -1 ? path.slice(0, idx) : '';
})();

/** Build a full URL to a REST API endpoint. e.g. SIMRS_API('/pasien') */
function SIMRS_API(path) {
    return SIMRS_BASE + '/api' + path;
}

/* ── Service Worker Registration ────────────────────────────────────────────
 * Must be called as early as possible. The SW file must live at the same
 * directory level as the HTML pages it controls (i.e., /public/sw.js).
 */
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('./sw.js', { scope: './' })
            .then(reg => {
                console.log('[App] Service Worker registered. Scope:', reg.scope);
                // Notify SW to update cache when a new version is detected
                reg.addEventListener('updatefound', () => {
                    const newWorker = reg.installing;
                    newWorker?.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            console.log('[App] New SW version available.');
                            // In Phase 4 we can prompt user to reload
                        }
                    });
                });
            })
            .catch(err => console.warn('[App] SW registration failed:', err));
    });
}

/* ── Global Offline Banner ───────────────────────────────────────────────────
 * Injects a top banner when the device goes offline and removes it on reconnect.
 * Appended to body once; all pages benefit automatically.
 */
(function initOfflineBanner() {
    let banner = null;

    function ensureBanner() {
        if (banner) return;
        banner = document.createElement('div');
        banner.id        = 'globalOfflineBanner';
        banner.className = 'offline-banner';
        banner.innerHTML = '<i class="bi bi-wifi-off me-2"></i>Mode Offline — Data baru akan disimpan di antrian lokal dan dikirim saat koneksi pulih.';
        document.body.prepend(banner);
    }

    function updateBanner() {
        if (!navigator.onLine) {
            ensureBanner();
            requestAnimationFrame(() => banner?.classList.add('show'));
            document.body.classList.add('is-offline');
        } else {
            banner?.classList.remove('show');
            document.body.classList.remove('is-offline');
        }
    }

    // Wait until DOM is ready before appending
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updateBanner);
    } else {
        updateBanner();
    }

    window.addEventListener('online',  updateBanner);
    window.addEventListener('offline', updateBanner);
})()


/* ── App Namespace ───────────────────────────────────────────────────────────*/
const App = (function () {

    // ── State ────────────────────────────────────────────────────────────────
    let _user = null;
    let _csrf = null;

    // Restore from localStorage (survives page refresh and browser restart)
    try {
        const stored = localStorage.getItem('simrs_user');
        if (stored) _user = JSON.parse(stored);
        _csrf = localStorage.getItem('simrs_csrf') || null;
    } catch (_) {}

    // ── User ─────────────────────────────────────────────────────────────────
    function setUser(u) {
        _user = u;
        try { localStorage.setItem('simrs_user', JSON.stringify(u)); } catch (_) {}
    }
    function getUser() { return _user; }

    // ── CSRF ─────────────────────────────────────────────────────────────────
    function setCsrf(t) {
        _csrf = t;
        try { localStorage.setItem('simrs_csrf', t); } catch (_) {}
    }
    function getCsrf() { return _csrf; }

    // ── apiFetch ──────────────────────────────────────────────────────────────
    /**
     * Thin wrapper around fetch() that:
     *  1. Always sends credentials (cookies) for session auth.
     *  2. Sets Content-Type: application/json if body is object.
     *  3. Attaches X-CSRF-Token header for mutating requests.
     *
     * @param {string} path     — API path e.g. '/pasien'
     * @param {object} options  — standard fetch options
     * @returns {Promise<Response>}
     */
    async function apiFetch(path, options = {}) {
        const method  = (options.method || 'GET').toUpperCase();
        const headers = { ...(options.headers || {}) };

        // JSON body
        if (options.body && typeof options.body === 'object' && !(options.body instanceof FormData)) {
            headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(options.body);
        }

        // CSRF on mutating requests
        if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method) && _csrf) {
            headers['X-CSRF-Token'] = _csrf;
        }

        return fetch(SIMRS_API(path), {
            ...options,
            method,
            headers,
            credentials: 'include',   // always send session cookie
        });
    }

    // ── Auth Guards ───────────────────────────────────────────────────────────
    /**
     * Check server session. If not authenticated, redirect to login.html.
     * Returns the user object on success.
     */
    async function requireAuth() {
        // If offline and we already have a cached user, allow access immediately
        if (!navigator.onLine && _user) {
            return _user;
        }

        try {
            const res  = await apiFetch('/auth/me');
            const data = await res.json();
            
            if (res.ok && data.ok) {
                setUser(data.user);
                return data.user;
            }

            // If Service Worker returned 503 Offline Fallback, treat it as a network error
            if (res.status === 503 && data.error === 'Offline' && _user) {
                return _user;
            }
            
            // If it's a real 401/403, we should fall through and logout
        } catch (_) {
            // If fetch actually fails (network error bypassing SW) but we have a cached user, fallback to offline access
            if (_user) return _user;
        }
        // Not authenticated or network error without cache → redirect
        window.location.replace('login.html');
        return null;
    }

    /** Logout: call API, clear state, redirect to login */
    async function logout() {
        try {
            // Ensure we have a fresh CSRF before logout
            await refreshCsrf();
            await apiFetch('/auth/logout', { method: 'POST' });
        } catch (_) {}
        clearState();
        window.location.replace('login.html');
    }

    function clearState() {
        _user = null;
        _csrf = null;
        try { 
            localStorage.removeItem('simrs_user');
            localStorage.removeItem('simrs_csrf');
        } catch (_) {}
    }

    // ── CSRF Refresh ─────────────────────────────────────────────────────────
    async function refreshCsrf() {
        try {
            // Prevent caching by adding a cache-buster query param and no-store header
            const url = SIMRS_API('/csrf-token?_=' + Date.now());
            const r = await fetch(url, { credentials: 'include', cache: 'no-store' });
            const d = await r.json();
            if (d.ok) setCsrf(d.csrf_token || d.token);
        } catch (_) {}
    }

    // ── Toast Notifications ───────────────────────────────────────────────────
    /**
     * @param {string} message
     * @param {'success'|'danger'|'warn'|'info'} type
     * @param {number} duration  ms
     */
    function toast(message, type = 'info', duration = 3500) {
        let host = document.getElementById('toastHost');
        if (!host) {
            host = document.createElement('div');
            host.id = 'toastHost';
            host.className = 'toast-host';
            document.body.appendChild(host);
        }
        const el = document.createElement('div');
        el.className = 'simrs-toast ' + type;

        // Icon prefix
        const icons = { success: '✓', danger: '✕', warn: '⚠', info: 'ℹ' };
        el.innerHTML = `<span style="margin-right:0.5rem;font-weight:900;">${icons[type] || 'ℹ'}</span>${message}`;

        host.appendChild(el);
        requestAnimationFrame(() => { el.classList.add('show'); });
        setTimeout(() => {
            el.classList.remove('show');
            setTimeout(() => el.remove(), 350);
        }, duration);
    }

    // ── Sidebar Renderer ──────────────────────────────────────────────────────
    /**
     * Injects the app shell (sidebar + topbar structure) into #appShell.
     * Call after requireAuth() resolves.
     */
    function renderSidebar(activePage) {
        const user = _user || {};
        const role = user.role || '';
        const initial = (user.nama_lengkap || 'U').charAt(0).toUpperCase();

        const menus = [
            { href: 'dashboard.html', icon: 'bi-grid-1x2',    label: 'Dashboard',          roles: ['Admin', 'Dokter', 'Perawat', 'Farmasi'] },
            { href: 'admisi.html',    icon: 'bi-person-plus', label: 'Admisi',             roles: ['Admin', 'Dokter', 'Perawat'] },
            { href: 'farmasi.html',   icon: 'bi-capsule',     label: 'Farmasi',            roles: ['Admin', 'Dokter', 'Farmasi'] },
            { href: 'audit.html',     icon: 'bi-shield-lock', label: 'Audit Log',          roles: ['Admin', 'Dokter'] },
            { href: 'sync.html',      icon: 'bi-cloud-arrow-up', label: 'Sinkronisasi',    roles: ['Admin', 'Dokter', 'Perawat', 'Farmasi'] },
        ];

        const navLinks = menus
            .filter(m => m.roles.includes(role))
            .map(m => {
                const isActive = activePage === m.href;
                return `
                <a class="side-link ${isActive ? 'active' : ''}" href="${m.href}">
                    <i class="bi ${m.icon}"></i>
                    <span>${m.label}</span>
                </a>`;
            }).join('');

        const html = `
        <aside class="app-sidebar" id="appSidebar">
            <div class="sidebar-brand">
                <i class="bi bi-hospital text-accent"></i>
                <div>
                    <strong>RSUD MEURAXA</strong>
                    <small>Sistem Informasi Medis</small>
                </div>
            </div>
            <nav class="sidebar-nav">${navLinks}</nav>
            <div class="side-user">
                <div class="avatar">${initial}</div>
                <div class="info">
                    <div class="name">${user.nama_lengkap || ''}</div>
                    <div class="role-pill">${role}</div>
                </div>
            </div>
            <div class="px-3 pb-3">
                <a class="side-link logout text-danger" href="#" id="btnLogout">
                    <i class="bi bi-box-arrow-left"></i><span>Keluar</span>
                </a>
            </div>
        </aside>
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <div class="app-main">
            <header class="app-topbar">
                <button class="btn btn-ghost" type="button" id="btnToggleSidebar" aria-label="Toggle menu">
                    <i class="bi bi-list"></i>
                </button>
                <div class="top-search">
                    <i class="bi bi-search"></i>
                    <input type="search" id="globalSearch" placeholder="Cari pasien (NIK / nama) lalu Enter…" autocomplete="off">
                </div>
                <div class="top-meta d-flex align-items-center">
                    <!-- ── Theme Toggle ───────────────────────────────────── -->
                    <button class="btn btn-ghost border-0 me-2" type="button" id="btnToggleTheme" title="Ganti Tema Terang/Gelap">
                        <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
                    </button>
                    <!-- ── Network Status Indicator ───────────────────────── -->
                    <div class="net-status me-2" id="netStatus" title="Status koneksi internet">
                        <span class="net-dot" id="netDot"></span>
                        <span class="net-label" id="netLabel">Online</span>
                    </div>
                    <!-- ── Offline Queue Counter ──────────────────────────── -->
                    <!-- data-queue is updated by Tahap 4 IndexedDB logic      -->
                    <div class="queue-indicator" id="queueIndicator" title="Antrian data yang belum terkirim">
                        <i class="bi bi-clock-history" style="font-size:0.9rem;"></i>
                        <span>Antrian: <strong id="queueCount" data-queue="0">0</strong></span>
                    </div>
                    <span id="liveClock" class="clock"></span>
                </div>
            </header>
            <div class="app-content" id="pageContent">`;

        const footer = `</div></div>`; // close app-content + app-main

        const shell = document.getElementById('appShell');
        if (shell) {
            shell.innerHTML = html + shell.innerHTML + footer;
        }

        // ── Wire sidebar behaviors ────────────────────────────────────────────
        const btnSidebar = document.getElementById('btnToggleSidebar');
        const sidebarEl = document.getElementById('appSidebar');
        const overlayEl = document.getElementById('sidebarOverlay');

        function toggleSidebar() {
            sidebarEl.classList.toggle('sidebar-open');
            overlayEl.classList.toggle('show');
        }

        btnSidebar?.addEventListener('click', toggleSidebar);
        overlayEl?.addEventListener('click', toggleSidebar);

        document.getElementById('btnLogout')?.addEventListener('click', async (e) => {
            e.preventDefault();
            await logout();
        });

        // ── Theme Toggle behavior ─────────────────────────────────────────────
        const btnTheme = document.getElementById('btnToggleTheme');
        const themeIcon = document.getElementById('themeIcon');
        
        function updateThemeIcon() {
            if (!themeIcon) return;
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            themeIcon.className = isDark ? 'bi bi-sun-fill text-warning' : 'bi bi-moon-stars-fill text-secondary';
        }
        updateThemeIcon();

        btnTheme?.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-theme');
            const target = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', target);
            localStorage.setItem('simrs_theme', target);
            updateThemeIcon();
        });

        // Global search → navigate to admisi with query
        document.getElementById('globalSearch')?.addEventListener('keydown', ev => {
            if (ev.key === 'Enter') {
                ev.preventDefault();
                window.location.href = `admisi.html?q=${encodeURIComponent(ev.target.value)}`;
            }
        });

        // Live clock
        function tickClock() {
            const el = document.getElementById('liveClock');
            if (el) el.textContent = new Date().toLocaleString('id-ID', {
                weekday: 'short', hour: '2-digit', minute: '2-digit', second: '2-digit'
            });
        }
        tickClock();
        setInterval(tickClock, 1000);

        // ── Network status indicator ──────────────────────────────────────
        function updateNetStatus() {
            const dot   = document.getElementById('netDot');
            const label = document.getElementById('netLabel');
            const qi    = document.getElementById('queueIndicator');

            if (navigator.onLine) {
                dot?.setAttribute('data-status', 'online');
                if (label) label.textContent = 'Online';
                qi?.classList.remove('queue-has-items');
            } else {
                dot?.setAttribute('data-status', 'offline');
                if (label) label.textContent = 'Offline';
            }
        }
        updateNetStatus();
        window.addEventListener('online',  () => { updateNetStatus(); App.toast('Koneksi pulih — sinkronisasi data dimulai.', 'success'); });
        window.addEventListener('offline', () => { updateNetStatus(); App.toast('Koneksi terputus — mode offline aktif.', 'warn'); });
    }

    /** Update the offline queue counter (called by Tahap 4 IndexedDB logic) */
    function setQueueCount(n) {
        const el = document.getElementById('queueCount');
        const qi = document.getElementById('queueIndicator');
        if (!el) return;
        el.textContent          = String(n);
        el.dataset.queue        = String(n);
        if (qi) qi.classList.toggle('queue-has-items', n > 0);
    }

    // ── Expose Public API ─────────────────────────────────────────────────────
    return {
        setUser, getUser,
        setCsrf, getCsrf,
        apiFetch,
        requireAuth,
        logout,
        refreshCsrf,
        toast,
        renderSidebar,
        setQueueCount,  // Phase 4: called by sync.js to update queue counter
    };

})();
