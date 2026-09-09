/**
 * public/js/sync.js
 * ─────────────────────────────────────────────────────────────────────────────
 * SIMRS PWA — IndexedDB Offline Storage & Background Sync (Tahap 4)
 * ─────────────────────────────────────────────────────────────────────────────
 */

const LocalDB = (function() {
    const DB_NAME = 'simrs_local';
    const DB_VERSION = 1;
    let dbPromise = null;

    function init() {
        if (!dbPromise) {
            dbPromise = new Promise((resolve, reject) => {
                const req = indexedDB.open(DB_NAME, DB_VERSION);
                req.onupgradeneeded = (e) => {
                    const db = e.target.result;
                    if (!db.objectStoreNames.contains('antrean_admisi')) {
                        db.createObjectStore('antrean_admisi', { keyPath: 'id', autoIncrement: true });
                    }
                    if (!db.objectStoreNames.contains('antrean_klinis')) {
                        db.createObjectStore('antrean_klinis', { keyPath: 'id', autoIncrement: true });
                    }
                };
                req.onsuccess = () => resolve(req.result);
                req.onerror = () => reject(req.error);
            });
        }
        return dbPromise;
    }

    async function _runTx(storeName, mode, callback) {
        const db = await init();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(storeName, mode);
            const store = tx.objectStore(storeName);
            let req;
            try {
                req = callback(store);
            } catch(e) {
                return reject(e);
            }
            if (req) {
                req.onsuccess = () => resolve(req.result !== undefined ? req.result : true);
                req.onerror = () => reject(req.error);
            } else {
                tx.oncomplete = () => resolve(true);
                tx.onerror = () => reject(tx.error);
            }
        });
    }

    return {
        init,
        saveAdmisi: (payload) => _runTx('antrean_admisi', 'readwrite', s => s.put(payload)),
        saveKlinis: (payload) => _runTx('antrean_klinis', 'readwrite', s => s.put(payload)),
        getAdmisi:  () => _runTx('antrean_admisi', 'readonly', s => s.getAll()),
        getKlinis:  () => _runTx('antrean_klinis', 'readonly', s => s.getAll()),
        delAdmisi:  (id) => _runTx('antrean_admisi', 'readwrite', s => s.delete(id)),
        delKlinis:  (id) => _runTx('antrean_klinis', 'readwrite', s => s.delete(id)),
        async getQueueCount() {
            try {
                const a = await this.getAdmisi();
                const k = await this.getKlinis();
                return a.length + k.length;
            } catch (e) {
                return 0;
            }
        }
    };
})();

const SyncManager = (function() {
    let isSyncing = false;

    async function updateQueueUI() {
        if (window.App && App.setQueueCount) {
            const count = await LocalDB.getQueueCount();
            App.setQueueCount(count);
        }
    }

    async function syncDataLocal() {
        if (isSyncing || !navigator.onLine) return;

        const totalQueue = await LocalDB.getQueueCount();
        if (totalQueue === 0) return;

        isSyncing = true;
        if (window.App) App.toast('Koneksi pulih. Memulai sinkronisasi data...', 'info');

        try {
            // a. Refresh Token SATUSEHAT
            try {
                if (window.App && App.refreshCsrf) await App.refreshCsrf();
                await App.apiFetch('/integrasi/token'); 
            } catch (err) {
                console.warn('[Sync] Gagal refresh token SATUSEHAT, lanjut sync lokal', err);
            }

            // b. Ambil Antrean & c. Upload Admisi
            const admisiList = await LocalDB.getAdmisi();
            for (const item of admisiList) {
                try {
                    const payload = { ...item };
                    const localId = payload.id;
                    delete payload.id;

                    const res = await App.apiFetch('/pasien', { method: 'POST', body: payload });
                    const resData = await res.json().catch(() => ({}));

                    if (res.status === 201 || res.status === 200) {
                        await LocalDB.delAdmisi(localId);
                        
                        // Map local ID to real DB ID for any pending Klinis records
                        const realPasienId = resData.pasien_id;
                        if (realPasienId) {
                            const klinisList = await LocalDB.getKlinis();
                            for (const kl of klinisList) {
                                if (kl.pasien_id === 'local_' + localId) {
                                    kl.pasien_id = realPasienId;
                                    await LocalDB.saveKlinis(kl);
                                }
                            }
                        }
                    } else {
                        item.sync_error = resData.message || `Gagal (Error ${res.status})`;
                        await LocalDB.saveAdmisi(item);
                    }
                    await updateQueueUI();
                } catch (err) {
                    console.error('[Sync] Gagal upload admisi', item, err);
                    item.sync_error = 'Gagal koneksi ke server';
                    await LocalDB.saveAdmisi(item);
                    await updateQueueUI();
                }
            }

            // b. Ambil Antrean & c. Upload Klinis
            const klinisList = await LocalDB.getKlinis();
            for (const item of klinisList) {
                try {
                    const payload = { ...item };
                    const localId = payload.id;
                    delete payload.id;
                    delete payload.sync_error;

                    const res = await App.apiFetch('/kunjungan', { method: 'POST', body: payload });
                    const resData = await res.json().catch(() => ({}));

                    if (res.status === 201 || res.status === 200) {
                        await LocalDB.delKlinis(localId);
                    } else {
                        item.sync_error = resData.message || `Gagal (Error ${res.status})`;
                        await LocalDB.saveKlinis(item);
                    }
                    await updateQueueUI();
                } catch (err) {
                    console.error('[Sync] Gagal upload klinis', item, err);
                    item.sync_error = 'Gagal koneksi ke server';
                    await LocalDB.saveKlinis(item);
                    await updateQueueUI();
                }
            }

            // Memanggil fungsi re-render tabel jika sedang di halaman Sinkronisasi
            if (typeof window.renderSyncTables === 'function') {
                window.renderSyncTables();
            }

            const remaining = await LocalDB.getQueueCount();
            if (remaining === 0) {
                if (window.App) App.toast('Semua antrean offline berhasil disinkronkan!', 'success');
            } else {
                if (window.App) App.toast(`Sinkronisasi selesai. Sisa antrean: ${remaining}`, 'warn');
            }

        } catch (err) {
            console.error('[Sync] Proses sinkronisasi terhenti', err);
        } finally {
            isSyncing = false;
        }
    }

    // Auto-update UI on load
    window.addEventListener('load', updateQueueUI);
    // Auto-sync when going online
    window.addEventListener('online', syncDataLocal);

    return { updateQueueUI, syncDataLocal };
})();
