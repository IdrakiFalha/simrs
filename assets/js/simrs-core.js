/**
 * SIMRS — UI klinis (sidebar, toast, NEWS2, DUR AJAX)
 */
(function () {
    'use strict';

    var cfg = window.SIMRS || { base: '', csrf: '', routes: {} };

    function toast(message, type) {
        var host = document.getElementById('toastHost');
        if (!host) return;
        var el = document.createElement('div');
        el.className = 'simrs-toast ' + (type || 'info');
        el.textContent = message;
        host.appendChild(el);
        setTimeout(function () { el.classList.add('show'); }, 20);
        setTimeout(function () {
            el.classList.remove('show');
            setTimeout(function () { el.remove(); }, 300);
        }, 3200);
    }

    function tickClock() {
        var el = document.getElementById('liveClock');
        if (!el) return;
        el.textContent = new Date().toLocaleString('id-ID', {
            weekday: 'short', hour: '2-digit', minute: '2-digit', second: '2-digit'
        });
    }



    function collectObatIds() {
        return Array.prototype.map.call(document.querySelectorAll('.js-obat'), function (sel) {
            return parseInt(sel.value, 10) || 0;
        }).filter(function (id) { return id > 0; });
    }



    function addObatRow() {
        var tpl = document.getElementById('tplObatRow');
        var host = document.getElementById('resepRows');
        if (!tpl || !host) return;
        host.appendChild(tpl.content.cloneNode(true));
    }

    function highlightFhir() {
        var el = document.getElementById('fhirOutput');
        if (!el) return;
        var html = el.textContent
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        html = html.replace(/"([^"]+)"(\s*:)/g, '<span class="json-key">"$1"</span>$2');
        html = html.replace(/:\s*"([^"]*?)"/g, ': <span class="json-string">"$1"</span>');
        el.innerHTML = html;
    }

    document.addEventListener('DOMContentLoaded', function () {
        tickClock();
        setInterval(tickClock, 1000);

        var btnSide = document.getElementById('btnToggleSidebar');
        if (btnSide) {
            btnSide.addEventListener('click', function () {
                document.body.classList.toggle('sidebar-collapsed');
            });
        }

        var gs = document.getElementById('globalSearch');
        if (gs) {
            gs.addEventListener('keydown', function (ev) {
                if (ev.key === 'Enter') {
                    ev.preventDefault();
                    window.location.href = cfg.routes.admisi + '&q=' + encodeURIComponent(gs.value);
                }
            });
        }

        var nik = document.getElementById('nik');
        if (nik) {
            nik.addEventListener('input', function () {
                nik.value = nik.value.replace(/\D/g, '').slice(0, 16);
                var c = document.getElementById('nikCount');
                if (c) c.textContent = String(nik.value.length);
            });
        }

        document.querySelectorAll('.js-consent').forEach(function (sw) {
            sw.addEventListener('change', function () {
                var fd = new FormData();
                fd.append('_csrf', cfg.csrf);
                fd.append('pasien_id', sw.getAttribute('data-id'));
                fd.append('consent', sw.checked ? '1' : '0');
                fetch(cfg.routes.toggleConsent, { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': cfg.csrf } })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        if (d.ok) toast(d.consent ? 'Consent diaktifkan.' : 'Consent ditolak.', d.consent ? 'success' : 'warn');
                    });
            });
        });



        var fill = document.getElementById('btnFillNormal');
        if (fill) {
            fill.addEventListener('click', function () {
                document.getElementById('sistole').value = 120;
                document.getElementById('diastole').value = 80;
                document.getElementById('nadi').value = 78;
                document.getElementById('suhu').value = 36.7;
                document.getElementById('laju_napas').value = 18;
                toast('Vital tanda normal diisi.', 'success');
            });
        }

        var addBtn = document.getElementById('btnAddObat');
        if (addBtn) {
            addBtn.addEventListener('click', function () {
                addObatRow();
            });
            addObatRow();
        }

        document.body.addEventListener('click', function (ev) {
            var del = ev.target.closest && ev.target.closest('.js-del-obat');
            if (del) {
                var row = del.closest('.resep-row');
                if (row) row.remove();
            }
        });

        var copy = document.getElementById('btnCopyFhir');
        if (copy) {
            copy.addEventListener('click', function () {
                var t = document.getElementById('fhirOutput');
                if (!t) return;
                navigator.clipboard.writeText(t.textContent).then(function () {
                    toast('JSON FHIR disalin.', 'success');
                });
            });
        }
        highlightFhir();

        var formK = document.getElementById('formKlinis');
        if (formK) {
            formK.addEventListener('submit', function () {
                toast('Menyimpan rekam medis…', 'info');
            });
        }
    });
})();
