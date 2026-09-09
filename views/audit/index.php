<?php
$pageTitle = 'Audit Log';
require APP_ROOT . '/views/layouts/header.php';
require APP_ROOT . '/views/layouts/sidebar.php';
?>
<h1 class="h4 fw-bold mb-1">Audit Log</h1>
<p class="text-secondary mb-4" style="font-size:0.88rem;">Catatan aktivitas pengguna.</p>
<div class="glass-card">
    <div class="table-responsive">
        <table class="table table-dark-custom mb-0">
            <thead><tr><th>Waktu</th><th>Nakes</th><th>Aksi</th><th>Tabel</th><th>Hash</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= e($log['waktu']) ?></td>
                    <td><?= e($log['nama_lengkap']) ?><div class="text-secondary" style="font-size:0.72rem;"><?= e($log['role']) ?></div></td>
                    <td><?= e($log['aksi']) ?></td>
                    <td><?= e($log['target_tabel']) ?></td>
                    <td><code class="hash-cell" title="<?= e($log['hash_signature']) ?>"><?= e(substr($log['hash_signature'], 0, 16)) ?>…</code></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require APP_ROOT . '/views/layouts/footer.php'; ?>
