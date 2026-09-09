<?php
$pageTitle = 'Admisi';
require APP_ROOT . '/views/layouts/header.php';
require APP_ROOT . '/views/layouts/sidebar.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">Admisi &amp; Master Pasien</h1>
        <p class="text-secondary mb-0" style="font-size:0.88rem;">Pendaftaran Pasien &amp; Pengelolaan Rekam Medis.</p>
    </div>
</div>

<?php if (!empty($flashOk)): ?><div class="alert alert-simrs success"><?= $flashOk ?></div><?php endif; ?>
<?php if (!empty($flashErr)): ?><div class="alert alert-simrs danger"><?= e($flashErr) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="glass-card">
            <div class="card-header-custom"><h5><i class="bi bi-person-plus me-2"></i>Registrasi pasien</h5></div>
            <div class="card-body p-4">
                <form method="post" action="<?= e(url('admisi/store')) ?>" id="formAdmisi">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <div class="mb-3">
                        <label class="form-label" for="nik">NIK</label>
                        <input class="form-control" id="nik" name="nik" maxlength="16" required placeholder="16 digit">
                        <div class="form-text text-secondary"><span id="nikCount">0</span>/16</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="nama">Nama lengkap</label>
                        <input class="form-control" id="nama" name="nama" required maxlength="100">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label" for="jenis_kelamin">Jenis kelamin</label>
                            <select class="form-select" id="jenis_kelamin" name="jenis_kelamin">
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="tanggal_lahir">Tanggal lahir</label>
                            <input class="form-control" type="date" id="tanggal_lahir" name="tanggal_lahir" required>
                        </div>
                    </div>
                    </div>
                    <button class="btn btn-accent w-100" type="submit">Daftarkan</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="glass-card">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <h5><i class="bi bi-people me-2"></i>Daftar pasien</h5>
                <form class="d-flex gap-2" method="get" action="<?= e(url('admisi')) ?>">
                    <input type="hidden" name="r" value="admisi">
                    <input class="form-control form-control-sm" name="q" value="<?= e($q ?? '') ?>" placeholder="Cari NIK/nama">
                    <button class="btn btn-outline-accent btn-sm" type="submit">Cari</button>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark-custom table-hover mb-0">
                        <thead>
                            <tr><th>NIK</th><th>Nama</th><th></th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($pasien as $p): ?>
                            <tr>
                                <td><code class="code-nik"><?= e($p['nik']) ?></code></td>
                                <td>
                                    <?= e($p['nama']) ?>
                                    <div class="text-secondary" style="font-size:0.75rem;"><?= e($p['jenis_kelamin']) ?> · <?= e(date('d/m/Y', strtotime($p['tanggal_lahir']))) ?></div>
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-outline-accent btn-sm" href="<?= e(url('klinis/periksa', ['pasien_id' => $p['id']])) ?>">
                                        <i class="bi bi-stethoscope"></i> Periksa
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($pasien)): ?>
                            <tr><td colspan="3" class="text-center text-secondary py-4">Tidak ada data.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require APP_ROOT . '/views/layouts/footer.php'; ?>
