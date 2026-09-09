<?php
$pageTitle = 'RSUD Meuraxa — Portal Digital SIMRS';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSUD Meuraxa — Portal Informasi & Digital SIMRS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="<?= e(APP_BASE) ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .hero-section {
            padding: 8rem 0 5rem;
            position: relative;
            overflow: hidden;
        }
        .hero-title {
            font-size: 3.4rem;
            font-weight: 800;
            line-height: 1.15;
            background: linear-gradient(135deg, var(--text-primary) 30%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.02em;
        }
        .navbar-home {
            background: rgba(11, 15, 25, 0.88);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--glass-border);
            transition: var(--transition);
        }
        .feature-card {
            height: 100%;
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
            border-radius: var(--radius-lg);
            background: var(--bg-card);
            border: 1px solid var(--glass-border);
            padding: 1.75rem;
        }
        .feature-card:hover {
            transform: translateY(-8px);
            border-color: var(--accent);
            box-shadow: 0 16px 40px rgba(0, 201, 167, 0.2);
        }
        .feature-icon-box {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1.25rem;
            background: rgba(0, 201, 167, 0.12);
            color: var(--accent);
            box-shadow: 0 0 20px rgba(0, 201, 167, 0.15);
        }
        .contact-box {
            background: var(--bg-card);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: 2rem;
            height: 100%;
        }
        .contact-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--accent), var(--primary-blue));
            color: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            font-weight: 700;
            flex-shrink: 0;
        }
        .section-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 4px 14px;
            border-radius: 20px;
            background: rgba(0, 201, 167, 0.12);
            border: 1px solid rgba(0, 201, 167, 0.25);
            color: var(--accent);
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 1rem;
        }
    </style>
    <script>
        (function initTheme() {
            const saved = localStorage.getItem('simrs_theme');
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (saved === 'dark' || (!saved && prefersDark)) {
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
</head>
<body>
    <!-- Background Animated Orbs -->
    <div class="login-bg-orb orb-1"></div>
    <div class="login-bg-orb orb-2"></div>

    <!-- Navigation Header -->
    <nav class="navbar navbar-dark fixed-top navbar-home px-3 px-lg-5">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <a class="navbar-brand d-flex align-items-center gap-3" href="#beranda" style="color: var(--text-primary);">
                <i class="bi bi-hospital text-accent fs-2"></i>
                <div>
                    <span class="fw-bold d-block lead mb-0" style="line-height: 1.1; letter-spacing: 0.02em; color: var(--text-primary);">RSUD MEURAXA</span>
                    <small class="text-secondary" style="font-size: 0.7rem; letter-spacing: 0.05em;">SISTEM INFORMASI MANAJEMEN RUMAH SAKIT</small>
                </div>
            </a>

            <div class="d-flex align-items-center gap-3">
                <!-- Theme Toggle Button -->
                <button class="btn btn-ghost rounded-circle-btn" type="button" id="btnToggleThemeIndex" title="Ganti Tema">
                    <i class="bi bi-moon-stars-fill" id="themeIconIndex"></i>
                </button>

                <!-- 3-Dots Kebab Menu Dropdown (Far Top-Right Corner) -->
                <div class="dropdown">
                    <button class="rounded-circle-btn" type="button" id="kebabDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Menu Opsi Navigasi (Titik 3)">
                        <i class="bi bi-three-dots-vertical fs-5"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end glass-dropdown mt-2 p-2 shadow-lg" aria-labelledby="kebabDropdown" style="min-width: 270px;">
                        <li class="dropdown-header text-accent fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.06em;">Navigasi Utama</li>
                        <li><a class="dropdown-item py-2 px-3 d-flex align-items-center gap-2" href="#beranda" style="color: var(--text-primary);"><i class="bi bi-house text-accent"></i>Beranda Utama</a></li>
                        <li><a class="dropdown-item py-2 px-3 d-flex align-items-center gap-2" href="#layanan" style="color: var(--text-primary);"><i class="bi bi-heart-pulse text-danger"></i>Layanan Medis</a></li>
                        <li><a class="dropdown-item py-2 px-3 d-flex align-items-center gap-2" href="#fitur" style="color: var(--text-primary);"><i class="bi bi-cpu text-info"></i>Standar & Fitur Tech</a></li>
                        <li><a class="dropdown-item py-2 px-3 d-flex align-items-center gap-2" href="#kontak" style="color: var(--text-primary);"><i class="bi bi-telephone-inbound text-warning"></i>Kontak (082363105834)</a></li>
                        <li><hr class="dropdown-divider border-secondary border-opacity-25"></li>
                        <li class="dropdown-header text-accent fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.06em;">Akses Sistem</li>
                        <?php if (!empty($currentUser)): ?>
                            <li><a class="dropdown-item py-2 px-3 text-accent fw-bold d-flex align-items-center gap-2" href="<?= e(url('dashboard')) ?>"><i class="bi bi-speedometer2"></i>Buka Dashboard SIMRS</a></li>
                        <?php else: ?>
                            <li><a class="dropdown-item py-2 px-3 text-accent fw-bold d-flex align-items-center gap-2" href="<?= e(url('auth/login')) ?>"><i class="bi bi-box-arrow-in-right"></i>Masuk Portal SIMRS</a></li>
                            <li><a class="dropdown-item py-2 px-3 fw-bold d-flex align-items-center gap-2" href="<?= e(url('auth/register')) ?>" style="color: var(--text-primary);"><i class="bi bi-person-plus text-success"></i>Pendaftaran Nakes Baru</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- SECTION 1: HERO BERANDA -->
    <section id="beranda" class="hero-section">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <div class="section-tag">
                        <i class="bi bi-shield-check"></i> RSUD Meuraxa
                    </div>
                    <h1 class="hero-title mb-4">Rumah Sakit Umum Daerah Meuraxa</h1>
                    <p class="lead text-secondary mb-4" style="max-width: 620px; font-size: 1.1rem;">
                        Melayani dengan sepenuh hati. Kami menyediakan fasilitas kesehatan terbaik dan tenaga medis profesional untuk memberikan kenyamanan dan pelayanan prima bagi masyarakat Kota Banda Aceh.
                    </p>
                    <div class="d-flex flex-wrap gap-3 mb-5">
                        <?php if (!empty($currentUser)): ?>
                            <a href="<?= e(url('dashboard')) ?>" class="btn btn-accent btn-lg px-4"><i class="bi bi-speedometer2 me-2"></i>Masuk ke Dashboard SIMRS</a>
                        <?php else: ?>
                            <a href="<?= e(url('auth/login')) ?>" class="btn btn-accent btn-lg px-4"><i class="bi bi-box-arrow-in-right me-2"></i>Masuk Portal Nakes</a>
                            <a href="<?= e(url('auth/register')) ?>" class="btn btn-outline-accent btn-lg px-4"><i class="bi bi-person-plus me-2"></i>Pendaftaran Akun Nakes</a>
                        <?php endif; ?>
                        <a href="#kontak" class="btn btn-outline-secondary btn-lg px-4" style="color: var(--text-primary);"><i class="bi bi-headset me-2"></i>Hubungi Hotline</a>
                    </div>
                    <!-- Quick Specs Badges -->
                    <div class="d-flex flex-wrap gap-3">
                        <div class="glass-card px-3 py-2 d-flex align-items-center gap-2 m-0">
                            <span class="live-dot" style="background-color: var(--accent);"></span>
                            <small class="text-secondary fw-semibold">Akreditasi Paripurna</small>
                        </div>
                        <div class="glass-card px-3 py-2 d-flex align-items-center gap-2 m-0">
                            <i class="bi bi-telephone-fill text-accent"></i>
                            <small class="text-secondary fw-semibold">Emergency: 082363105834</small>
                        </div>
                        <div class="glass-card px-3 py-2 d-flex align-items-center gap-2 m-0">
                            <i class="bi bi-clock-fill text-warning"></i>
                            <small class="text-secondary fw-semibold">UGD 24 Jam Nonstop</small>
                        </div>
                    </div>
                </div>
                <!-- Hero Widget -->
                <div class="col-lg-5">
                    <div class="glass-card p-4 text-center position-relative">
                        <div class="login-logo mb-3" style="width: 86px; height: 86px; font-size: 2.6rem;">
                            <i class="bi bi-hospital"></i>
                        </div>
                        <h3 class="fw-bold mb-1">RSUD MEURAXA</h3>
                        <p class="text-secondary small mb-4">Kota Banda Aceh, Provinsi Aceh</p>

                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <div class="p-3 rounded-3" style="background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border);">
                                    <div class="h3 fw-bold text-accent mb-0">24/7</div>
                                    <small class="text-secondary">UGD & Ambulans</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 rounded-3" style="background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border);">
                                    <div class="h3 fw-bold text-info mb-0"><i class="bi bi-star-fill"></i></div>
                                    <small class="text-secondary">Layanan Unggulan</small>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 rounded-3 text-start" style="background: rgba(0,201,167,0.08); border: 1px solid rgba(0,201,167,0.2);">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-telephone-outbound fs-2 text-accent"></i>
                                <div>
                                    <small class="text-secondary d-block">Hotline & WhatsApp Bantuan:</small>
                                    <a href="https://wa.me/6282363105834" target="_blank" class="h5 fw-bold text-accent mb-0">082363105834</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 2: LAYANAN MEDIS -->
    <section id="layanan" class="py-5" style="background: var(--bg-surface);">
        <div class="container py-4">
            <div class="text-center mb-5">
                <div class="section-tag mx-auto"><i class="bi bi-heart-pulse"></i> Fasilitas Fasyankes</div>
                <h2 class="fw-bold fs-1" style="color: var(--text-primary);">Layanan Medis Handal</h2>
                <p class="text-secondary" style="max-width: 600px; margin: 0 auto;">Rumah Sakit Umum Daerah Meuraxa hadir dengan layanan medis terpadu untuk memenuhi kebutuhan kesehatan Anda secara menyeluruh.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon-box"><i class="bi bi-activity"></i></div>
                        <h4 class="fw-bold mb-2" style="color: var(--text-primary);">Instalasi Gawat Darurat (IGD)</h4>
                        <p class="text-secondary small mb-3">Penanganan kegawatdaruratan medis yang cepat dan tanggap oleh tim dokter jaga serta perawat tersertifikasi selama 24 jam penuh.</p>
                        <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25 px-3 py-2 rounded-pill"><i class="bi bi-telephone-fill me-1"></i> Emergency: 082363105834</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon-box" style="color:#3b82f6; background:rgba(59,130,246,0.12); box-shadow:0 0 20px rgba(59,130,246,0.15);"><i class="bi bi-person-lines-fill"></i></div>
                        <h4 class="fw-bold mb-2" style="color: var(--text-primary);">Rawat Jalan (Poliklinik)</h4>
                        <p class="text-secondary small mb-3">Layanan konsultasi spesialis mulai dari Penyakit Dalam, Anak, Kandungan, Bedah, hingga poliklinik gigi dengan sistem antrean teratur.</p>
                        <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill"><i class="bi bi-calendar-check me-1"></i> Jadwal Reguler</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon-box" style="color:#fbbf24; background:rgba(251,191,36,0.12); box-shadow:0 0 20px rgba(251,191,36,0.15);"><i class="bi bi-capsule"></i></div>
                        <h4 class="fw-bold mb-2" style="color: var(--text-primary);">Fasilitas Farmasi</h4>
                        <p class="text-secondary small mb-3">Penyediaan obat-obatan lengkap dan berkualitas dengan pengawasan apoteker berlisensi untuk memastikan keamanan pengobatan pasien.</p>
                        <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-25 px-3 py-2 rounded-pill"><i class="bi bi-shield-check me-1"></i> Obat Terjamin</span>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon-box" style="color:#a855f7; background:rgba(168,85,247,0.12); box-shadow:0 0 20px rgba(168,85,247,0.15);"><i class="bi bi-building"></i></div>
                        <h4 class="fw-bold mb-2" style="color: var(--text-primary);">Rawat Inap & ICU</h4>
                        <p class="text-secondary small mb-3">Kamar perawatan intensif dan kelas VIP, I, II, III dengan pengawasan tanda vital berkala dan rekam perjalanan klinis digital.</p>
                        <span class="badge bg-purple bg-opacity-25 text-info border border-info border-opacity-25 px-3 py-2 rounded-pill"><i class="bi bi-check-circle me-1"></i> Ruang Rawat Nyaman</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon-box" style="color:#ec4899; background:rgba(236,72,153,0.12); box-shadow:0 0 20px rgba(236,72,153,0.15);"><i class="bi bi-droplet-half"></i></div>
                        <h4 class="fw-bold mb-2" style="color: var(--text-primary);">Laboratorium & Radiologi</h4>
                        <p class="text-secondary small mb-3">Pemeriksaan darah lengkap, patologi anatomi, rontgen, dan USG dengan hasil digital yang dapat diakses langsung oleh dokter pemeriksa.</p>
                        <span class="badge bg-pink bg-opacity-25 text-pink border border-danger border-opacity-25 px-3 py-2 rounded-pill"><i class="bi bi-lightning-charge me-1"></i> Hasil Akurat</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon-box" style="color:#10b981; background:rgba(16,185,129,0.12); box-shadow:0 0 20px rgba(16,185,129,0.15);"><i class="bi bi-truck-front"></i></div>
                        <h4 class="fw-bold mb-2" style="color: var(--text-primary);">Ambulans & Evakuasi</h4>
                        <p class="text-secondary small mb-3">Armada ambulans gawat darurat dan rujukan antar fasilitas kesehatan yang siap menjemput pasien 24 jam nonstop.</p>
                        <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill"><i class="bi bi-telephone-inbound me-1"></i> Call 082363105834</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 3: KEUNGGULAN RUMAH SAKIT -->
    <section id="fitur" class="py-5">
        <div class="container py-4">
            <div class="text-center mb-5">
                <div class="section-tag mx-auto"><i class="bi bi-award"></i> Keunggulan Layanan</div>
                <h2 class="fw-bold fs-1" style="color: var(--text-primary);">Pelayanan Prima & Terpercaya</h2>
                <p class="text-secondary" style="max-width: 600px; margin: 0 auto;">Komitmen kami memberikan pelayanan kesehatan terbaik dengan fasilitas lengkap dan tenaga medis profesional.</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="glass-card p-4 h-100 d-flex gap-4">
                        <div class="feature-icon-box flex-shrink-0" style="color:#3b82f6; background:rgba(59,130,246,0.12); width:64px; height:64px; font-size:2rem;"><i class="bi bi-person-badge"></i></div>
                        <div>
                            <h4 class="fw-bold mb-2" style="color: var(--text-primary);">Dokter Spesialis Profesional</h4>
                            <p class="text-secondary small mb-2">Didukung oleh tim dokter spesialis dan subspesialis yang berpengalaman dan berkompeten di bidangnya masing-masing.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="glass-card p-4 h-100 d-flex gap-4">
                        <div class="feature-icon-box flex-shrink-0" style="color:#10b981; background:rgba(16,185,129,0.12); width:64px; height:64px; font-size:2rem;"><i class="bi bi-heart-pulse"></i></div>
                        <div>
                            <h4 class="fw-bold mb-2" style="color: var(--text-primary);">Peralatan Medis Modern</h4>
                            <p class="text-secondary small mb-2">Dilengkapi dengan teknologi medis terkini untuk memastikan diagnosis yang akurat dan penanganan pasien yang optimal.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="glass-card p-4 h-100 d-flex gap-4">
                        <div class="feature-icon-box flex-shrink-0" style="color:#f59e0b; background:rgba(245,158,11,0.12); width:64px; height:64px; font-size:2rem;"><i class="bi bi-emoji-smile"></i></div>
                        <div>
                            <h4 class="fw-bold mb-2" style="color: var(--text-primary);">Pelayanan Ramah & Cepat</h4>
                            <p class="text-secondary small mb-2">Proses administrasi yang efisien dan pelayanan staf yang ramah mengutamakan kenyamanan setiap pasien dan keluarga.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="glass-card p-4 h-100 d-flex gap-4">
                        <div class="feature-icon-box flex-shrink-0" style="color:#8b5cf6; background:rgba(139,92,246,0.12); width:64px; height:64px; font-size:2rem;"><i class="bi bi-tree"></i></div>
                        <div>
                            <h4 class="fw-bold mb-2" style="color: var(--text-primary);">Lingkungan Bersih & Nyaman</h4>
                            <p class="text-secondary small mb-2">Area rumah sakit yang terawat, luas, dan hijau untuk menciptakan suasana tenang yang mendukung proses penyembuhan pasien.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 4: KONTAK & LOKASI -->
    <section id="kontak" class="py-5" style="background: rgba(0,0,0,0.35);">
        <div class="container py-4">
            <div class="text-center mb-5">
                <div class="section-tag mx-auto"><i class="bi bi-telephone-inbound"></i> Kontak & Informasi</div>
                <h2 class="fw-bold fs-1" style="color: var(--text-primary);">Hubungi Fasilitas</h2>
                <p class="text-secondary" style="max-width: 600px; margin: 0 auto;">Layanan Pelanggan, Informasi Pendaftaran Pasien & Darurat UGD 24 Jam</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="contact-box">
                        <h4 class="fw-bold mb-4" style="color: var(--text-primary);"><i class="bi bi-info-circle text-accent me-2"></i>Informasi Kontak Official</h4>

                        <div class="d-flex align-items-start gap-3 mb-4">
                            <div class="contact-icon"><i class="bi bi-telephone-fill text-white"></i></div>
                            <div>
                                <small class="text-secondary d-block">Nomor Telepon & Hotline Resmi:</small>
                                <a href="tel:082363105834" class="h5 fw-bold d-block mb-1" style="color: var(--text-primary);">082363105834</a>
                                <small class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Siap melayani 24 Jam Nonstop</small>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3 mb-4">
                            <div class="contact-icon" style="background:linear-gradient(135deg,#25D366,#128C7E);"><i class="bi bi-whatsapp text-white"></i></div>
                            <div>
                                <small class="text-secondary d-block">WhatsApp Center / Chat Live:</small>
                                <a href="https://wa.me/6282363105834" target="_blank" class="h5 fw-bold text-accent d-block mb-1">082363105834</a>
                                <small class="text-secondary">Klik untuk langsung konsultasi via WA</small>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3 mb-4">
                            <div class="contact-icon" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);"><i class="bi bi-envelope-fill text-white"></i></div>
                            <div>
                                <small class="text-secondary d-block">Email Resmi Rumah Sakit:</small>
                                <a href="mailto:info@rsudmeuraxa.go.id" class="h6 fw-bold d-block mb-1" style="color: var(--text-primary);">info@rsudmeuraxa.go.id</a>
                                <small class="text-secondary">Surat kedinasan & informasi umum</small>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3">
                            <div class="contact-icon" style="background:linear-gradient(135deg,#ef4444,#b91c1c);"><i class="bi bi-geo-alt-fill text-white"></i></div>
                            <div>
                                <small class="text-secondary d-block">Alamat Lengkap Fasyankes:</small>
                                <div class="fw-bold small" style="color: var(--text-primary);">Jl. Soekarno-Hatta No. 1, Mbang, Kota Banda Aceh, Aceh</div>
                                <small class="text-secondary">Provinsi Aceh, Indonesia</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Simulation Form / Interactive Map -->
                <div class="col-lg-7">
                    <div class="contact-box">
                        <h4 class="fw-bold mb-3"><i class="bi bi-send text-accent me-2"></i>Kirim Pesan Ke Customer Service</h4>
                        <p class="text-secondary small mb-4">Silakan isi formulir di bawah ini untuk pertanyaan seputar pendaftaran, poliklinik, keluhan, atau layanan RSUD Meuraxa.</p>

                        <form id="formPesanHome">
                            <div class="mb-3">
                                <label class="form-label text-secondary" for="email">Alamat Email</label>
                                <input type="email" class="form-control" name="email" id="email" required placeholder="nama@email.com">
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-secondary" for="subjek">Subjek Pertanyaan</label>
                                <select class="form-select" name="subjek" id="subjek" required>
                                    <option value="" disabled selected>Pilih Subjek</option>
                                    <option value="Informasi Pendaftaran Pasien">Informasi Pendaftaran Pasien</option>
                                    <option value="Jadwal Dokter Spesialis">Jadwal Dokter Spesialis</option>
                                    <option value="Layanan Umum">Layanan Umum</option>
                                    <option value="UGD & Ambulans Emergency">UGD & Ambulans Emergency</option>
                                    <option value="Saran & Keluhan">Saran & Keluhan</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label text-secondary" for="pesan">Pesan / Pertanyaan Anda</label>
                                <textarea class="form-control" name="pesan" id="pesan" rows="4" required placeholder="Tuliskan pesan Anda di sini..."></textarea>
                            </div>
                            
                            <div id="alertPesan" class="alert d-none" role="alert"></div>

                            <button class="btn btn-accent px-4 w-100" type="submit" id="btnSubmitPesan">
                                <i class="bi bi-paperplane me-1"></i> Kirim Pesan Ke Customer Service
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="py-4 border-top border-secondary border-opacity-25" style="background: rgba(5,8,17,0.95);">
        <div class="container">
            <div class="row align-items-center g-3">
                <div class="col-md-6 text-center text-md-start">
                    <div class="fw-bold text-white fs-5"><i class="bi bi-hospital text-accent me-2"></i>RSUD MEURAXA BANDA ACEH</div>
                    <small class="text-secondary">Sistem Informasi Manajemen Rumah Sakit (SIMRS) Digital</small>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <small class="text-secondary">&copy; <?= date('Y') ?> RSUD Meuraxa. Call Center: <a href="tel:082363105834" class="text-accent fw-bold">082363105834</a></small>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btnToggleTheme = document.getElementById('btnToggleThemeIndex');
            const themeIcon = document.getElementById('themeIconIndex');

            function updateIcon() {
                const currentTheme = document.documentElement.getAttribute('data-theme');
                if (currentTheme === 'dark') {
                    themeIcon.className = 'bi bi-sun-fill text-warning';
                } else {
                    themeIcon.className = 'bi bi-moon-stars-fill';
                }
            }
            
            updateIcon();

            if (btnToggleTheme) {
                btnToggleTheme.addEventListener('click', () => {
                    const currentTheme = document.documentElement.getAttribute('data-theme');
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    document.documentElement.setAttribute('data-theme', newTheme);
                    localStorage.setItem('simrs_theme', newTheme);
                    updateIcon();
                });
            }

            // Form Submit Logic
            const formPesan = document.getElementById('formPesanHome');
            const alertPesan = document.getElementById('alertPesan');
            const btnSubmitPesan = document.getElementById('btnSubmitPesan');

            if (formPesan) {
                formPesan.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const formData = new FormData(formPesan);
                    
                    btnSubmitPesan.disabled = true;
                    btnSubmitPesan.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Mengirim...';
                    alertPesan.className = 'alert d-none';

                    try {
                        const response = await fetch('<?= e(url('pesan/store')) ?>', {
                            method: 'POST',
                            body: formData
                        });
                        const result = await response.json();
                        
                        if (result.success) {
                            alertPesan.className = 'alert alert-success mt-3';
                            alertPesan.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>' + result.message;
                            formPesan.reset();
                        } else {
                            alertPesan.className = 'alert alert-danger mt-3';
                            alertPesan.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i>' + (result.message || 'Gagal mengirim pesan.');
                        }
                    } catch (error) {
                        alertPesan.className = 'alert alert-danger mt-3';
                        alertPesan.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i>Terjadi kesalahan sistem.';
                    } finally {
                        btnSubmitPesan.disabled = false;
                        btnSubmitPesan.innerHTML = '<i class="bi bi-paperplane me-1"></i> Kirim Pesan Ke Customer Service';
                    }
                });
            }
        });
    </script>
</body>
</html>
