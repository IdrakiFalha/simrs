-- ═══════════════════════════════════════════════
-- SIMRS Database Schema + Seeder
-- SATUSEHAT FHIR R4
-- ═══════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS simrs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE simrs;

-- Drop existing tables (in reverse dependency order)
DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS resep;
DROP TABLE IF EXISTS kunjungan;
DROP TABLE IF EXISTS tabel_obat;
DROP TABLE IF EXISTS pasien;
DROP TABLE IF EXISTS users;

-- ── 1. users ──────────────────────────────────
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nama_lengkap  VARCHAR(100) NOT NULL,
    role          ENUM('Dokter','Perawat','Farmasi','Admin') NOT NULL
) ENGINE=InnoDB;

-- ── 2. pasien ─────────────────────────────────
CREATE TABLE pasien (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    nik               CHAR(16)    NOT NULL UNIQUE,
    nama              VARCHAR(100) NOT NULL,
    jenis_kelamin     ENUM('L','P') NOT NULL,
    tanggal_lahir     DATE         NOT NULL,
    ihs_number        VARCHAR(50),
    consent_satusehat BOOLEAN      NOT NULL DEFAULT FALSE
) ENGINE=InnoDB;

-- ── 3. kunjungan ──────────────────────────────
CREATE TABLE kunjungan (
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    pasien_id               INT         NOT NULL,
    dokter_id               INT         NOT NULL,
    waktu                   DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sistole                 INT,
    diastole                INT,
    nadi                    INT,
    suhu                    DECIMAL(4,1),
    laju_napas              INT,
    skor_news2              INT,
    kategori_news2          VARCHAR(20),
    keluhan                 TEXT,
    diagnosa_icd10          VARCHAR(20),
    satusehat_encounter_id  VARCHAR(100),
    status_kirim_satusehat  ENUM('pending','sent','failed') DEFAULT 'pending',
    FOREIGN KEY (pasien_id) REFERENCES pasien(id) ON DELETE CASCADE,
    FOREIGN KEY (dokter_id) REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── 4. tabel_obat ─────────────────────────────
CREATE TABLE tabel_obat (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    kode_kfa             VARCHAR(20)  NOT NULL UNIQUE,
    nama_obat            VARCHAR(100) NOT NULL,
    kandungan_aktif      VARCHAR(255),
    peringatan_interaksi TEXT
) ENGINE=InnoDB;

-- ── 5. resep ──────────────────────────────────
CREATE TABLE resep (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    kunjungan_id      INT          NOT NULL,
    obat_id           INT          NOT NULL,
    aturan_pakai      VARCHAR(255) NOT NULL,
    status_dur_warning ENUM('none','warning','critical') DEFAULT 'none',
    FOREIGN KEY (kunjungan_id) REFERENCES kunjungan(id)  ON DELETE CASCADE,
    FOREIGN KEY (obat_id)      REFERENCES tabel_obat(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ── 6. audit_log ──────────────────────────────
CREATE TABLE audit_log (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT          NOT NULL,
    aksi           VARCHAR(100) NOT NULL,
    target_tabel   VARCHAR(50)  NOT NULL,
    waktu          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    hash_signature CHAR(64)     NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ═══════════════════════════════════════════════
-- SEEDER DATA
-- ═══════════════════════════════════════════════

-- Admin user (username: admin, password: rahasia123)
INSERT INTO users (username, password_hash, nama_lengkap, role) VALUES
('admin', '$2y$10$nXcdBcAeKRA8rQq8bd6R2e1vl2xDnnlN2D4fj61BL3jgzjxroEZJ.', 'Administrator', 'Admin');

-- Sample users
INSERT INTO users (username, password_hash, nama_lengkap, role) VALUES
('dr.andi',   '$2y$10$nXcdBcAeKRA8rQq8bd6R2e1vl2xDnnlN2D4fj61BL3jgzjxroEZJ.', 'dr. Andi Pratama, Sp.PD', 'Dokter'),
('ns.sari',   '$2y$10$nXcdBcAeKRA8rQq8bd6R2e1vl2xDnnlN2D4fj61BL3jgzjxroEZJ.', 'Ns. Sari Dewi, S.Kep',    'Perawat'),
('apt.budi',  '$2y$10$nXcdBcAeKRA8rQq8bd6R2e1vl2xDnnlN2D4fj61BL3jgzjxroEZJ.', 'Apt. Budi Santoso, S.Farm', 'Farmasi');

-- Sample patients
INSERT INTO pasien (nik, nama, jenis_kelamin, tanggal_lahir, ihs_number, consent_satusehat) VALUES
('3201010101900001', 'Ahmad Hidayat',    'L', '1990-01-01', 'P10000001', TRUE),
('3201020202850002', 'Siti Nurhaliza',   'P', '1985-02-02', 'P10000002', TRUE),
('3201030303780003', 'Bambang Suryanto', 'L', '1978-03-03', NULL,        FALSE);

-- Sample drugs (KFA codes) with DUR interaction data
INSERT INTO tabel_obat (kode_kfa, nama_obat, kandungan_aktif, peringatan_interaksi) VALUES
('93001833', 'Amoxicillin 500mg',   'amoxicillin',    'Interaksi dengan warfarin, methotrexate'),
('93001539', 'Ibuprofen 400mg',     'ibuprofen',      'Interaksi dengan aspirin, warfarin, lithium. Risiko perdarahan GI'),
('93000288', 'Metformin 500mg',     'metformin',      'Interaksi dengan alkohol, kontras iodin'),
('93000744', 'Amlodipine 5mg',      'amlodipine',     'Interaksi dengan simvastatin dosis tinggi'),
('93000195', 'Simvastatin 20mg',    'simvastatin',    'Interaksi dengan amlodipine, erythromycin, ketoconazole'),
('93001221', 'Aspirin 100mg',       'aspirin',        'Interaksi dengan ibuprofen, warfarin. Risiko perdarahan'),
('93000401', 'Omeprazole 20mg',     'omeprazole',     'Interaksi dengan clopidogrel, methotrexate'),
('93000612', 'Warfarin 2mg',        'warfarin',       'Interaksi dengan aspirin, ibuprofen, amoxicillin, paracetamol dosis tinggi'),
('93001102', 'Paracetamol 500mg',   'paracetamol',    NULL),
('93000950', 'Clopidogrel 75mg',    'clopidogrel',    'Interaksi dengan omeprazole, aspirin');
