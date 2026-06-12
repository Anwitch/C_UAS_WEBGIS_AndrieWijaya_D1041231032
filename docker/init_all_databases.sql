-- =========================================================================
-- DATABASE 1: db_webgis_01 (Project 01: WebGIS Choropleth Visualizer)
-- =========================================================================
CREATE DATABASE IF NOT EXISTS db_webgis_01 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_webgis_01;

CREATE TABLE IF NOT EXISTS users (
    id       INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
);

INSERT IGNORE INTO users (username, password) VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
);

CREATE TABLE IF NOT EXISTS choropleth_layers (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    nama          VARCHAR(150) NOT NULL,
    deskripsi     TEXT,
    geojson       LONGTEXT NOT NULL,
    attribute_key VARCHAR(100) NOT NULL,
    palette       ENUM('blue','orange','green','purple') DEFAULT 'blue',
    is_visible    TINYINT(1) NOT NULL DEFAULT 1,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS lokasi_usaha (
    id           INT PRIMARY KEY AUTO_INCREMENT,
    nama_tempat  VARCHAR(150) NOT NULL,
    no_wa        VARCHAR(30) DEFAULT NULL,
    buka_24jam   TINYINT(1) NOT NULL DEFAULT 0,
    latitude     DECIMAL(10,8) NOT NULL,
    longitude    DECIMAL(11,8) NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS data_jalan (
    id             INT PRIMARY KEY AUTO_INCREMENT,
    nama_jalan     VARCHAR(150) NOT NULL,
    status_jalan   ENUM('Nasional','Provinsi','Kabupaten') NOT NULL DEFAULT 'Kabupaten',
    geojson        LONGTEXT NOT NULL,
    panjang_meter  DOUBLE NOT NULL DEFAULT 0,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS data_parsil (
    id                  INT PRIMARY KEY AUTO_INCREMENT,
    nama_parsil         VARCHAR(150) NOT NULL,
    status_kepemilikan  ENUM('SHM','HGB','HGU','HP') NOT NULL DEFAULT 'SHM',
    geojson             LONGTEXT NOT NULL,
    luas_m2             DOUBLE NOT NULL DEFAULT 0,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =========================================================================
-- DATABASE 2: db_webgis (Aplikasi Final: WebGIS Poverty Mapping)
-- =========================================================================
CREATE DATABASE IF NOT EXISTS db_webgis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_webgis;

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

ALTER TABLE rumah_ibadah
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
    ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at;

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

ALTER TABLE penduduk_miskin ADD INDEX IF NOT EXISTS idx_pm_verifikasi (status_verifikasi);
ALTER TABLE penduduk_miskin ADD UNIQUE INDEX IF NOT EXISTS uq_nik (nik);

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

INSERT INTO rumah_ibadah (nama, jenis, alamat, lat, lng, radius_m, kontak)
SELECT 'Masjid Demo Operator', 'Masjid', 'Pontianak, Kalimantan Barat', -0.05570000, 109.34870000, 1000, '081234567890'
WHERE NOT EXISTS (SELECT 1 FROM rumah_ibadah WHERE deleted_at IS NULL);

INSERT INTO users (username, nama_lengkap, password, role, ibadah_id, is_active, must_change_password)
VALUES
    ('admin', 'Administrator', '$2y$10$SBXp6R9CuUxfD3gai9cVN.iPrHzFLqVi33bqbCAnuWBj7v0uTuYMK', 'administrator', NULL, 1, 0),
    ('operator', 'Operator Demo', '$2y$10$SBXp6R9CuUxfD3gai9cVN.iPrHzFLqVi33bqbCAnuWBj7v0uTuYMK', 'operator', (SELECT id FROM rumah_ibadah WHERE deleted_at IS NULL ORDER BY id LIMIT 1), 1, 0),
    ('viewer', 'Viewer Demo', '$2y$10$SBXp6R9CuUxfD3gai9cVN.iPrHzFLqVi33bqbCAnuWBj7v0uTuYMK', 'viewer', NULL, 1, 0)
ON DUPLICATE KEY UPDATE
    nama_lengkap = VALUES(nama_lengkap),
    password = VALUES(password),
    role = VALUES(role),
    ibadah_id = VALUES(ibadah_id),
    is_active = 1,
    must_change_password = 0,
    login_attempts = 0,
    locked_until = NULL;
