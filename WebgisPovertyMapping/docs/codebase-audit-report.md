# Codebase Audit Report - WebGIS Poverty Mapping

> Updated: 2026-06-03. Laporan ini merangkum proses bisnis aktif, wiring backend/frontend, lifecycle data, catatan logika, prioritas fix, dan saran pengembangan berdasarkan traverse codebase dan dokumentasi lokal.

## Ringkasan Eksekutif

Proyek ini adalah aplikasi WebGIS PHP prosedural + MySQLi + Leaflet untuk memetakan penduduk miskin, rumah ibadah, radius layanan, blank spot, status bantuan, kebutuhan bantuan, papan kebutuhan publik, import/export, dan dashboard admin.

Implementasi aktif sudah cukup konsisten dengan dokumentasi teknis di `README.md`, `docs/codebase-navigation.md`, dan `docs/superpowers/plans/2026-06-03-codebase-audit-refresh.md`. Dokumen lama di `Tugas/` dan sebagian plan historis bukan sumber kebenaran implementasi aktif karena masih membawa artifact lama atau rencana fase yang sudah selesai.

Status audit refresh 2026-06-03:

1. Validasi koordinat sudah dipusatkan di `includes/validation.php` dan dipakai oleh endpoint mutasi/import utama.
2. Import admin sudah menghasilkan warga `Terverifikasi` beserta `verified_by` dan `verified_at`.
3. Policy NIK sudah dibuat ketat: NIK dicek terhadap semua record, termasuk arsip/inactive, agar sejalan dengan unique index.
4. Statistik operator sudah memakai scope rumah ibadah operator.
5. Workflow kebutuhan dan status bantuan sudah dikunci untuk warga `Terverifikasi`.
6. Heatmap publik sudah disembunyikan karena koordinat warga publik tetap dimasking.
7. User management dan soft delete rumah ibadah sudah memiliki guard tambahan.

Risiko residual terbesar saat ini:

1. Form kontak donatur publik belum memiliki rate limit/CAPTCHA.
2. Model ownership `proximity-only` bisa mengubah scope operator setelah recalculate; pertimbangkan model hybrid jika dipakai multi-operator serius.
3. Test runner masih ad-hoc; belum ada satu command tunggal untuk semua static guard dan DB smoke test.
4. Dependency CDN/tile provider eksternal perlu strategi mirror lokal untuk deployment jaringan tertutup.

## Triage Review Tambahan 2026-06-03

Review tambahan menyorot beberapa risiko bisnis yang perlu dibedakan antara bug yang sudah jelas, keputusan produk yang perlu dikunci, dan optimasi jangka menengah.

### Valid Dan Perlu Masuk Prioritas

1. Aksi kebutuhan pada warga `Pending` belum dikunci. Endpoint `api/kebutuhan/simpan.php` dan `api/kebutuhan/update_status.php` hanya mengecek warga aktif, tidak mengecek `status_verifikasi`. UI popup juga membangun section kebutuhan untuk user login tanpa membedakan status verifikasi. Jika warga ditolak admin, data kebutuhan yang sudah dibuat tetap tertinggal dan bisa menjadi data tidak relevan.
2. Heatmap publik tidak akan berfungsi dengan data warga saat ini karena `api/penduduk/ambil.php` mengosongkan `lat` dan `lng` untuk non-privileged user, sementara `modules/heatmap.js` membutuhkan koordinat valid. Ini adalah tradeoff privasi vs visualisasi yang perlu diputuskan eksplisit.
3. Import admin memang perlu policy yang lebih jelas. Solusi minimum adalah set hasil import admin ke `Terverifikasi`; solusi lebih matang adalah staging/bulk verify sebelum commit permanen.
4. NIK vs soft delete memang perlu strategi lifecycle. Mengubah NIK saat soft delete adalah opsi cepat, tetapi perlu audit trail jelas agar data historis tidak membingungkan.
5. Refresh data lintas modul sebaiknya distandarkan. Pola event atau `window.refreshAllData()` akan mengurangi state drift antar map, list, stats, dan heatmap.

### Perlu Keputusan Produk

1. Kepemilikan warga operator saat ini ditentukan oleh hasil proximity, bukan input bebas operator. Pada `api/penduduk/simpan.php`, data operator tidak dipindahkan diam-diam ke rumah ibadah lain; request ditolak jika titik tidak masuk rumah ibadah operator. Namun, `ibadah_id` warga dapat berubah setelah admin mengubah posisi/radius rumah ibadah atau menjalankan recalculation. Produk perlu memilih:
   - ownership dinamis berbasis proximity, cocok untuk analisis spasial otomatis;
   - ownership terkunci berbasis operator input, cocok untuk tanggung jawab operasional;
   - hybrid: simpan `assigned_ibadah_id` untuk ownership operasional dan `nearest_ibadah_id` untuk hasil analisis spasial.
2. Spatial fuzzing untuk publik dapat menghidupkan heatmap publik, tetapi titik jitter 50-100 meter tetap perlu aturan konsisten agar tidak menciptakan kesan akurasi palsu. Alternatif yang lebih aman adalah endpoint agregat grid/kelurahan untuk heatmap publik, bukan titik warga individual.

### Keputusan Eksekusi Audit 2026-06-03

1. Model ownership warga untuk batch fix ini tetap `proximity-only`. `penduduk_miskin.ibadah_id` tetap menjadi hasil `calc_proximity()` dan menjadi dasar scope operator. Perubahan schema `assigned_ibadah_id`/`nearest_ibadah_id` tidak dilakukan dalam batch ini agar tidak mengubah kontrak bisnis besar tanpa PRD lanjutan.
2. Strategi heatmap publik untuk batch fix ini adalah `hidden`: kontrol heatmap disembunyikan untuk viewer publik karena koordinat warga publik tetap dimasking. Endpoint agregat grid/kelurahan dapat dirancang sebagai pengembangan lanjutan jika produk membutuhkan visualisasi heatmap publik tanpa membocorkan koordinat rumah warga.

### Optimasi Jangka Menengah

1. Bounding box untuk Haversine bagus untuk pencarian kandidat terdekat, tetapi recalculation saat perubahan rumah ibadah tetap perlu menyentuh semua warga aktif jika sistem ingin memastikan semua assignment global tetap benar. Optimasi yang lebih tepat bisa berupa index lat/lng, batch processing, atau recalculation queue.
2. Closed-loop donatur ke kebutuhan spesifik akan meningkatkan akuntabilitas, tetapi membutuhkan model data tambahan seperti `donasi`, `donasi_kebutuhan`, status pledge/confirmed, dan audit penyelesaian.

### Batas Skala Proximity Saat Ini

`calc_proximity()` melakukan scan semua rumah ibadah aktif untuk setiap warga. Ini sederhana dan benar untuk data kecil-menengah, tetapi recalculate/import dapat melambat jika jumlah warga atau rumah ibadah besar.

Opsi peningkatan:

1. Tambah index `lat` dan `lng` untuk membantu query kandidat.
2. Gunakan bounding box kasar sebelum Haversine.
3. Jalankan recalculate dalam batch.
4. Simpan job recalculate jika proses lebih dari beberapa detik.

Jangan mengganti proximity engine sebelum ada data performa nyata. Algoritma saat ini mudah dipahami dan sudah punya test. Optimasi harus menjaga hasil assignment tetap sama.

## Dokumentasi Yang Berlaku

| File | Status | Catatan |
| --- | --- | --- |
| `README.md` | Aktif | Setup, stack, role, fitur, dan test. |
| `docs/codebase-navigation.md` | Aktif | Referensi teknis paling lengkap untuk struktur, API, modul frontend, DB, RBAC, proximity. |
| `setup_database.sql` | Aktif | Schema otoritatif dan seed admin default. |
| `docs/superpowers/specs/2026-05-24-poverty-mapping-prd.md` | Historis/produk | PRD berguna untuk konteks fitur, tetapi status implementasi perlu diupdate. |
| `docs/superpowers/plans/*.md` | Historis implementasi | Log rencana/fase kerja. |
| `Tugas/*` | Artifact tugas lama | Masih menyebut jalan/parsil/POI; jangan dijadikan acuan implementasi aktif. |
| `DESIGN.md` | Artifact desain lama | Bukan referensi desain aktif WebGIS. |

## Proses Bisnis Aktif

1. Administrator menyiapkan sistem dengan mengatur `config.php` dan menjalankan `setup_database.sql`.
2. Administrator login memakai akun default `admin / Admin1234`, lalu wajib mengganti password.
3. Administrator membuat dan mengelola `rumah_ibadah`: nama, jenis, alamat, kontak, koordinat, dan `radius_m`.
4. Administrator membuat akun operator dan mengaitkannya ke satu rumah ibadah melalui `users.ibadah_id`.
5. Administrator atau operator mendata `penduduk_miskin` berbasis koordinat.
6. Backend menjalankan `calc_proximity()` di `koneksi.php` untuk menentukan rumah ibadah terdekat yang mencakup warga.
7. Jika warga masuk radius salah satu rumah ibadah, sistem menyimpan `ibadah_id`, `jarak_m`, dan `is_blank_spot=0`; jika tidak, `ibadah_id=NULL`, `jarak_m` ke rumah ibadah terdekat, dan `is_blank_spot=1`.
8. Data yang dibuat administrator langsung `Terverifikasi`; data yang dibuat operator masuk `Pending` dan perlu diverifikasi admin.
9. Publik tanpa login diperlakukan sebagai viewer: hanya data `Terverifikasi`, PII dimasking, dan koordinat rumah warga tidak dikirim.
10. Operator mengelola warga dalam scope rumah ibadahnya: status bantuan, riwayat bantuan, dan kebutuhan bantuan.
11. Kebutuhan `Belum Terpenuhi` dari warga terverifikasi diagregasi ke `papan-kebutuhan.php`.
12. Donatur publik mengirim kontak/minat bantuan; admin membaca dan menandai kontak donatur sebagai read.
13. Admin mengakses dashboard, tren, import CSV, export CSV/PDF, arsip, verifikasi, user management, dan recalculation proximity.

## Wiring Backend

Backend tidak memakai framework MVC. File endpoint di `api/` berperan sebagai controller, `koneksi.php` dan `auth/helper.php` sebagai helper/service utama, dan schema di `setup_database.sql` sebagai model data.

### Entry Point Dan Helper

| Area | File | Peran |
| --- | --- | --- |
| Konfigurasi | `config.php` | Konstanta app, session, database, map center. |
| DB/proximity | `koneksi.php` | Koneksi global `$conn`, `haversine_distance()`, `calc_proximity()`. |
| Auth/RBAC | `auth/helper.php` | Session, role hierarchy, `require_auth()`, `has_role()`, CSRF. |
| Peta publik/auth-aware | `index.php` | Shell utama Leaflet dan module loader. |
| Dashboard | `dashboard.php` | Ringkasan admin dan trend dashboard. |
| Halaman admin/operator | `pages/*.php` | UI domain per fitur. |
| API | `api/**/*.php` | Endpoint JSON/CSV/HTML per domain. |

### Endpoint Per Domain

| Domain | Endpoint Utama | Akses |
| --- | --- | --- |
| Auth | `api/auth/login.php`, `change_password.php`, `logout.php` | Login/logout, ganti password, lockout. |
| Rumah ibadah | `api/ibadah/ambil.php`, `simpan.php`, `update.php`, `hapus.php`, `update_posisi.php`, `update_radius.php`, `update_kontak.php`, `recalculate.php` | GET publik untuk data aktif; mutasi admin + CSRF. |
| Penduduk | `api/penduduk/ambil.php`, `simpan.php`, `update_posisi.php`, `update_status.php`, `riwayat.php`, `verifikasi.php`, `hapus.php`, `nonaktifkan.php`, `arsip.php` | Public read masked; operator/admin mutasi scoped; verifikasi/admin. |
| Kebutuhan | `api/kebutuhan/ambil.php`, `simpan.php`, `update_status.php` | Operator/admin scoped + CSRF untuk mutasi. |
| Statistik | `api/stats/ambil.php`, `api/dashboard/trend.php` | Public/operator/admin stats; trend admin. |
| Papan publik | `api/papan/ambil.php`, `kontak_donatur.php`, `donatur.php` | Public needs/donor form; admin donor inbox. |
| Users | `api/users/ambil.php`, `simpan.php`, `update.php`, `reset_password.php`, `toggle_active.php` | Admin + CSRF untuk mutasi. |
| Import/export | `api/import/csv.php`, `template.php`, `api/export/csv.php`, `pdf.php` | Admin. |

### Auth Dan Scope

- Role hierarchy: `viewer < operator < administrator`.
- Unauthenticated map user diperlakukan sebagai viewer.
- `require_auth('operator')` mengizinkan operator dan administrator.
- Mutasi terautentikasi memakai `require_csrf()`.
- Operator dibatasi oleh `get_ibadah_id()` dan `penduduk_miskin.ibadah_id` di endpoint penduduk/kebutuhan/riwayat.
- Public endpoint penduduk hanya mengirim data terverifikasi dan memasking `nik`, `nama_kk`, `alamat`, `catatan`, `lat`, dan `lng`.

## Wiring Frontend

Frontend adalah PHP-rendered pages + vanilla JavaScript global modules. Tidak ada bundler, framework SPA, atau package manager JS.

### Entrypoint

| File | Peran |
| --- | --- |
| `index.html` | Redirect legacy ke `index.php`. |
| `index.php` | Shell peta utama, inject `window.APP_USER`, Leaflet, Chart.js, module loader. |
| `papan-kebutuhan.php` | Papan kebutuhan publik dan form kontak donatur. |
| `auth/login.php` | Login form POST ke API auth. |
| `auth/change_password.php` | Ganti password dengan CSRF. |
| `dashboard.php` | Dashboard admin. |
| `pages/map.php` | Iframe `../index.php?embedded=1` untuk map di shell admin. |
| `pages/*.php` | Halaman domain admin/operator. |

### Modul Map

| Module | State/Entry | API Yang Dipakai |
| --- | --- | --- |
| `modules/ibadah.js` | `window.initIbadah()`, `window._ibadahList` | `api/ibadah/ambil.php`, `simpan.php`, `update_posisi.php`, `update_kontak.php`, `update_radius.php`, `hapus.php`, `recalculate.php`. |
| `modules/penduduk.js` | `window.initPenduduk()`, `window._pendudukList` | `api/penduduk/ambil.php`, `simpan.php`, `update_posisi.php`, `update_status.php`, `riwayat.php`, `hapus.php`, `nonaktifkan.php`, `verifikasi.php`. |
| `modules/kebutuhan.js` | Loaded only for authenticated user | `api/kebutuhan/ambil.php`, `simpan.php`, `update_status.php`. |
| `modules/stats.js` | `window._showDashboard()`, `window._statsUpdate()` | `api/stats/ambil.php`. |
| `modules/heatmap.js` | `window._toggleHeatmap()`, `window._heatmapUpdate()` | Uses `window._pendudukList`; no direct API. |

### Flow Frontend-ke-Frontend

- `index.php` injects `window.APP_USER` for role, login status, assigned `ibadahId`, and CSRF token.
- `window.MAP_MODULES` loads module scripts sequentially; once complete, calls `initIbadah()` then `initPenduduk()`.
- `ibadah.js` fills `window._ibadahList`, then `penduduk.js` uses it for client-side proximity preview.
- `penduduk.js` fills `window._pendudukList`, then `stats.js` and `heatmap.js` read it for modal stats/heatmap.
- `ibadah.js` can call `_pendudukRecalcAll()` after ibadah changes.
- `penduduk.js` calls `_statsUpdate()`, `_heatmapUpdate()`, and blank spot counter update after list changes.
- `pages/map.php` embeds map in iframe, so page state and map state communicate through API/server rather than shared memory.

## Data Lifecycle

### Rumah Ibadah

- Dibuat/diedit oleh admin.
- `radius_m` menentukan cakupan layanan.
- Perubahan posisi/radius/delete memicu recalculation semua penduduk aktif.
- Delete bersifat soft delete dengan `deleted_at`.

### Penduduk Miskin

- Data aktif adalah `is_active=1 AND deleted_at IS NULL`.
- `deleted_at` dipakai untuk koreksi entry salah.
- `is_active=0` dipakai untuk warga pindah/meninggal/arsip real-world.
- `status_verifikasi` mengontrol visibilitas publik: hanya `Terverifikasi` yang muncul ke publik/papan kebutuhan.
- `status_bantuan` mengontrol workflow bantuan: `Belum Ditangani`, `Dalam Proses`, `Sudah Ditangani`.
- `riwayat_bantuan` append-only untuk audit perubahan status bantuan.

### Kebutuhan

- Kebutuhan menempel ke `penduduk_miskin`.
- Status: `Belum Terpenuhi`, `Dalam Proses`, `Terpenuhi`.
- `riwayat_kebutuhan` append-only untuk audit status kebutuhan.
- Papan publik mengagregasi kebutuhan `Belum Terpenuhi` dari warga aktif dan terverifikasi.

### User

- `administrator` mengelola sistem penuh.
- `operator` wajib dikaitkan ke satu rumah ibadah untuk scope kerja.
- `viewer` masih ada di backend sebagai fallback/kompatibilitas, tetapi UI tidak menawarkan pembuatan akun viewer baru.
- Password default/temp memaksa `must_change_password=1`.

### Donatur

- Publik mengirim kontak ke `kontak_donatur`.
- Admin melihat dan menandai semua unread sebagai read.
- Belum ada rate limit/spam mitigation yang terdokumentasi.

## Catatan Logika Dan Risiko

### Prioritas Tinggi Audit Awal - Status Refresh 2026-06-03

| Temuan Audit Awal | Status Saat Ini | Referensi |
| --- | --- | --- |
| Validasi koordinat tidak konsisten. | Selesai: helper `validate_lat_lng()` dipakai di endpoint utama dan import. | `includes/validation.php`, `api/penduduk/simpan.php`, `api/penduduk/update_posisi.php`, `api/ibadah/simpan.php`, `api/ibadah/update.php`, `api/ibadah/update_posisi.php`, `api/import/csv.php` |
| Import admin tidak mengisi `status_verifikasi`. | Selesai: import admin set `Terverifikasi`, `verified_by`, `verified_at`. | `api/import/csv.php` |
| Unique NIK global tidak cocok dengan pengecekan duplikat hanya data aktif. | Selesai dengan policy ketat: semua record dicek, termasuk arsip/inactive. | `api/penduduk/simpan.php`, `api/import/csv.php`, `setup_database.sql` |
| Statistik operator berpotensi global. | Selesai: stats memakai scope operator. | `api/stats/ambil.php` |
| Update status bantuan dari popup map bisa membuat UI stale. | Sebagian besar selesai: update state lokal, refresh list, dan dispatch event data changed. Tetap perlu test manual end-to-end di browser. | `modules/penduduk.js`, `index.php` |
| Kebutuhan dapat dibuat/diubah untuk warga `Pending`. | Selesai: endpoint menolak non-`Terverifikasi`, UI dibuat read-only/filtered. | `api/kebutuhan/simpan.php`, `api/kebutuhan/update_status.php`, `modules/kebutuhan.js`, `pages/kebutuhan.php` |
| Heatmap publik kosong karena koordinat publik dimasking. | Selesai dengan strategi `hidden`: kontrol heatmap hanya untuk operator/admin. | `index.php`, `modules/heatmap.js` |

### Prioritas Menengah

| Temuan | Dampak | Referensi |
| --- | --- | --- |
| Soft delete rumah ibadah tidak memakai `AND deleted_at IS NULL` dan tidak cek `affected_rows`. | Request ke ID deleted/tidak ada bisa tampak sukses. | `api/ibadah/hapus.php:40` |
| Update kontak rumah ibadah tidak mengecek `deleted_at IS NULL`. | Rumah ibadah soft-deleted masih bisa diubah kontaknya. | `api/ibadah/update_kontak.php:23` |
| Client dan server sama-sama punya logic proximity. | Rawan drift jika aturan proximity berubah. | `koneksi.php:28`, `modules/penduduk.js` |
| Heatmap memakai `_pendudukList` yang bisa sudah terfilter/visible. | Heatmap berubah diam-diam saat filter/layer berubah, tidak selalu merepresentasikan dataset penuh. | `modules/heatmap.js` |
| UI status bantuan tidak mengirim `catatan`, padahal backend menyimpan audit note. | Audit trail kurang informatif. | `api/penduduk/update_status.php`, `modules/penduduk.js` |
| Backend masih menerima create/update role `viewer`. | Kebijakan UI dan backend tidak sepenuhnya sinkron. | `api/users/simpan.php`, `api/users/update.php`, `README.md` |
| Admin bisa self-demotion via update user. | Risiko kehilangan akses admin jika tidak ada admin lain. | `api/users/update.php` |

### Prioritas Rendah

| Temuan | Dampak | Referensi |
| --- | --- | --- |
| Sidebar punya dead link `Pengaturan Sistem` dan `Aktivitas & Audit Log`. | Navigasi terlihat belum selesai. | `includes/page-start.php` |
| Naming legacy `searchPoi` dan toast POI masih ada. | Membingungkan karena fitur POI legacy sudah dihapus. | `index.php` |
| Papan publik memakai inline `onclick` untuk string kategori. | Saat ini mitigated oleh enum kategori, tetapi pola rapuh. | `papan-kebutuhan.php` |
| `DESIGN.md` berada di root walau bukan referensi aktif. | Developer baru bisa salah membaca arah desain. | `DESIGN.md` |

## Rekomendasi Fix

### Backend

1. Tambahkan helper validasi koordinat bersama, misalnya `validate_lat_lng($lat, $lng)` dan pakai di semua endpoint penduduk/ibadah/import.
2. Putuskan policy import admin: jika import dianggap aksi admin final, set `status_verifikasi='Terverifikasi'`, `verified_by=get_user_id()`, `verified_at=NOW()` pada insert import.
3. Selaraskan NIK dengan lifecycle soft delete/inactive:
   - opsi cepat: cek semua NIK termasuk inactive/deleted dan tampilkan pesan jelas;
   - opsi lebih baik: ubah constraint ke unique untuk NIK aktif saja via generated column/partial strategy sesuai DB yang dipakai.
4. Kunci endpoint kebutuhan untuk warga non-`Terverifikasi`, atau set policy eksplisit bahwa kebutuhan hanya boleh dibuat setelah approve admin.
5. Tambahkan operator scope ke `api/stats/ambil.php` bila operator tidak boleh melihat agregat global.
6. Perketat endpoint soft-delete/update dengan `deleted_at IS NULL`, `affected_rows`, dan response 404/error saat data tidak ditemukan.
7. Tambahkan guard user management untuk self-demotion dan admin terakhir.
8. Ekstrak helper response JSON, method guard, CSRF/method utility, coordinate validation, recalc-all, dan operator scope check agar endpoint tidak terus menduplikasi pola.

### Frontend

1. Setelah update status bantuan berhasil, update state lokal lalu panggil refresh list/stat/heatmap.
2. Jadikan backend sebagai sumber kebenaran proximity setelah perubahan posisi/radius rumah ibadah; reload penduduk dari API setelah recalc penting.
3. Pisahkan dataset mentah dan dataset visible, misalnya `_pendudukAll` dan `_pendudukVisible`.
4. Buat helper JS bersama untuk `fetchJson`, `appendCsrf`, escape, render badge, dan status update.
5. Tambahkan modal input `catatan` untuk perubahan status bantuan/kebutuhan, atau hapus ekspektasi catatan dari audit UI jika tidak dipakai.
6. Disable action kebutuhan/status bantuan untuk warga `Pending`/`Ditolak` di UI operator.
7. Pilih strategi heatmap publik: sembunyikan kontrol untuk viewer, gunakan endpoint agregat grid, atau gunakan spatial fuzzing dengan disclaimer akurasi.
8. Ganti inline event string di `papan-kebutuhan.php` dengan `data-*` dan `addEventListener`.
9. Bersihkan dead link dan naming legacy POI.

### Dokumentasi/Operasional

1. Dokumen deployment utama sekarang ada di `docs/deployment-docker.md`; `docs/deployment-xampp.md` dipertahankan sebagai referensi legacy.
2. Tambahkan dokumentasi dependency eksternal: Leaflet, Chart.js, leaflet.heat, Google Fonts, OSM/Esri/Carto tiles, Nominatim.
3. Tambahkan `config.example.php` atau `.env.example` jika proyek akan dipakai lintas mesin.
4. Update PRD dengan status implemented/partial/pending.
5. Pindahkan atau arsipkan `DESIGN.md` ke `docs/archive/` agar root documentation tidak ambigu.

## Saran Pengembangan

- Tambahkan audit log admin yang bisa dicari untuk perubahan user, ibadah, verifikasi, dan import.
- Tambahkan rate limiting atau CAPTCHA ringan untuk `api/papan/kontak_donatur.php`.
- Tambahkan batch job/recalculate queue jika jumlah data penduduk besar, karena update radius/posisi ibadah saat ini loop semua warga aktif.
- Tambahkan export laporan untuk blank spot dan kebutuhan per kategori.
- Tambahkan map bounds/wilayah studi di config agar koordinat di luar Pontianak bisa ditolak lebih awal.
- Tambahkan test runner tunggal untuk menjalankan seluruh `tests/test_*.php` dengan urutan yang jelas.
- Pertimbangkan service/helper layer ringan untuk domain `Penduduk`, `Ibadah`, `Kebutuhan`, dan `User` agar endpoint procedural tetap mudah dirawat.

## Prioritas Eksekusi Disarankan

1. Fix validasi koordinat dan import verification policy.
2. Kunci workflow kebutuhan/status bantuan untuk warga belum `Terverifikasi`.
3. Fix NIK lifecycle/constraint mismatch.
4. Putuskan ownership model: proximity-only, operator-locked, atau hybrid `assigned_ibadah_id` + `nearest_ibadah_id`.
5. Putuskan strategi heatmap publik: hidden, agregat grid, atau spatial fuzzing.
6. Scope operator untuk statistik bila memang data agregat harus terbatas.
7. Fix stale UI status bantuan dan heatmap dataset.
8. Hardening user management dan soft-delete endpoint.
9. Cleanup dead link/legacy naming.
10. Tambah deployment/config docs.
