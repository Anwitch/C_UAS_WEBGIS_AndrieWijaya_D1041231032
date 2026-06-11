# Tugas Besar SIG - WebGIS Poverty Mapping

Repository ini berisi aplikasi utama Tugas Besar SIG dan artifact tugas kelas WebGIS dasar.

## Prioritas Penilaian

Jalankan dan nilai aplikasi utama pada folder:

```text
WebgisPovertyMapping/
```

Folder `01/` disertakan sebagai bukti tugas kelas/prototype pengembangan sebelum aplikasi final.

## Daftar Project

| Folder | Peran | Ringkasan |
| --- | --- | --- |
| `01/` | Tugas kelas WebGIS dasar | CRUD POI/lokasi usaha, digitasi jalan polyline, digitasi parsil polygon |
| `WebgisPovertyMapping/` | Aplikasi final tugas besar | Auth, role admin/operator/viewer, dashboard, kebutuhan bantuan, import/export, keamanan, laporan |

## Mapping Database

Setiap project memakai database terpisah agar schema tidak saling bertabrakan saat di-import.

```text
01                   -> db_webgis_01
WebgisPovertyMapping -> db_webgis
```

## URL Lokal Dengan Docker

Jalankan dari root repository dengan Docker Compose lokal:

```bash
cp .env.example .env
docker compose -f docker-compose.yml -f docker-compose.local.yml up --build -d
```

Buka:

```text
http://localhost:8080/
http://localhost:8080/01/
http://localhost:8080/WebgisPovertyMapping/
```

Tambahkan `APP_PORT=8081` di `.env` jika port `8080` sudah dipakai.

## Quick Start Aplikasi Final

Setelah Docker berjalan, buka aplikasi final:

```text
http://localhost:8080/WebgisPovertyMapping/
```

Database `db_webgis` otomatis dibuat dan diisi dari `WebgisPovertyMapping/setup_database.sql` saat volume MariaDB Docker masih kosong.

XAMPP tidak lagi menjadi jalur utama. Jika perlu menjalankan mode legacy tanpa Docker, lihat `WebgisPovertyMapping/docs/deployment-xampp.md`.

## Quick Start Docker

Docker setup menjalankan dua service:

- `app`: PHP 8.2 + Apache untuk root repo, `01/`, dan `WebgisPovertyMapping/`.
- `db`: MariaDB 11.4 yang otomatis import `01/setup_database.sql` dan `WebgisPovertyMapping/setup_database.sql` saat volume database masih kosong.

Jalankan dari root repository:

```bash
cp .env.example .env
docker compose -f docker-compose.yml -f docker-compose.local.yml up --build -d
```

Buka:

```text
http://localhost:8080/
http://localhost:8080/01/
http://localhost:8080/WebgisPovertyMapping/
```

Tambahkan `APP_PORT=8081` di `.env` jika port `8080` sudah dipakai. Untuk reset database Docker dari awal:

```bash
docker compose -f docker-compose.yml -f docker-compose.local.yml down -v
docker compose -f docker-compose.yml -f docker-compose.local.yml up --build -d
```

## Deployment Coolify

Gunakan repo ini sebagai source project Coolify dengan Docker Compose deployment. File `docker-compose.yml` adalah konfigurasi production: service `app` hanya memakai `expose: 80`, sehingga tidak membuka host port publik. Domain publik diarahkan oleh reverse proxy Coolify ke container port `80`; service `db` tetap private di network internal compose.

1. Push root repository ini ke Git provider yang bisa diakses Coolify.
2. Di Coolify, buat resource baru dari Git repository tersebut.
3. Pilih build pack/deployment type `Docker Compose`.
4. Pastikan compose file yang dipakai adalah `docker-compose.yml` di root repository.
5. Assign domain pada service `app`, misalnya:

```text
https://ifuntanhub.dev
```

Karena container Apache listen di port `80`, domain tidak perlu diberi suffix port.

6. Set environment variable berikut di Coolify:

```env
MARIADB_ROOT_PASSWORD=isi-password-kuat
```

7. Deploy. Saat database volume masih kosong, MariaDB otomatis menjalankan:

```text
01/setup_database.sql
WebgisPovertyMapping/setup_database.sql
```

8. Setelah deploy, buka URL publik Coolify root. Landing page akan menampilkan link ke project kelas `01/` dan aplikasi final `WebgisPovertyMapping/`.

Catatan penting:

- Jangan tambahkan `ports:` di `docker-compose.yml` production. Jika port host dipublish, service dapat terbuka di luar kontrol reverse proxy.
- `docker-compose.local.yml` hanya untuk development lokal dan tidak perlu dipilih di Coolify.
- Jika ingin langsung membuka aplikasi final sebagai halaman utama domain, atur domain/path di reverse proxy ke `/WebgisPovertyMapping/` atau ubah landing page root sesuai kebutuhan.
- Jika database Docker perlu direset di Coolify, hapus persistent volume database terlebih dahulu. Tanpa reset volume, file SQL init tidak dijalankan ulang oleh MariaDB.

## Environment

- Runtime utama: Docker Compose dengan PHP 8.2 Apache dan MariaDB 11.4.
- Di Docker, aplikasi memakai `DB_HOST=db` dan `DB_PORT=3306`.
- Default port `3307` hanya relevan untuk mode XAMPP legacy.
- Leaflet, Chart.js, font, tile peta, dan geocoding memakai CDN/layanan eksternal sehingga demo membutuhkan koneksi internet.
- File root `DESIGN.md` adalah catatan desain lokal yang tidak terkait pengumpulan dan diabaikan oleh `.gitignore`.

## Urutan Demo Yang Disarankan

1. Buka `WebgisPovertyMapping/` sebagai aplikasi final.
2. Login dengan akun demo:

```text
Administrator: admin / password
Operator     : operator / password
Viewer       : viewer / password
```

3. Untuk akses publik tanpa login, buka langsung halaman peta aplikasi final.
4. Tunjukkan peta, dashboard, rumah ibadah, penduduk miskin, proximity/blank spot, kebutuhan bantuan, dan laporan.
5. Jika diminta bukti tugas kelas, buka `01/`.
