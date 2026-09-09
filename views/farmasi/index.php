<?php
$pageTitle = 'Farmasi';
require APP_ROOT . '/views/layouts/header.php';
require APP_ROOT . '/views/layouts/sidebar.php';
?>
<h1 class="h4 fw-bold mb-1">Farmasi</h1>
<p class="text-secondary mb-4" style="font-size:0.88rem;">Daftar obat berdasarkan Katalog.</p>

<div class="glass-card">
    <div class="card-header-custom d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-capsule me-2"></i>Katalog Obat</h5>
        <button type="button" class="btn btn-sm btn-accent" data-bs-toggle="modal" data-bs-target="#modalTambahObat">
            <i class="bi bi-plus-lg me-1"></i> Tambah Obat
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-dark-custom mb-0">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Obat</th>
                    <th>Kandungan Aktif</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($obatList as $o): ?>
                <tr>
                    <td><code class="code-nik"><?= e($o['kode_kfa']) ?></code></td>
                    <td><?= e($o['nama_obat']) ?></td>
                    <td><?= e($o['kandungan_aktif'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($obatList)): ?>
                <tr><td colspan="3" class="text-center text-secondary py-4">Belum ada data obat. Tambahkan via database.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Obat -->
<div class="modal fade" id="modalTambahObat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-card border-glass">
            <form action="?r=farmasi/store" method="POST">
                <div class="modal-header border-bottom-glass">
                    <h5 class="modal-title fw-bold text-primary-custom"><i class="bi bi-plus-circle me-2 text-accent"></i>Tambah Obat Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kode KFA</label>
                        <input type="text" name="kode_kfa" class="form-control" required placeholder="Contoh: 93000123">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Obat</label>
                        <input type="text" name="nama_obat" class="form-control" required placeholder="Nama obat...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kandungan Aktif (Opsional)</label>
                        <input type="text" name="kandungan_aktif" class="form-control" placeholder="Kandungan obat...">
                    </div>
                </div>
                <div class="modal-footer border-top-glass">
                    <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-accent">Simpan Obat</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require APP_ROOT . '/views/layouts/footer.php'; ?>
