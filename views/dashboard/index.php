<?php
$pageTitle = 'Dashboard Portal — RSUD Meuraxa';
require APP_ROOT . '/views/layouts/header.php';
require APP_ROOT . '/views/layouts/sidebar.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1 text-white">Dashboard SIMRS RSUD Meuraxa</h1>
        <p class="text-secondary mb-0" style="font-size:0.9rem;">Monitoring Rekam Medis Elektronik & Kunjungan Harian</p>
    </div>
    <div>
        <a href="<?= e(url('admisi')) ?>" class="btn btn-accent"><i class="bi bi-person-plus me-1"></i>Pendaftaran Pasien Baru</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon-box" style="background:rgba(16,185,129,0.15); color:var(--success);">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="h2 fw-bold text-white mb-1"><?= (int)$totalPasien ?></div>
            <div class="text-secondary small fw-semibold">Total Pasien Terdaftar</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon-box" style="background:rgba(59,130,246,0.15); color:var(--primary-blue);">
                <i class="bi bi-clipboard2-pulse-fill"></i>
            </div>
            <div class="h2 fw-bold text-white mb-1"><?= (int)$totalKunjungan ?></div>
            <div class="text-secondary small fw-semibold">Total Rekam Kunjungan</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon-box" style="background:rgba(245,158,11,0.15); color:var(--warning);">
                <i class="bi bi-calendar2-check-fill"></i>
            </div>
            <div class="h2 fw-bold text-white mb-1"><?= (int)$kunjunganHariIni ?></div>
            <div class="text-secondary small fw-semibold">Kunjungan Hari Ini</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon-box" style="background:rgba(139,92,246,0.15); color:#a855f7;">
                <i class="bi bi-capsule"></i>
            </div>
            <div class="h2 fw-bold text-white mb-1"><?= (int)$totalObat ?></div>
            <div class="text-secondary small fw-semibold">Katalog Obat KFA</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="glass-card">
            <div class="card-header-custom">
                <h5><i class="bi bi-clock-history text-accent me-2"></i>Kunjungan & Pemeriksaan Terakhir</h5>
                <a href="<?= e(url('admisi')) ?>" class="btn btn-sm btn-outline-accent">Lihat Semua Pasien</a>
            </div>
            <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                <table class="table table-dark-custom mb-0">
                    <thead>
                            <th>Waktu</th>
                            <th>Pasien</th>
                            <th>Dokter Pemeriksa</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentVisits as $v): ?>
                        <tr>
                            <td class="fw-semibold"><?= e(date('d/m/Y H:i', strtotime($v['waktu']))) ?></td>
                            <td>
                                <div class="fw-bold text-white"><?= e($v['nama_pasien'] ?? '-') ?></div>
                            </td>
                            <td><?= e($v['nama_dokter'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentVisits)): ?>
                        <tr><td colspan="3" class="text-center text-secondary py-4">Belum ada data kunjungan.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="glass-card">
            <div class="card-header-custom">
                <h5><i class="bi bi-shield-check text-accent me-2"></i>Status & Info Sistem</h5>
            </div>
            <div class="card-body p-4">
                <div class="mb-4">
                    <small class="text-secondary d-block mb-1 font-monospace">TOTAL AUDIT TRAIL LOGS</small>
                    <div class="h3 fw-bold text-accent mb-0"><i class="bi bi-shield-lock me-1"></i><?= (int)$totalAudit ?> Entries</div>
                </div>
            </div>
        </div>

        <!-- CS Messages Panel -->
        <div class="glass-card mt-4">
            <div class="card-header-custom">
                <h5><i class="bi bi-chat-left-text text-accent me-2"></i>Pesan CS Terbaru</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush" style="background: transparent;">
                    <?php if (empty($pesanCS)): ?>
                        <li class="list-group-item text-center text-secondary py-4" style="background: transparent; border: none;">Belum ada pesan.</li>
                    <?php else: ?>
                        <?php foreach ($pesanCS as $p): ?>
                            <li class="list-group-item" style="background: transparent; border-bottom: 1px solid var(--glass-border);">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-white"><?= e($p['email']) ?></strong>
                                    <small class="text-secondary"><?= e(date('d/m H:i', strtotime($p['waktu']))) ?></small>
                                </div>
                                <div class="text-accent small fw-bold mb-1"><?= e($p['subjek']) ?> <span class="badge bg-secondary ms-1"><?= e($p['status']) ?></span></div>
                                <p class="mb-1 text-secondary" style="font-size: 0.85rem; line-height: 1.4;"><?= e(strlen($p['pesan']) > 60 ? substr($p['pesan'], 0, 60) . '...' : $p['pesan']) ?></p>
                                <a href="mailto:<?= e($p['email']) ?>?subject=RE: <?= rawurlencode($p['subjek']) ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-2 p-1 px-2" style="font-size: 0.75rem;"><i class="bi bi-envelope me-1"></i>Balas Email</a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php require APP_ROOT . '/views/layouts/footer.php'; ?>
