# Project 01 - WebGIS Dasar

Project ini adalah tugas kelas WebGIS dasar berbasis Leaflet.js, PHP, dan MySQL/MariaDB. Fokusnya adalah pengelolaan layer geometri dasar: point, polyline, dan polygon.

## Status Project

Project ini disertakan sebagai artifact tugas kelas. Aplikasi final Tugas Besar ada di folder `../WebgisPovertyMapping`.

File utama untuk demo project ini adalah:

```text
index.php
```

File `index.html` dan endpoint root seperti `simpan.php`, `ambil_data.php`, `hapus.php`, dan `update_posisi.php` adalah peninggalan versi awal POI. Demo utama menggunakan `index.php` dan endpoint di folder `api/`.

## Fitur

| Fitur | Deskripsi |
| --- | --- |
| POI/lokasi usaha | Tambah, tampilkan, hapus, dan drag marker lokasi usaha |
| Data jalan | Gambar polyline jalan, simpan status jalan, hitung panjang |
| Data parsil | Gambar polygon bidang tanah, simpan status kepemilikan, hitung luas |
| CRUD API | Endpoint per layer di `api/point/`, `api/jalan/`, dan `api/parsil/` |
| Peta interaktif | Leaflet.js dengan tile OpenStreetMap |

## Struktur Utama

```text
01/
├── index.php
├── koneksi.php
├── setup_database.sql
├── api/
│   ├── point/
│   ├── jalan/
│   └── parsil/
└── modules/
    ├── point.js
    ├── jalan.js
    └── parsil.js
```

## Database

Project ini memakai database khusus:

```text
db_webgis_01
```

Import schema:

```powershell
mysql -u root -P 3307 < setup_database.sql
```

Jika MySQL/MariaDB lokal memakai port lain, sesuaikan `DB_PORT` pada `koneksi.php`.

## Konfigurasi

Konfigurasi database ada di `koneksi.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_webgis_01');
define('DB_PORT', 3307);
```

## Akses Lokal

```text
http://localhost/webgis/01/
```

## Dependensi

- Leaflet.js
- OpenStreetMap tile
- Google Fonts

Dependensi frontend dimuat dari CDN, sehingga demo membutuhkan koneksi internet.
