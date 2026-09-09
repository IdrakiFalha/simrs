<?php
$role = current_user()['role'] ?? '';
$r = $_GET['r'] ?? 'dashboard';
$menus = [
    ['r' => 'dashboard', 'icon' => 'bi-grid-1x2', 'label' => 'Dashboard', 'roles' => ['Admin', 'Dokter', 'Perawat', 'Farmasi']],
    ['r' => 'admisi', 'icon' => 'bi-person-plus', 'label' => 'Admisi', 'roles' => ['Admin', 'Dokter', 'Perawat']],
    ['r' => 'farmasi', 'icon' => 'bi-capsule', 'label' => 'Farmasi', 'roles' => ['Admin', 'Dokter', 'Farmasi']],
    ['r' => 'audit', 'icon' => 'bi-shield-lock', 'label' => 'Audit Log', 'roles' => ['Admin', 'Dokter']],
    ['r' => 'sync', 'icon' => 'bi-cloud-arrow-up', 'label' => 'Sinkronisasi', 'roles' => ['Admin', 'Dokter', 'Perawat', 'Farmasi']],
];
?>
<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <i class="bi bi-hospital text-accent"></i>
        <div>
            <strong>RSUD MEURAXA</strong>
            <small>Sistem Informasi Medis</small>
        </div>
    </div>
    <nav class="sidebar-nav">
        <?php foreach ($menus as $m): ?>
            <?php if (!in_array($role, $m['roles'], true)) continue; ?>
            <a class="side-link <?= $r === $m['r'] || str_starts_with((string) $r, $m['r'] . '/') ? 'active' : '' ?>"
               href="<?= e(url($m['r'])) ?>">
                <i class="bi <?= e($m['icon']) ?>"></i>
                <span><?= e($m['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="side-user">
        <div class="avatar"><?= e(mb_strtoupper(mb_substr(current_user()['nama_lengkap'] ?? 'U', 0, 1))) ?></div>
        <div class="info">
            <div class="name"><?= e(current_user()['nama_lengkap'] ?? '') ?></div>
            <div class="role-pill"><?= e($role) ?></div>
        </div>
    </div>
    <div class="px-3 pb-3">
        <a class="side-link logout text-danger" href="<?= e(url('auth/logout')) ?>"><i class="bi bi-box-arrow-left"></i><span>Keluar</span></a>
    </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="app-main">
    <header class="app-topbar">
        <button class="btn btn-ghost" type="button" id="btnToggleSidebar" aria-label="Toggle menu">
            <i class="bi bi-list"></i>
        </button>
        <div class="top-search">
            <i class="bi bi-search"></i>
            <input type="search" id="globalSearch" placeholder="Cari pasien (NIK / nama) lalu Enter…" autocomplete="off">
        </div>
        <div class="top-meta d-flex align-items-center">
            <!-- ── Theme Toggle ───────────────────────────────────── -->
            <button class="btn btn-ghost border-0 me-2" type="button" id="btnToggleThemeLayout" title="Ganti Tema Terang/Gelap">
                <i class="bi bi-moon-stars-fill" id="themeIconLayout"></i>
            </button>
            <a href="<?= e(url('home')) ?>" class="btn btn-sm btn-outline-secondary py-0 px-2 me-2" style="font-size:0.75rem;"><i class="bi bi-house me-1"></i>Beranda</a>
            <span class="badge text-bg-secondary" style="font-size: 0.7rem; letter-spacing: 0.05em;"><?= e($currentUser['role'] ?? $role) ?></span>
        </div>
    </header>
    <div class="app-content">

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btnTheme = document.getElementById('btnToggleThemeLayout');
            const themeIcon = document.getElementById('themeIconLayout');
            
            function updateThemeIcon() {
                if (!themeIcon) return;
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                themeIcon.className = isDark ? 'bi bi-sun-fill text-warning' : 'bi bi-moon-stars-fill text-secondary';
            }
            updateThemeIcon();

            btnTheme?.addEventListener('click', () => {
                const current = document.documentElement.getAttribute('data-theme');
                const target = current === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', target);
                localStorage.setItem('simrs_theme', target);
                updateThemeIcon();
            });

            // Sidebar Toggle Logic
            const btnSidebar = document.getElementById('btnToggleSidebar');
            const sidebar = document.getElementById('appSidebar');
            const overlay = document.getElementById('sidebarOverlay');

            function toggleSidebar() {
                sidebar.classList.toggle('sidebar-open');
                overlay.classList.toggle('show');
            }

            btnSidebar?.addEventListener('click', toggleSidebar);
            overlay?.addEventListener('click', toggleSidebar);
        });
    </script>
