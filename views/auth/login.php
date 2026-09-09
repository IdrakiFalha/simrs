<?php
$pageTitle = 'Masuk Portal — RSUD Meuraxa';
$error = $error ?? flash('error');
$flashOk = flash('ok');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — RSUD Meuraxa</title>
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
    <div class="login-card glass-card shadow-lg border-0" style="background:var(--bg-card); max-width: 440px;">
        <div class="text-center mb-4">
            <div class="login-logo"><i class="bi bi-hospital"></i></div>
            <h1 class="h4 fw-bold mb-1">RSUD MEURAXA</h1>
            <p class="text-secondary mb-0" style="font-size:0.85rem;">Portal Digital SIMRS</p>
            <p class="text-secondary" style="font-size:0.75rem;">SATUSEHAT FHIR R4 · BPJS · NEWS2 · DUR</p>
        </div>
        <?php if (!empty($error)): ?>
            <div class="alert alert-simrs danger py-2"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($flashOk)): ?>
            <div class="alert alert-simrs success py-2"><?= e($flashOk) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= e(url('auth/doLogin')) ?>" autocomplete="off">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <div class="mb-3">
                <label class="form-label" for="username">Username</label>
                <input class="form-control" id="username" name="username" required autofocus placeholder="admin">
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <div class="input-group">
                    <input class="form-control" type="password" id="password" name="password" required placeholder="••••••••">
                    <button class="btn btn-outline-secondary" type="button" id="togglePass" tabindex="-1"><i class="bi bi-eye"></i></button>
                </div>
            </div>
            <button class="btn btn-accent w-100 mb-3" type="submit"><i class="bi bi-box-arrow-in-right me-1"></i>Masuk Portal</button>
        </form>
        <div class="login-hints">
            <div class="hint" data-user="admin">Isi akun admin (demo)</div>
        </div>
        <div class="text-center mt-3 pt-3 border-top border-secondary border-opacity-25">
            <p class="mb-2"><a href="<?= e(url('auth/register')) ?>" class="text-accent small fw-bold"><i class="bi bi-person-plus me-1"></i>Belum punya akun? Daftar Nakes Baru</a></p>
            <p class="mb-0"><a href="<?= e(url('home')) ?>" class="text-secondary small"><i class="bi bi-house me-1"></i>Kembali ke Beranda RSUD Meuraxa</a></p>
        </div>
    </div>
    <script>
        document.getElementById('togglePass').addEventListener('click', function () {
            var p = document.getElementById('password');
            p.type = p.type === 'password' ? 'text' : 'password';
        });
        document.querySelectorAll('.login-hints .hint').forEach(function (el) {
            el.addEventListener('click', function () {
                document.getElementById('username').value = el.getAttribute('data-user');
                document.getElementById('password').value = 'rahasia123';
                document.getElementById('password').focus();
            });
        });
    </script>
</body>
</html>
