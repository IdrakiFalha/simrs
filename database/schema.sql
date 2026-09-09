-- ============================================================
-- SIMRS — Skema Database Nasional + Internasional
-- SATUSEHAT FHIR R4 · BPJS · Audit Estonia
-- Database: simrs (Laragon MySQL/MariaDB)
-- ============================================================

CREATE DATABASE IF NOT EXISTS simrs
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE simrs;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `pesan_cs`;
DROP TABLE IF EXISTS `resep`;
DROP TABLE IF EXISTS `audit_log`;
DROP TABLE IF EXISTS `kunjungan`;
DROP TABLE IF EXISTS `obat`;
DROP TABLE IF EXISTS `pasien`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- 1. users — Akses tenaga kesehatan
-- ------------------------------------------------------------
CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `nama_lengkap` VARCHAR(120) NOT NULL,
    `role` ENUM('Dokter', 'Perawat', 'Farmasi', 'Admin') NOT NULL DEFAULT 'Perawat',
    `aktif` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. pasien — Identitas Kemenkes + consent (model Singapura)
-- ------------------------------------------------------------
CREATE TABLE `pasien` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nik` VARCHAR(16) NOT NULL,
    `nama` VARCHAR(100) NOT NULL,
    `jenis_kelamin` ENUM('L', 'P') NOT NULL,
    `tanggal_lahir` DATE NOT NULL,
    `ihs_number` VARCHAR(50) DEFAULT NULL,
    `consent_satusehat` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_pasien_nik` (`nik`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. kunjungan — Rekam medis
-- ------------------------------------------------------------
CREATE TABLE `kunjungan` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `pasien_id` INT UNSIGNED NOT NULL,
    `dokter_id` INT UNSIGNED NOT NULL,
    `waktu` DATETIME NOT NULL,
    `sistole` INT DEFAULT NULL,
    `diastole` INT DEFAULT NULL,
    `nadi` INT DEFAULT NULL,
    `suhu` DECIMAL(4,1) NULL,
    `laju_napas` INT NULL,
    `keluhan` TEXT NULL,
    `diagnosa_icd10` VARCHAR(10) DEFAULT NULL,
    `satusehat_encounter_id` VARCHAR(100) DEFAULT NULL,
    `status_kirim_satusehat` ENUM('Draft', 'Terkirim', 'Gagal', 'Ditolak Pasien') NOT NULL DEFAULT 'Draft',
    `response_fhir` TEXT,
    PRIMARY KEY (`id`),
    KEY `idx_kunjungan_pasien` (`pasien_id`),
    KEY `idx_kunjungan_dokter` (`dokter_id`),
    KEY `idx_kunjungan_waktu` (`waktu`),
    CONSTRAINT `fk_kunjungan_pasien` FOREIGN KEY (`pasien_id`) REFERENCES `pasien` (`id`) ON UPDATE CASCADE,
    CONSTRAINT `fk_kunjungan_dokter` FOREIGN KEY (`dokter_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. obat — Farmasi (KFA Kemenkes)
-- ------------------------------------------------------------
CREATE TABLE `obat` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kode_kfa` VARCHAR(40) NOT NULL,
    `nama_obat` VARCHAR(150) NOT NULL,
    `kandungan_aktif` TEXT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_obat_kfa` (`kode_kfa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. resep — Preskripsi elektronik
-- ------------------------------------------------------------
CREATE TABLE `resep` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kunjungan_id` INT UNSIGNED NOT NULL,
    `obat_id` INT UNSIGNED NOT NULL,
    `aturan_pakai` VARCHAR(100) NULL,
    PRIMARY KEY (`id`),
    KEY `idx_resep_kunjungan` (`kunjungan_id`),
    KEY `idx_resep_obat` (`obat_id`),
    CONSTRAINT `fk_resep_kunjungan` FOREIGN KEY (`kunjungan_id`) REFERENCES `kunjungan` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_resep_obat` FOREIGN KEY (`obat_id`) REFERENCES `obat` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. audit_log — Jejak akses gaya Estonia (hash SHA-256)
-- ------------------------------------------------------------
CREATE TABLE `audit_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `aksi` VARCHAR(80) NOT NULL,
    `target_tabel` VARCHAR(60) NOT NULL,
    `waktu` DATETIME NOT NULL,
    `hash_signature` VARCHAR(256) NOT NULL,
    `detail` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_audit_user` (`user_id`),
    KEY `idx_audit_waktu` (`waktu`),
    CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. pesan_cs — Pesan dari pelanggan ke Customer Service
-- ------------------------------------------------------------
CREATE TABLE `pesan_cs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(100) NOT NULL,
    `subjek` VARCHAR(100) NOT NULL,
    `pesan` TEXT NOT NULL,
    `waktu` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('Baru', 'Dibaca', 'Selesai') NOT NULL DEFAULT 'Baru',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Seeder nakes — password seragam: rahasia123 (bcrypt)
-- ------------------------------------------------------------
INSERT INTO `users` (`username`, `password_hash`, `nama_lengkap`, `role`) VALUES
('admin', '$2y$10$a2JF8tc/iklPNwIwArnypubgjS7SnHfMunCWkk.tyOLf0yCvZMpHi', 'Administrator SIMRS', 'Admin'),
('dokter', '$2y$10$a2JF8tc/iklPNwIwArnypubgjS7SnHfMunCWkk.tyOLf0yCvZMpHi', 'dr. Budi Santoso, Sp.PD', 'Dokter'),
('perawat', '$2y$10$a2JF8tc/iklPNwIwArnypubgjS7SnHfMunCWkk.tyOLf0yCvZMpHi', 'Ns. Sari Wulandari, S.Kep', 'Perawat'),
('farmasi', '$2y$10$a2JF8tc/iklPNwIwArnypubgjS7SnHfMunCWkk.tyOLf0yCvZMpHi', 'Apt. Andi Pratama, S.Farm', 'Farmasi');

-- Formularium dummy + peringatan DUR
INSERT INTO `obat` (`kode_kfa`, `nama_obat`, `kandungan_aktif`, `peringatan_interaksi`) VALUES
('KFA-93000215', 'Paracetamol 500 mg tablet', 'Paracetamol', 'Hindari kombinasi dosis harian > 4000 mg. Kontraindikasi pada hepatitis, sirosis, dan konsumsi alkohol berat (hepatotoksisitas).'),
('KFA-92001001', 'Aspirin 80 mg tablet', 'Aspirin', 'Interaksi mayor dengan Warfarin dan NSAID: risiko perdarahan gastrointestinal dan serebral. Wajib cek INR.'),
('KFA-91000088', 'Warfarin 2 mg tablet', 'Warfarin', 'Interaksi mayor dengan Aspirin, Ibuprofen, dan NSAID lain. Monitor INR ketat (model DUR Korea / HIRA).'),
('KFA-94000321', 'Ibuprofen 400 mg tablet', 'Ibuprofen', 'NSAID. Interaksi dengan Warfarin (perdarahan) dan Aspirin (menurunkan efek kardioprotektif).'),
('KFA-95000110', 'Amoxicillin 500 mg kapsul', 'Amoxicillin', 'Hati-hati pada riwayat alergi penisilin. Interaksi ringan dengan warfarin (flora usus).'),
('KFA-96000240', 'Omeprazole 20 mg kapsul', 'Omeprazole', 'Dapat menurunkan aktivasi clopidogrel. Monitor jika dikombinasi antiplatelet.');

-- Pasien contoh
INSERT INTO `pasien` (`nik`, `nama`, `jenis_kelamin`, `tanggal_lahir`, `ihs_number`, `consent_satusehat`) VALUES
('3275010101990001', 'Ahmad Fauzi', 'L', '1990-01-15', 'P00000001', 1),
('3275015202880002', 'Siti Nurhaliza', 'P', '1988-02-12', NULL, 0);
