# Deployment XAMPP Legacy

Panduan ini hanya referensi legacy jika aplikasi harus dijalankan tanpa Docker. Jalur utama saat ini adalah Docker Compose dan Coolify; lihat `deployment-docker.md`.

## Minimum Runtime

- Apache `>= 2.4`.
- PHP `>= 7.4`; pengujian lokal berjalan di PHP `8.2`.
- MySQL `>= 5.7` atau MariaDB `>= 10.5`.

## Port Dan Konfigurasi

- Contoh URL lokal: `http://localhost:8081/webgis/WebgisPovertyMapping/`.
- Apache dapat berjalan di `80`, `8080`, atau `8081`; sesuaikan URL dengan port XAMPP.
- MySQL/MariaDB default proyek memakai port `3307`.
- Jika MySQL berjalan di `3306`, ubah `DB_PORT` di `config.php`.
- Konfigurasi koneksi utama ada di `config.php`: `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`, dan `DB_PORT`.

## Import Database

1. Buat database dari schema:

```powershell
mysql -u root -P 3307 < setup_database.sql
```

2. Alternatif: buka phpMyAdmin, pilih tab SQL, lalu jalankan isi `setup_database.sql`.
3. Pastikan nama database sesuai `DB_NAME` di `config.php`.

## Akun Default

- Username: `admin`
- Password awal: `Admin1234`
- Setelah login pertama, admin wajib mengganti password.

## Proteksi Folder Non-Publik

Blokir folder tests dan tmp dari akses browser.

Kenapa: file di tests hanya untuk pengecekan developer. Beberapa test dapat menulis data ke database. Folder tmp dapat berisi session atau file sementara.

Pastikan `.htaccess` aktif dan Apache mengizinkan override:

```apache
AllowOverride All
```

Smoke test manual:

```text
http://localhost:8081/webgis/WebgisPovertyMapping/tests/test_proximity.php
```

Expected: `403 Forbidden`.

## Dependency Eksternal

- Leaflet CSS/JS dari `unpkg.com`.
- Chart.js dari `cdn.jsdelivr.net`.
- `leaflet.heat@0.2.0` dari `cdn.jsdelivr.net`.
- Google Fonts `Inter`.
- OpenStreetMap tiles dari `tile.openstreetmap.org`.
- Esri imagery dari `server.arcgisonline.com`.
- Carto dark tiles dari `basemaps.cartocdn.com`.
- Nominatim reverse geocoding dari `nominatim.openstreetmap.org`.

Jika CDN atau tile server tidak dapat diakses, peta dasar, chart, heatmap, font, atau reverse geocoding bisa terganggu walaupun data aplikasi tetap tersedia.

## Strategi Mirror Lokal

Jika aplikasi dipakai di jaringan tertutup, siapkan salinan lokal untuk:

- Leaflet CSS/JS.
- leaflet.heat.
- Chart.js.
- Lucide icons.
- Google Fonts Inter.

Tile peta juga perlu strategi sendiri:

- Gunakan tile server internal; atau
- Batasi penggunaan ke jaringan yang boleh mengakses OpenStreetMap, Esri, dan Carto.

## HTTPS Behind Reverse Proxy

Session cookie `Secure` aktif berdasarkan deteksi HTTPS di PHP. Jika Apache berada di belakang reverse proxy, pastikan header proxy HTTPS diteruskan dan server PHP mengenali request sebagai HTTPS.

## Abuse Control Publik

Form kontak donatur memiliki rate limit session sederhana: satu submit per 60 detik per browser session.
Untuk deploy publik serius, pertimbangkan CAPTCHA atau rate limit IP di web server.

## Troubleshooting

- DB gagal terhubung: cek service MySQL/MariaDB XAMPP, `DB_PORT`, username, password, dan nama database.
- Port Apache berbeda: gunakan URL sesuai port aktif XAMPP, misalnya `8081`.
- Import SQL gagal: pastikan MySQL/MariaDB aktif dan user punya izin membuat database/tabel.
- CDN tidak bisa diakses: siapkan mirror lokal untuk Leaflet, Chart.js, leaflet.heat, dan font bila deployment berada di jaringan tertutup.
- Tile peta lambat/gagal: cek akses ke OpenStreetMap, Esri, dan Carto.
- Nominatim gagal: reverse geocoding akan kosong, tetapi input koordinat tetap bisa dipakai.
- session timeout: nilai `SESSION_TIMEOUT` di `config.php` mengatur durasi sesi login.
