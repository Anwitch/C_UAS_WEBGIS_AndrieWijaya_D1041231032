-- setup_database.sql — Project 01: WebGIS Choropleth Visualizer
-- Jalankan: mysql -u root -p < setup_database.sql
--            atau paste ke phpMyAdmin

CREATE DATABASE IF NOT EXISTS db_webgis_01 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_webgis_01;

-- ── Admin users ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id       INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- Development seed only. Before public deployment, rotate this password immediately
-- or replace this seed with an environment-specific admin creation step.
INSERT IGNORE INTO users (username, password) VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
);

-- ── Choropleth layers ─────────────────────────────────────────────────
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

-- ── Point of Interest / Lokasi usaha ──────────────────────────────────
CREATE TABLE IF NOT EXISTS lokasi_usaha (
    id           INT PRIMARY KEY AUTO_INCREMENT,
    nama_tempat  VARCHAR(150) NOT NULL,
    no_wa        VARCHAR(30) DEFAULT NULL,
    buka_24jam   TINYINT(1) NOT NULL DEFAULT 0,
    latitude     DECIMAL(10,8) NOT NULL,
    longitude    DECIMAL(11,8) NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Jalan polyline ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS data_jalan (
    id             INT PRIMARY KEY AUTO_INCREMENT,
    nama_jalan     VARCHAR(150) NOT NULL,
    status_jalan   ENUM('Nasional','Provinsi','Kabupaten') NOT NULL DEFAULT 'Kabupaten',
    geojson        LONGTEXT NOT NULL,
    panjang_meter  DOUBLE NOT NULL DEFAULT 0,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Parsil polygon ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS data_parsil (
    id                  INT PRIMARY KEY AUTO_INCREMENT,
    nama_parsil         VARCHAR(150) NOT NULL,
    status_kepemilikan  ENUM('SHM','HGB','HGU','HP') NOT NULL DEFAULT 'SHM',
    geojson             LONGTEXT NOT NULL,
    luas_m2             DOUBLE NOT NULL DEFAULT 0,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
