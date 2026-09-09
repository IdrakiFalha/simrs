    </div>
</div>
</div>
<div id="toastHost" class="toast-host"></div>
<script>
    window.SIMRS = {
        base: <?= json_encode(APP_BASE) ?>,
        csrf: <?= json_encode(csrf_token()) ?>,
        routes: {
            admisi: <?= json_encode(url('admisi')) ?>,
            cekDur: <?= json_encode(url('klinis/cekDur')) ?>,
            cariObat: <?= json_encode(url('klinis/cariObat')) ?>,
            toggleConsent: <?= json_encode(url('admisi/toggleConsent')) ?>
        }
    };
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(APP_BASE) ?>/assets/js/simrs-core.js"></script>
</body>
</html>
