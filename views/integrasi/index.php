<?php
$pageTitle = 'Integrasi SATUSEHAT';
require APP_ROOT . '/views/layouts/header.php';
require APP_ROOT . '/views/layouts/sidebar.php';
?>
<h1 class="h4 fw-bold mb-1">Integrasi SATUSEHAT / BPJS</h1>
<p class="text-secondary mb-4" style="font-size:0.88rem;">Koneksi ke API SATUSEHAT FHIR R4 (sandbox). Token diambil via OAuth2 client_credentials.</p>

<?php if (!empty($flashOk)): ?><div class="alert alert-simrs success"><?= $flashOk ?></div><?php endif; ?>
<?php if (!empty($flashErr)): ?><div class="alert alert-simrs danger"><?= e($flashErr) ?></div><?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="glass-card">
            <div class="card-header-custom"><h5><i class="bi bi-key me-2"></i>OAuth2 Token</h5></div>
            <div class="card-body p-4">
                <?php if ($token): ?>
                    <div class="mb-2"><span class="badge bg-success">Connected</span></div>
                    <label class="form-label text-secondary">Access Token (truncated)</label>
                    <div class="p-2 rounded" style="background:rgba(0,0,0,0.3);word-break:break-all;font-family:'JetBrains Mono',monospace;font-size:0.78rem;">
                        <?= e(substr($token, 0, 60)) ?>…
                    </div>
                <?php else: ?>
                    <div class="mb-2"><span class="badge bg-danger">Error</span></div>
                    <p class="text-secondary mb-0"><?= e($tokenErr ?? 'Tidak dapat mengambil token.') ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="glass-card">
            <div class="card-header-custom"><h5><i class="bi bi-gear me-2"></i>Konfigurasi API</h5></div>
            <div class="card-body p-4" style="font-size:0.85rem;">
                <div class="mb-2"><span class="text-secondary">Environment:</span> <code><?= e(SATUSEHAT_ENV) ?></code></div>
                <div class="mb-2"><span class="text-secondary">Auth URL:</span> <code style="word-break:break-all"><?= e(SATUSEHAT_AUTH_URL) ?></code></div>
                <div class="mb-2"><span class="text-secondary">Base URL:</span> <code style="word-break:break-all"><?= e(SATUSEHAT_BASE_URL) ?></code></div>
                <div class="mb-2"><span class="text-secondary">Organization:</span> <code><?= e(ORGANIZATION_ID) ?></code></div>
                <div><span class="text-secondary">Client ID:</span> <code><?= e(substr(CLIENT_ID, 0, 12)) ?>…</code></div>
            </div>
        </div>
    </div>
</div>

<div class="glass-card">
    <div class="card-header-custom"><h5><i class="bi bi-cloud-upload me-2"></i>Status Pengiriman Encounter</h5></div>
    <div class="table-responsive">
        <table class="table table-dark-custom mb-0">
            <thead><tr><th>Waktu</th><th>Pasien</th><th>Dokter</th><th>Encounter ID</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($visits as $v): ?>
                <tr>
                    <td><?= e(date('d/m/Y H:i', strtotime($v['waktu']))) ?></td>
                    <td><?= e($v['nama_pasien'] ?? '-') ?></td>
                    <td><?= e($v['nama_dokter'] ?? '-') ?></td>
                    <td><code class="code-nik"><?= e($v['satusehat_encounter_id'] ?? '—') ?></code></td>
                    <td>
                        <span class="badge <?= $v['status_kirim_satusehat'] === 'sent' ? 'bg-success' : ($v['status_kirim_satusehat'] === 'failed' ? 'bg-danger' : 'bg-secondary') ?>">
                            <?= e($v['status_kirim_satusehat']) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($visits)): ?>
                <tr><td colspan="5" class="text-center text-secondary py-4">Belum ada kunjungan.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require APP_ROOT . '/views/layouts/footer.php'; ?>
