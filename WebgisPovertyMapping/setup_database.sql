-- setup_database.sql — WebGIS Poverty Mapping v1.0
-- Jalankan via phpMyAdmin (SQL tab) atau:
--   mysql -u root -P 3307 < setup_database.sql
-- Aman dijalankan berulang (CREATE IF NOT EXISTS + ALTER ADD COLUMN IF NOT EXISTS)

CREATE DATABASE IF NOT EXISTS db_webgis
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_webgis;

-- NOTE: data_jalan and data_parsil were removed from setup because those features are obsolete.
-- Existing production databases should only DROP those tables after backup and explicit approval.

-- ─── Core v1.0: rumah_ibadah ───────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS rumah_ibadah (
    id         INT           NOT NULL AUTO_INCREMENT,
    nama       VARCHAR(150)  NOT NULL,
    jenis      ENUM('Masjid','Mushola','Gereja','Pura','Vihara','Klenteng') NOT NULL DEFAULT 'Masjid',
    alamat     TEXT          DEFAULT NULL,
    lat        DECIMAL(10,8) NOT NULL,
    lng        DECIMAL(11,8) NOT NULL,
    radius_m   INT           NOT NULL DEFAULT 500,
    kontak     VARCHAR(100)  DEFAULT NULL,
    created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP     NULL DEFAULT NULL,
    PRIMARY KEY (id),
    INDEX idx_ri_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Upgrade kolom jika tabel sudah ada dari prototype
ALTER TABLE rumah_ibadah
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
    ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at;

-- ─── Core v1.0: penduduk_miskin ───────────────────────────────────────────

CREATE TABLE IF NOT EXISTS penduduk_miskin (
    id             INT           NOT NULL AUTO_INCREMENT,
    nama_kk        VARCHAR(150)  NOT NULL,
    nik            VARCHAR(16)   DEFAULT NULL,
    jumlah_jiwa    INT           NOT NULL DEFAULT 1,
    kategori       ENUM('Sangat Miskin','Miskin','Hampir Miskin') DEFAULT 'Miskin',
    alamat         TEXT          DEFAULT NULL,
    catatan        TEXT          DEFAULT NULL,
    lat            DECIMAL(10,8) NOT NULL,
    lng            DECIMAL(11,8) NOT NULL,
    ibadah_id      INT           DEFAULT NULL,
    jarak_m        FLOAT         DEFAULT NULL,
    is_blank_spot  TINYINT(1)    DEFAULT 0,
    status_bantuan ENUM('Belum Ditangani','Dalam Proses','Sudah Ditangani') DEFAULT 'Belum Ditangani',
    status_verifikasi ENUM('Pending','Terverifikasi','Ditolak') NOT NULL DEFAULT 'Pending',
    verified_by    INT           DEFAULT NULL,
    verified_at    TIMESTAMP     NULL DEFAULT NULL,
    catatan_verifikasi TEXT      DEFAULT NULL,
    is_active      TINYINT(1)    DEFAULT 1,
    created_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at     TIMESTAMP     NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_nik (nik),
    INDEX idx_pm_blank  (is_blank_spot),
    INDEX idx_pm_active (is_active),
    INDEX idx_pm_verifikasi (status_verifikasi),
    INDEX idx_pm_deleted(deleted_at),
    FOREIGN KEY (ibadah_id) REFERENCES rumah_ibadah(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Upgrade kolom jika tabel sudah ada dari prototype
ALTER TABLE penduduk_miskin
    ADD COLUMN IF NOT EXISTS catatan        TEXT          DEFAULT NULL                                                        AFTER alamat,
    ADD COLUMN IF NOT EXISTS is_blank_spot  TINYINT(1)    DEFAULT 0                                                          AFTER jarak_m,
    ADD COLUMN IF NOT EXISTS status_bantuan ENUM('Belum Ditangani','Dalam Proses','Sudah Ditangani') DEFAULT 'Belum Ditangani' AFTER is_blank_spot,
    ADD COLUMN IF NOT EXISTS status_verifikasi ENUM('Pending','Terverifikasi','Ditolak') NOT NULL DEFAULT 'Pending'          AFTER status_bantuan,
    ADD COLUMN IF NOT EXISTS verified_by    INT           DEFAULT NULL                                                        AFTER status_verifikasi,
    ADD COLUMN IF NOT EXISTS verified_at    TIMESTAMP     NULL DEFAULT NULL                                                   AFTER verified_by,
    ADD COLUMN IF NOT EXISTS catatan_verifikasi TEXT      DEFAULT NULL                                                        AFTER verified_at,
    ADD COLUMN IF NOT EXISTS is_active      TINYINT(1)    DEFAULT 1                                                          AFTER catatan_verifikasi,
    ADD COLUMN IF NOT EXISTS updated_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP              AFTER created_at,
    ADD COLUMN IF NOT EXISTS deleted_at     TIMESTAMP     NULL DEFAULT NULL                                                  AFTER updated_at;

ALTER TABLE penduduk_miskin
    ADD INDEX IF NOT EXISTS idx_pm_verifikasi (status_verifikasi);

ALTER TABLE penduduk_miskin
    ADD UNIQUE INDEX IF NOT EXISTS uq_nik (nik);

-- ─── Core v1.0: users ─────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS users (
    id                   INT           NOT NULL AUTO_INCREMENT,
    username             VARCHAR(100)  NOT NULL,
    nama_lengkap         VARCHAR(150)  NOT NULL,
    password             VARCHAR(255)  NOT NULL,
    role                 ENUM('administrator','operator','viewer') NOT NULL DEFAULT 'viewer',
    ibadah_id            INT           NULL DEFAULT NULL,
    is_active            TINYINT(1)    NOT NULL DEFAULT 1,
    must_change_password TINYINT(1)    NOT NULL DEFAULT 0,
    login_attempts       INT           NOT NULL DEFAULT 0,
    locked_until         TIMESTAMP     NULL DEFAULT NULL,
    created_at           TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_username (username),
    FOREIGN KEY (ibadah_id) REFERENCES rumah_ibadah(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Core v1.0: riwayat_bantuan (append-only) ─────────────────────────────

CREATE TABLE IF NOT EXISTS riwayat_bantuan (
    id           INT          NOT NULL AUTO_INCREMENT,
    penduduk_id  INT          NOT NULL,
    operator_id  INT          NOT NULL,
    status_lama  VARCHAR(50)  DEFAULT NULL,
    status_baru  VARCHAR(50)  NOT NULL,
    catatan      TEXT         DEFAULT NULL,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (penduduk_id) REFERENCES penduduk_miskin(id),
    FOREIGN KEY (operator_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── F1.1: kebutuhan ──────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS kebutuhan (
    id           INT           NOT NULL AUTO_INCREMENT,
    penduduk_id  INT           NOT NULL,
    kategori     ENUM('Sembako','Biaya Sekolah','Biaya Kesehatan','Modal Usaha',
                      'Renovasi Rumah','Perlengkapan Rumah','Pakaian','Lainnya') NOT NULL,
    deskripsi    VARCHAR(300)  DEFAULT NULL,
    status       ENUM('Belum Terpenuhi','Dalam Proses','Terpenuhi') NOT NULL DEFAULT 'Belum Terpenuhi',
    created_by   INT           NOT NULL,
    updated_by   INT           DEFAULT NULL,
    created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_k_penduduk (penduduk_id),
    INDEX idx_k_status   (status),
    INDEX idx_k_kategori (kategori),
    FOREIGN KEY (penduduk_id) REFERENCES penduduk_miskin(id),
    FOREIGN KEY (created_by)  REFERENCES users(id),
    FOREIGN KEY (updated_by)  REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── F1.1: riwayat_kebutuhan (append-only) ───────────────────────────────

CREATE TABLE IF NOT EXISTS riwayat_kebutuhan (
    id           INT          NOT NULL AUTO_INCREMENT,
    kebutuhan_id INT          NOT NULL,
    operator_id  INT          NOT NULL,
    status_lama  VARCHAR(50)  DEFAULT NULL,
    status_baru  VARCHAR(50)  NOT NULL,
    catatan      TEXT         DEFAULT NULL,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (kebutuhan_id) REFERENCES kebutuhan(id),
    FOREIGN KEY (operator_id)  REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── F1.1: kontak_donatur ────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS kontak_donatur (
    id              INT           NOT NULL AUTO_INCREMENT,
    nama            VARCHAR(150)  NOT NULL,
    kontak          VARCHAR(150)  NOT NULL,
    kategori_minat  ENUM('Sembako','Biaya Sekolah','Biaya Kesehatan','Modal Usaha',
                         'Renovasi Rumah','Perlengkapan Rumah','Pakaian','Lainnya') DEFAULT NULL,
    pesan           TEXT          DEFAULT NULL,
    is_read         TINYINT(1)    NOT NULL DEFAULT 0,
    created_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_kd_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Demo seed untuk penilaian — password semua akun: password ───────────
-- Rumah ibadah demo hanya dibuat kalau belum ada data rumah ibadah aktif.
INSERT INTO rumah_ibadah (nama, jenis, alamat, lat, lng, radius_m, kontak)
SELECT
    'Masjid Demo Operator',
    'Masjid',
    'Pontianak, Kalimantan Barat',
    -0.05570000,
    109.34870000,
    1000,
    '081234567890'
WHERE NOT EXISTS (
    SELECT 1 FROM rumah_ibadah WHERE deleted_at IS NULL
);

INSERT INTO users (username, nama_lengkap, password, role, ibadah_id, is_active, must_change_password)
VALUES
    (
        'admin',
        'Administrator',
        '$2y$10$SBXp6R9CuUxfD3gai9cVN.iPrHzFLqVi33bqbCAnuWBj7v0uTuYMK',
        'administrator',
        NULL,
        1,
        0
    ),
    (
        'operator',
        'Operator Demo',
        '$2y$10$SBXp6R9CuUxfD3gai9cVN.iPrHzFLqVi33bqbCAnuWBj7v0uTuYMK',
        'operator',
        (SELECT id FROM rumah_ibadah WHERE deleted_at IS NULL ORDER BY id LIMIT 1),
        1,
        0
    ),
    (
        'viewer',
        'Viewer Demo',
        '$2y$10$SBXp6R9CuUxfD3gai9cVN.iPrHzFLqVi33bqbCAnuWBj7v0uTuYMK',
        'viewer',
        NULL,
        1,
        0
    )
ON DUPLICATE KEY UPDATE
    nama_lengkap = VALUES(nama_lengkap),
    password = VALUES(password),
    role = VALUES(role),
    ibadah_id = VALUES(ibadah_id),
    is_active = 1,
    must_change_password = 0,
    login_attempts = 0,
    locked_until = NULL;
