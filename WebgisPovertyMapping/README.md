# WebGIS Poverty Mapping

Aplikasi utama Tugas Besar SIG untuk pemetaan warga miskin, rumah ibadah, kebutuhan bantuan, status bantuan, dan blank spot layanan berbasis Leaflet, PHP, dan MySQL/MariaDB.

Status audit terakhir: 2026-06-03. Kode aktif sudah memakai validasi koordinat bersama, CSRF untuk mutasi terautentikasi, session cookie hardening, scope operator, masking data publik, verifikasi warga sebelum workflow bantuan/kebutuhan, dan kebijakan import admin langsung `Terverifikasi`.

Catatan submit: project final ini sekarang dijalankan lewat Docker Compose dari root repository `webgis/`. Runtime utama memakai PHP 8.2 Apache dan MariaDB 11.4; database `db_webgis` otomatis dibuat dari `setup_database.sql` saat volume MariaDB masih kosong. SQL setup menggunakan syntax MariaDB-friendly seperti `ADD COLUMN IF NOT EXISTS`.

## Stack

- PHP procedural dengan MySQLi
- MySQL/MariaDB
- Leaflet untuk peta interaktif
- Chart.js untuk statistik
- Vanilla JavaScript modules
- Docker Compose sebagai runtime utama
- XAMPP/Laragon/WAMP hanya sebagai opsi legacy lokal

## Struktur Utama

```text
WebgisPovertyMapping/
├── index.php              # Shell peta utama
├── dashboard.php          # Dashboard administrator
├── config.php             # Konfigurasi app, session, dan database
├── koneksi.php            # Koneksi DB + Haversine/proximity helper
├── setup_database.sql     # Schema database dan seed admin
├── auth/                  # Login, change password, session/RBAC helper
├── api/                   # Endpoint JSON/CSV/HTML per domain
├── pages/                 # Halaman admin/operator
├── modules/               # JavaScript map modules
├── css/                   # CSS admin
├── tests/                 # Test PHP ad-hoc
└── docs/                  # Dokumentasi audit, deployment, dan superpowers plans/specs
```

## Setup Lokal Dengan Docker

Jalankan dari root repository `webgis/`, bukan dari folder `WebgisPovertyMapping/`:

```bash
cp .env.example .env
docker compose -f docker-compose.yml -f docker-compose.local.yml up --build -d
```

Akses aplikasi:

```text
http://localhost:8080/WebgisPovertyMapping/
```

Tambahkan `APP_PORT=8081` di file `.env` root jika port `8080` sudah dipakai. Database akan otomatis diinisialisasi oleh container MariaDB dari `WebgisPovertyMapping/setup_database.sql`.

Untuk reset database Docker dari awal:

```bash
docker compose -f docker-compose.yml -f docker-compose.local.yml down -v
docker compose -f docker-compose.yml -f docker-compose.local.yml up --build -d
```

XAMPP tidak lagi menjadi jalur utama. Dokumentasi XAMPP tetap ada sebagai referensi legacy di `docs/deployment-xampp.md`.

## Akun Default

Default akun demo untuk penilaian:

```text
Administrator: admin / password
Operator     : operator / password
Viewer       : viewer / password
```

Untuk demo penilaian, akun tersebut tidak dipaksa mengganti password saat login pertama.

## Role

- `viewer`: akses publik/read-only, data sensitif penduduk dimasking.
- `operator`: tambah/mengelola warga dan kebutuhan dalam rumah ibadah yang ditugaskan.
- `administrator`: kelola semua data, rumah ibadah, pengguna, import/export, verifikasi, dan recalculation.

Publik tanpa login tetap bisa membuka peta sebagai `viewer` read-only. Dashboard admin tidak menawarkan pembuatan akun `viewer` baru karena akses publik tidak membutuhkan akun; role tersebut dipertahankan sebagai fallback RBAC dan kompatibilitas akun lama.

Operator harus dikaitkan ke satu `rumah_ibadah` melalui halaman manajemen user.

Model ownership warga saat ini adalah `proximity-only`: `penduduk_miskin.ibadah_id` dihitung dari radius rumah ibadah terdekat dan menjadi dasar scope operator. Jika posisi/radius rumah ibadah berubah, admin dapat menjalankan hitung ulang proximity dan assignment warga bisa berubah mengikuti hasil spasial terbaru.

## Fitur Utama

- Peta interaktif Pontianak berbasis Leaflet.
- Layer rumah ibadah dengan radius layanan.
- Layer penduduk miskin dengan proximity dan blank spot.
- Status bantuan dan riwayat perubahan.
- Kebutuhan bantuan per warga dan papan kebutuhan publik.
- Dashboard statistik dan heatmap untuk operator/admin.
- Import CSV dan export laporan.
- RBAC berbasis session.
- Proteksi data publik: hanya warga `Terverifikasi` yang tampil, PII dimasking, dan koordinat rumah warga tidak dikirim untuk pengunjung publik.
- Workflow bantuan/kebutuhan hanya aktif untuk warga `Terverifikasi`.

## Dokumentasi Teknis

- `docs/codebase-navigation.md`: navigasi struktur repo, schema, endpoint, modul frontend, proximity, dan RBAC.
- `docs/codebase-audit-report.md`: laporan proses bisnis, wiring backend/frontend, data lifecycle, catatan logika, prioritas fix, dan saran pengembangan.
- `docs/business-process-sop.md`: SOP proses bisnis aktif untuk admin, operator, publik, dan donatur.
- `docs/deployment-docker.md`: panduan deployment Docker/Coolify, reverse proxy, database container, dan troubleshooting.
- `docs/deployment-xampp.md`: referensi legacy jika harus menjalankan aplikasi tanpa Docker.
- `docs/audit-fix-task-plan.md`: task plan audit/hardening lintas fase.
- `docs/superpowers/README.md`: indeks dokumen superpowers aktif.
- `docs/superpowers/plans/2026-06-03-codebase-audit-refresh.md`: ringkasan audit implementasi terbaru.
- `Tugas/` dan `DESIGN.md` adalah artifact lama/pendukung; gunakan dokumen di atas sebagai referensi implementasi aktif.

## Catatan Audit 2026-06-03

Yang sudah terlihat terpasang di kode:

- Validasi koordinat terpusat di `includes/validation.php` dan bounds wilayah studi di `config.php`.
- Import CSV admin menyimpan warga sebagai `Terverifikasi` beserta `verified_by` dan `verified_at`.
- Duplikasi NIK dicek terhadap semua record, termasuk arsip/inactive, agar validasi PHP sejalan dengan unique index database.
- Endpoint kebutuhan dan status bantuan menolak warga yang belum `Terverifikasi`.
- Statistik operator sudah scoped ke rumah ibadah operator.
- Heatmap publik disembunyikan; heatmap menggunakan dataset internal untuk operator/admin.
- User management mencegah self-demotion administrator dan operator ke rumah ibadah yang sudah dihapus.

Sisa pekerjaan yang masih disarankan:

- Rate limit session sederhana sudah aktif untuk form kontak donatur publik; pertimbangkan CAPTCHA atau rate limit IP untuk deploy publik serius.
- Pertimbangkan model ownership `hybrid` jika tanggung jawab operasional operator harus dipisah dari hasil proximity spasial.
- Perluas `tests/run_all.php` jika ingin mencakup semua test DB smoke test dalam satu runner.
- Siapkan mirror lokal untuk CDN/tile provider bila deployment berada di jaringan tertutup.

## Test dan Verifikasi Dengan Docker

Lint semua file PHP:

```bash
docker compose -f ../docker-compose.yml -f ../docker-compose.local.yml exec app sh -lc 'find /var/www/html/WebgisPovertyMapping -name "*.php" -print0 | xargs -0 -n1 php -l'
```

Test ad-hoc statis yang aman dijalankan dari root project:

```bash
docker compose -f ../docker-compose.yml -f ../docker-compose.local.yml exec app php /var/www/html/WebgisPovertyMapping/tests/run_all.php
docker compose -f ../docker-compose.yml -f ../docker-compose.local.yml exec app php /var/www/html/WebgisPovertyMapping/tests/test_verifikasi_binding.php
docker compose -f ../docker-compose.yml -f ../docker-compose.local.yml exec app php /var/www/html/WebgisPovertyMapping/tests/test_update_posisi_scope.php
docker compose -f ../docker-compose.yml -f ../docker-compose.local.yml exec app php /var/www/html/WebgisPovertyMapping/tests/test_operator_scope_endpoints.php
docker compose -f ../docker-compose.yml -f ../docker-compose.local.yml exec app php /var/www/html/WebgisPovertyMapping/tests/test_public_verification_filters.php
docker compose -f ../docker-compose.yml -f ../docker-compose.local.yml exec app php /var/www/html/WebgisPovertyMapping/tests/test_frontend_module_loader.php
docker compose -f ../docker-compose.yml -f ../docker-compose.local.yml exec app php /var/www/html/WebgisPovertyMapping/tests/test_frontend_reliability_guards.php
docker compose -f ../docker-compose.yml -f ../docker-compose.local.yml exec app php /var/www/html/WebgisPovertyMapping/tests/test_no_legacy_poi.php
docker compose -f ../docker-compose.yml -f ../docker-compose.local.yml exec app php /var/www/html/WebgisPovertyMapping/tests/test_viewer_public_filters.php
docker compose -f ../docker-compose.yml -f ../docker-compose.local.yml exec app php /var/www/html/WebgisPovertyMapping/tests/test_operator_scope_strict.php
docker compose -f ../docker-compose.yml -f ../docker-compose.local.yml exec app php /var/www/html/WebgisPovertyMapping/tests/test_penduduk_binding_types.php
docker compose -f ../docker-compose.yml -f ../docker-compose.local.yml exec app php /var/www/html/WebgisPovertyMapping/tests/test_csrf_enforcement.php
docker compose -f ../docker-compose.yml -f ../docker-compose.local.yml exec app php /var/www/html/WebgisPovertyMapping/tests/test_session_cookie_security.php
docker compose -f ../docker-compose.yml -f ../docker-compose.local.yml exec app php /var/www/html/WebgisPovertyMapping/tests/test_operator_ibadah_highlight.php
docker compose -f ../docker-compose.yml -f ../docker-compose.local.yml exec app php /var/www/html/WebgisPovertyMapping/tests/test_viewer_account_ui_hidden.php
```

Test yang memuat `../config.php` atau `../koneksi.php` perlu dijalankan dari folder `tests`:

```bash
docker compose -f ../docker-compose.yml -f ../docker-compose.local.yml exec app sh -lc 'cd /var/www/html/WebgisPovertyMapping/tests && php test_auth_helper.php'
docker compose -f ../docker-compose.yml -f ../docker-compose.local.yml exec app sh -lc 'cd /var/www/html/WebgisPovertyMapping/tests && php test_db.php'
docker compose -f ../docker-compose.yml -f ../docker-compose.local.yml exec app sh -lc 'cd /var/www/html/WebgisPovertyMapping/tests && php test_ibadah.php'
docker compose -f ../docker-compose.yml -f ../docker-compose.local.yml exec app sh -lc 'cd /var/www/html/WebgisPovertyMapping/tests && php test_kebutuhan.php'
docker compose -f ../docker-compose.yml -f ../docker-compose.local.yml exec app sh -lc 'cd /var/www/html/WebgisPovertyMapping/tests && php test_penduduk.php'
docker compose -f ../docker-compose.yml -f ../docker-compose.local.yml exec app sh -lc 'cd /var/www/html/WebgisPovertyMapping/tests && php test_proximity.php'
```

Beberapa test membutuhkan container database yang sudah sehat. Jika menjalankan mode XAMPP legacy, sesuaikan command PHP dan port database mengikuti `docs/deployment-xampp.md`.
