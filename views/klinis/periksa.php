<?php
$pageTitle = 'Pemeriksaan Klinis';
require APP_ROOT . '/views/layouts/header.php';
require APP_ROOT . '/views/layouts/sidebar.php';
?>
<div class="patient-info-bar mb-4">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <div class="patient-avatar <?= $pasien['jenis_kelamin'] === 'L' ? 'male' : 'female' ?>">
            <i class="bi bi-<?= $pasien['jenis_kelamin'] === 'L' ? 'gender-male' : 'gender-female' ?>"></i>
        </div>
        <div class="flex-grow-1">
            <h1 class="h5 mb-0 fw-bold"><?= e($pasien['nama']) ?></h1>
            <div class="d-flex gap-3 flex-wrap mt-1" style="font-size:0.82rem;">
                <span class="text-secondary">NIK <code class="code-nik"><?= e($pasien['nik']) ?></code></span>
                <span class="text-secondary"><?= (int) $umur ?> tahun</span>
            </div>
        </div>
        <a class="btn btn-outline-accent btn-sm" href="<?= e(url('admisi')) ?>">Kembali</a>
    </div>
</div>

<?php if (!empty($flashOk)): ?><div class="alert alert-simrs success"><?= $flashOk ?></div><?php endif; ?>
<?php if (!empty($flashErr)): ?><div class="alert alert-simrs danger"><?= e($flashErr) ?></div><?php endif; ?>

<form method="post" action="<?= e(url('klinis/store')) ?>" id="formKlinis">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="pasien_id" value="<?= (int) $pasien['id'] ?>">

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="glass-card mb-4">
                <div class="card-header-custom"><h5><i class="bi bi-heart-pulse me-2" style="color:#ef4444"></i>Tanda vital</h5></div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label" for="sistole">Sistole</label>
                            <div class="input-group">
                                <input class="form-control vital-input" type="number" id="sistole" name="sistole" min="50" max="300" required>
                                <span class="input-group-text">mmHg</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="diastole">Diastole</label>
                            <div class="input-group">
                                <input class="form-control" type="number" id="diastole" name="diastole" min="30" max="200" required>
                                <span class="input-group-text">mmHg</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="nadi">Nadi</label>
                            <div class="input-group">
                                <input class="form-control vital-input" type="number" id="nadi" name="nadi" min="20" max="250" required>
                                <span class="input-group-text">x/min</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="suhu">Suhu</label>
                            <div class="input-group">
                                <input class="form-control vital-input" type="number" step="0.1" id="suhu" name="suhu" min="30" max="45" required>
                                <span class="input-group-text">°C</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="laju_napas">Laju napas</label>
                            <div class="input-group">
                                <input class="form-control vital-input" type="number" id="laju_napas" name="laju_napas" min="4" max="60" required>
                                <span class="input-group-text">x/min</span>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-outline-accent btn-sm mt-3" type="button" id="btnFillNormal">Isi vital normal (demo)</button>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="glass-card mb-4">
                <div class="card-header-custom"><h5><i class="bi bi-clipboard2-pulse me-2"></i>Diagnosa &amp; resep</h5></div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label" for="keluhan">Keluhan utama</label>
                        <textarea class="form-control" id="keluhan" name="keluhan" rows="2" required placeholder="Misal: demam 3 hari, nyeri otot"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="diagnosa_icd10">ICD-10</label>
                        <input class="form-control" id="diagnosa_icd10" name="diagnosa_icd10" maxlength="10" placeholder="A90" list="icdHints">
                        <datalist id="icdHints">
                            <option value="A90">Dengue fever</option>
                            <option value="J06.9">ISPA</option>
                            <option value="I10">Hipertensi esensial</option>
                            <option value="E11">DM tipe 2</option>
                        </datalist>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">Resep</label>
                        <button class="btn btn-outline-accent btn-sm" type="button" id="btnAddObat"><i class="bi bi-plus"></i> Tambah obat</button>
                    </div>
                    <div id="resepRows"></div>
                    <hr style="border-color:var(--glass-border)">
                    <button class="btn btn-accent w-100" type="submit">
                        <i class="bi bi-send me-1"></i> Simpan Data Klinis
                    </button>
                </div>
            </div>

            <?php if (!empty($riwayat)): ?>
            <div class="glass-card">
                <div class="card-header-custom"><h5>Riwayat kunjungan</h5></div>
                <div class="table-responsive">
                    <table class="table table-dark-custom mb-0">
                        <thead><tr><th>Waktu</th><th>ICD</th></tr></thead>
                        <tbody>
                        <?php foreach ($riwayat as $rw): ?>
                            <tr>
                                <td><?= e(date('d/m/Y H:i', strtotime($rw['waktu']))) ?></td>
                                <td><code class="code-nik"><?= e($rw['diagnosa_icd10']) ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</form>

<template id="tplObatRow">
    <div class="resep-row mb-2">
        <div class="row g-2">
            <div class="col-md-6">
                <select class="form-select js-obat" name="obat_id[]">
                    <option value="">— pilih obat —</option>
                    <?php foreach ($obatList as $o): ?>
                        <option value="<?= (int) $o['id'] ?>" data-aktif="<?= e($o['kandungan_aktif']) ?>">
                            <?= e($o['nama_obat']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <input class="form-control" name="aturan_pakai[]" placeholder="Aturan pakai, mis. 3x1 sesudah makan">
            </div>
            <div class="col-md-1 d-grid">
                <button class="btn btn-outline-danger js-del-obat" type="button"><i class="bi bi-x"></i></button>
            </div>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnAdd = document.getElementById('btnAddObat');
    const container = document.getElementById('resepRows');
    const tpl = document.getElementById('tplObatRow');

    function addRow() {
        const clone = tpl.content.cloneNode(true);
        const btnDel = clone.querySelector('.js-del-obat');
        btnDel.addEventListener('click', function(e) {
            e.currentTarget.closest('.resep-row').remove();
        });
        container.appendChild(clone);
    }

    if (btnAdd && tpl && container) {
        btnAdd.addEventListener('click', addRow);
    }
});
</script>

<?php require APP_ROOT . '/views/layouts/footer.php'; ?>
