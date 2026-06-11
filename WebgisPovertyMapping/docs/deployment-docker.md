# Deployment Docker

Panduan ini menjadi referensi utama untuk menjalankan WebGIS Poverty Mapping. XAMPP tidak lagi menjadi jalur deploy utama; gunakan XAMPP hanya sebagai fallback legacy.

## Runtime

- PHP 8.2 Apache dari `Dockerfile` root repository.
- MariaDB 11.4 dari `docker-compose.yml`.
- Database aplikasi final: `db_webgis`.
- Database project kelas `01/`: `db_webgis_01`.
- Aplikasi final tersedia di path `/WebgisPovertyMapping/`.

## Struktur Docker

File Docker berada di root repository `webgis/`:

```text
Dockerfile
docker-compose.yml
docker-compose.local.yml
.env.example
docker/apache-webgis.conf
```

Service:

- `app`: Apache + PHP, melayani root landing page, `01/`, dan `WebgisPovertyMapping/`.
- `db`: MariaDB, private untuk service `app`.

## Local Development

Jalankan dari root repository:

```bash
cp .env.example .env
docker compose -f docker-compose.yml -f docker-compose.local.yml up --build -d
```

Buka:

```text
http://localhost:8080/
http://localhost:8080/WebgisPovertyMapping/
```

Jika port `8080` sudah dipakai, tambahkan di `.env`:

```env
APP_PORT=8081
```

Lalu jalankan ulang compose lokal.

## Database Init

MariaDB menjalankan file berikut hanya saat volume database masih kosong:

```text
01/setup_database.sql
WebgisPovertyMapping/setup_database.sql
```

Untuk reset database lokal:

```bash
docker compose -f docker-compose.yml -f docker-compose.local.yml down -v
docker compose -f docker-compose.yml -f docker-compose.local.yml up --build -d
```

## Production Di Coolify

Gunakan `docker-compose.yml` saja.

1. Push root repository `webgis/` ke Git provider.
2. Buat resource baru di Coolify dari repository tersebut.
3. Pilih deployment type `Docker Compose`.
4. Set compose file ke `docker-compose.yml`.
5. Assign domain ke service `app`, misalnya `https://ifuntanhub.dev`.
6. Set environment variable:

```env
MARIADB_ROOT_PASSWORD=isi-password-kuat
```

`docker-compose.yml` production hanya memakai `expose: 80`. Jangan tambahkan `ports:` untuk production, karena routing publik harus lewat reverse proxy Coolify.

## Reverse Proxy

Coolify/reverse proxy menerima traffic publik dari domain dan meneruskannya ke container `app` port `80`.

Alur request:

```text
https://ifuntanhub.dev -> Coolify reverse proxy -> app:80 -> /WebgisPovertyMapping/
```

Header `X-Forwarded-Proto: https` dipakai oleh aplikasi untuk mengenali HTTPS saat menentukan secure session cookie.

## Troubleshooting

- App tidak bisa dibuka lokal: pastikan memakai `docker-compose.local.yml`, bukan hanya `docker-compose.yml`.
- DB gagal terhubung: cek env `MARIADB_ROOT_PASSWORD`, service `db`, dan healthcheck MariaDB.
- SQL init tidak berubah setelah edit schema: reset volume database; MariaDB hanya menjalankan init script saat volume kosong.
- Domain Coolify tidak masuk ke app: pastikan domain diassign ke service `app` dan container port `80`.
- Peta/chart/font gagal load: cek akses internet ke CDN dan tile provider eksternal.
