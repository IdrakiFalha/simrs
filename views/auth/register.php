<?php
$pageTitle = 'Pendaftaran Akun Nakes — RSUD Meuraxa';
$flashErr  = $flashErr ?? flash('error');
$flashOk   = $flashOk ?? flash('ok');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Nakes — RSUD Meuraxa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="<?= e(APP_BASE) ?>/assets/css/style.css" rel="stylesheet">
    <script>
        (function initTheme() {
            const saved = localStorage.getItem('simrs_theme');
            if (saved) document.documentElement.setAttribute('data-theme', saved);
            else if (window.matchMedia('(prefers-color-scheme: dark)').matches) document.documentElement.setAttribute('data-theme', 'dark');
        })();
    </script>
</head>
<body class="login-body">
    <div class="login-bg-orb orb-1"></div>
    <div class="login-bg-orb orb-2"></div>

    <div class="login-card glass-card my-4 shadow-lg border-0" style="background:var(--bg-card); max-width: 500px;">
        <div class="text-center mb-4">
            <div class="login-logo"><i class="bi bi-person-plus-fill"></i></div>
            <h1 class="h4 fw-bold mb-1">RSUD MEURAXA</h1>
            <p class="text-secondary mb-0" style="font-size:0.85rem;">Pendaftaran Akun Tenaga Kesehatan (Nakes)</p>
            <small class="text-accent">Dokter · Perawat · Farmasi · Admin</small>
        </div>

        <?php if (!empty($flashErr)): ?>
            <div class="alert alert-simrs danger py-2 mb-3"><?= e($flashErr) ?></div>
        <?php endif; ?>
        <?php if (!empty($flashOk)): ?>
            <div class="alert alert-simrs success py-2 mb-3"><?= e($flashOk) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('auth/doRegister')) ?>" autocomplete="off">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

            <div class="mb-3">
                <label class="form-label" for="nama_lengkap">Nama Lengkap & Gelar</label>
                <input class="form-control" id="nama_lengkap" name="nama_lengkap" required placeholder="dr. Budi Santoso, Sp.PD" autofocus>
            </div>

            <div class="mb-3">
                <label class="form-label" for="username">Username Sistem</label>
                <input class="form-control" id="username" name="username" required placeholder="dr_budi">
            </div>

            <div class="mb-3">
                <label class="form-label" for="role">Peran / Profesi Nakes</label>
                <select class="form-select" id="role" name="role" required>
                    <option value="Dokter">Dokter</option>
                    <option value="Perawat">Perawat</option>
                    <option value="Farmasi">Farmasi</option>
                    <option value="Admin">Admin SIMRS</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label" for="password">Password (Minimal 6 Karakter)</label>
                <div class="input-group">
                    <input class="form-control" type="password" id="password" name="password" required minlength="6" placeholder="••••••••">
                    <button class="btn btn-outline-secondary" type="button" id="togglePass" tabindex="-1"><i class="bi bi-eye"></i></button>
                </div>
            </div>

            <button class="btn btn-accent w-100 mb-3" type="submit">
                <i class="bi bi-person-check me-1"></i> Daftar Akun Nakes Baru
            </button>
        </form>

        <div class="text-center pt-2 border-top border-secondary border-opacity-25">
            <a href="<?= e(url('auth/login')) ?>" class="text-secondary small me-3"><i class="bi bi-arrow-left me-1"></i>Sudah punya akun? Masuk</a>
            <a href="<?= e(url('home')) ?>" class="text-secondary small"><i class="bi bi-house me-1"></i>Beranda RSUD Meuraxa</a>
        </div>
    </div>

    <script>
        document.getElementById('togglePass').addEventListener('click', function () {
            var p = document.getElementById('password');
            p.type = p.type === 'password' ? 'text' : 'password';
        });
    </script>
</body>
</html>
