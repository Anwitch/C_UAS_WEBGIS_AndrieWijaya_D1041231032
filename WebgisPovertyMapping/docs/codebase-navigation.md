# Codebase Navigation — WebGIS Poverty Mapping

> Quick reference for navigating the project. Updated: 2026-06-03.

---

## Directory Tree at a Glance

```
WebgisPovertyMapping/
├── index.php                   ← Main app shell (auth-aware)
├── index.html                  ← Redirect/legacy entry from older prototype
├── dashboard.php               ← Authenticated admin/operator shell
├── papan-kebutuhan.php         ← Public needs board page
├── config.php                  ← All app constants (DB, session, map center)
├── koneksi.php                 ← DB connection + Haversine + calc_proximity()
├── setup_database.sql          ← Full schema (idempotent, run to init DB)
│
├── auth/
│   ├── helper.php              ← RBAC core: is_logged_in(), require_auth(), has_role()
│   ├── login.php               ← Login page
│   └── change_password.php     ← Forced password-change page
│
├── pages/
│   ├── map.php                 ← Dashboard wrapper embedding index.php?embedded=1
│   ├── penduduk.php            ← Operator/admin penduduk management
│   ├── kebutuhan.php           ← Operator/admin kebutuhan workflow
│   ├── status-bantuan.php      ← Operator/admin aid status workflow
│   ├── ibadah.php              ← Admin rumah ibadah management
│   ├── users.php               ← Admin user management
│   ├── import.php              ← Admin CSV import
│   ├── laporan.php             ← Admin export/report page
│   └── analisis.php            ← Admin analytics/recalculation page
│
├── modules/                    ← Frontend JS modules (loaded by index.php)
│   ├── ibadah.js               ← Rumah Ibadah layer: markers, circles, CRUD
│   ├── penduduk.js             ← Penduduk layer: proximity coloring, CRUD, filters
│   ├── stats.js                ← Dashboard modal: cards + chart + rekap table
│   ├── heatmap.js              ← Heatmap overlay (leaflet.heat, weighted by jiwa)
│   └── kebutuhan.js            ← Kebutuhan bantuan popup/actions
│
├── css/
│   └── admin.css               ← Shared dashboard/page styling
│
├── includes/
│   ├── page-start.php          ← Authenticated page shell start
│   └── page-end.php            ← Authenticated page shell end
│
├── api/
│   ├── auth/                   ← login.php · logout.php · change_password.php
│   ├── users/                  ← ambil · simpan · update · reset_password · toggle_active
│   ├── ibadah/                 ← ambil · simpan · update · hapus · update_posisi · update_kontak · update_radius · recalculate
│   ├── penduduk/               ← ambil · simpan · verifikasi · hapus · nonaktifkan · update_posisi · update_status · riwayat · arsip
│   ├── stats/                  ← ambil.php (aggregations)
│   ├── kebutuhan/              ← ambil · simpan · update_status
│   ├── papan/                  ← public board + donor contact endpoints
│   ├── import/                 ← csv.php · template.php
│   └── export/                 ← csv.php · pdf.php
│
├── tests/
│   ├── test_db.php                         ← Schema smoke test
│   ├── test_auth_helper.php                ← Auth helper unit tests
│   ├── test_ibadah.php                     ← Ibadah column + radius tests
│   ├── test_penduduk.php                   ← Penduduk columns + NIK index tests
│   ├── test_kebutuhan.php                  ← Kebutuhan schema/query smoke tests
│   ├── test_proximity.php                  ← Haversine + tie-breaking unit tests
│   ├── test_verifikasi_binding.php         ← Verification endpoint bind order guard
│   ├── test_update_posisi_scope.php        ← Penduduk move scope guard
│   ├── test_operator_scope_endpoints.php   ← Operator endpoint scope checks
│   ├── test_public_verification_filters.php← Public verification filters
│   ├── test_frontend_module_loader.php     ← Module loader cache-busting guard
│   ├── test_frontend_reliability_guards.php ← Loader/heatmap/recalc guard checks
│   ├── test_no_legacy_poi.php              ← Legacy POI removal guard
│   ├── test_viewer_public_filters.php      ← Viewer public-data leakage guard
│   ├── test_operator_scope_strict.php      ← Strict operator ibadah scope guard
│   ├── test_penduduk_binding_types.php     ← Penduduk/import bind type guard
│   ├── test_csrf_enforcement.php           ← CSRF helper/endpoint/frontend wiring guard
│   ├── test_session_cookie_security.php    ← Session cookie security guard
│   ├── test_operator_ibadah_highlight.php  ← Operator assigned-ibadah highlight guard
│   └── test_viewer_account_ui_hidden.php   ← Viewer account creation UI guard
│
├── Tugas/                      ← Assignment docs (SRS, use-case diagram)
└── docs/
    ├── codebase-navigation.md  ← This file
    └── superpowers/
        ├── specs/              ← PRD and design specs
        └── plans/              ← Phase implementation plans
```

---

## Essential Files — Start Here

| File | Why You Need It |
|------|-----------------|
| [config.php](../config.php) | All configurable constants — DB credentials, map center (CENTER_LAT/LNG), SESSION_TIMEOUT, MAX_LOGIN_ATTEMPTS |
| [koneksi.php](../koneksi.php) | DB connection + the two core functions: `haversine_distance()` and `calc_proximity()` — the heart of the proximity engine |
| [setup_database.sql](../setup_database.sql) | Authoritative schema — run this to initialize or reset the DB |
| [auth/helper.php](../auth/helper.php) | RBAC foundation used by every endpoint: `require_auth()`, `has_role()`, `is_logged_in()`, session timeout logic |
| [index.php](../index.php) | Main SPA shell — role detection, toolbar rendering, all modals, map init, utility functions (`showToast`, `showDeleteConfirm`, etc.) |

## Related Documentation

| File | Purpose |
|------|---------|
| [codebase-audit-report.md](codebase-audit-report.md) | End-to-end business process, backend/frontend wiring, data lifecycle, current logic concerns, fix priorities, and development suggestions. |
| [business-process-sop.md](business-process-sop.md) | SOP proses bisnis aktif untuk administrator, operator, publik, donatur, verifikasi, proximity, dan catatan audit. |
| [deployment-docker.md](deployment-docker.md) | Docker/Coolify deployment, reverse proxy routing, database container, and troubleshooting. |
| [deployment-xampp.md](deployment-xampp.md) | Legacy XAMPP setup if the app must run without Docker. |
| [superpowers/README.md](superpowers/README.md) | Index PRD, implementation plans, and the latest superpowers audit refresh. |
| [superpowers/plans/2026-06-03-codebase-audit-refresh.md](superpowers/plans/2026-06-03-codebase-audit-refresh.md) | Latest codebase audit snapshot after hardening. |
| [../README.md](../README.md) | Setup, stack, default account, roles, features, and test commands. |
| [../setup_database.sql](../setup_database.sql) | Authoritative database schema and default admin seed. |

---

## Database Tables

### Core v1.0 Tables

**`rumah_ibadah`** — Worship places with service radius
| Key Columns | Notes |
|-------------|-------|
| `id`, `nama`, `jenis` (ENUM 6 types), `lat/lng` | Core identity |
| `radius_m` INT DEFAULT 500 | Validated 100–5000 |
| `deleted_at` TIMESTAMP NULL | Soft delete — `WHERE deleted_at IS NULL` for all queries |

**`penduduk_miskin`** — Poor residents (core data)
| Key Columns | Notes |
|-------------|-------|
| `id`, `nama_kk`, `nik` VARCHAR(16) UNIQUE | NIK nullable but unique |
| `lat/lng`, `ibadah_id` FK, `jarak_m`, `is_blank_spot` | Proximity results — computed by `calc_proximity()` |
| `kategori` ENUM 3 levels, `status_bantuan` ENUM 3 states | Classification + aid tracking |
| `status_verifikasi` ENUM('Pending','Terverifikasi','Ditolak') | Public visibility gate |
| `is_active` TINYINT(1) | 0 = deceased/relocated (archived) |
| `deleted_at` TIMESTAMP NULL | Admin error correction (soft delete) |

> **`is_active=0` vs `deleted_at IS NOT NULL`:** Both hide the record from all active queries. `is_active=0` = real person, archived. `deleted_at` = data entry error, logically cancelled.

**`users`** — System accounts
| Key Columns | Notes |
|-------------|-------|
| `role` ENUM('administrator','operator','viewer') | RBAC basis |
| `ibadah_id` FK | Operator's assigned worship place |
| `must_change_password` TINYINT(1) | Forces redirect to change_password.php on login |
| `login_attempts`, `locked_until` | Brute-force protection state |

**`riwayat_bantuan`** — Aid status audit log (append-only)
| Key Columns | Notes |
|-------------|-------|
| `penduduk_id`, `operator_id` FK | Who changed what |
| `status_lama`, `status_baru`, `catatan` | Change record |
| No `deleted_at` | Intentionally — immutable audit trail |

**`kebutuhan`** — Assistance needs per resident
| Key Columns | Notes |
|-------------|-------|
| `penduduk_id`, `kategori`, `deskripsi`, `status` | Need details and fulfillment state |
| `created_by`, `updated_by` | Operator/admin audit references |

**`riwayat_kebutuhan`** — Kebutuhan status audit log (append-only)
| Key Columns | Notes |
|-------------|-------|
| `kebutuhan_id`, `operator_id` FK | Which need changed and who changed it |
| `status_lama`, `status_baru`, `catatan` | Status transition record |
| `created_at` | Append-only timestamp for audit chronology |

**`kontak_donatur`** — Public donor contact submissions
| Key Columns | Notes |
|-------------|-------|
| `nama`, `kontak`, `kategori_minat`, `pesan` | Public contact form data |
| `is_read` | Admin dashboard notification state |

## API Endpoint Map

### Public (no auth required)
| Endpoint | Method | Purpose |
|----------|--------|---------|
| `api/ibadah/ambil.php` | GET | All active ibadah + KK/jiwa counts |
| `api/penduduk/ambil.php` | GET | Active penduduk — PII masked for non-auth |
| `api/stats/ambil.php` | GET | Aggregate stats; rekap_ibadah only for admin |
| `api/papan/ambil.php` | GET | Public verified kebutuhan board aggregation |
| `api/papan/kontak_donatur.php` | POST | Public donor contact submission |

### Operator+ (operator or admin)
| Endpoint | Method | Purpose |
|----------|--------|---------|
| `api/penduduk/simpan.php` | POST | Add penduduk (auto-calls calc_proximity) |
| `api/penduduk/update_posisi.php` | POST | Move marker (recalcs proximity) |
| `api/penduduk/update_status.php` | POST | Change aid status + write audit log |
| `api/penduduk/riwayat.php` | GET | Aid history for a penduduk (scoped to own ibadah for operators) |
| `api/kebutuhan/ambil.php` | GET | List kebutuhan for a scoped penduduk |
| `api/kebutuhan/simpan.php` | POST | Add kebutuhan for a scoped penduduk |
| `api/kebutuhan/update_status.php` | POST | Update kebutuhan status + write audit log |

### Admin only
| Endpoint | Method | Purpose |
|----------|--------|---------|
| `api/ibadah/simpan.php` | POST | Create ibadah |
| `api/ibadah/update.php` | POST | Update ibadah master data from admin page |
| `api/ibadah/hapus.php` | POST | Soft-delete ibadah (warns if operators linked) |
| `api/ibadah/update_posisi.php` | POST | Move ibadah → triggers full recalc |
| `api/ibadah/update_radius.php` | POST | Change radius → triggers full recalc |
| `api/ibadah/update_kontak.php` | POST | Update contact only |
| `api/ibadah/recalculate.php` | POST | Bulk recalculate all proximity |
| `api/penduduk/verifikasi.php` | POST | Approve/reject pending penduduk records |
| `api/penduduk/hapus.php` | POST | Soft-delete penduduk (data entry error) |
| `api/penduduk/nonaktifkan.php` | POST | Set is_active=0 (deceased/relocated) |
| `api/penduduk/arsip.php` | GET | List inactive/deleted warga for audit (up to 200 rows) |
| `api/papan/donatur.php` | GET/POST | GET lists donor contacts; POST marks unread entries as read with CSRF |
| `api/users/ambil.php` | GET | List all accounts |
| `api/users/simpan.php` | POST | Create account (must_change_password=1 forced) |
| `api/users/update.php` | POST | Edit nama/role/ibadah_id |
| `api/users/reset_password.php` | POST | Generate temp password + must_change=1 |
| `api/users/toggle_active.php` | POST | Enable/disable account (cannot self-deactivate) |
| `api/import/csv.php` | POST | CSV import (mode=preview or mode=import) |
| `api/import/template.php` | GET | Download CSV template |
| `api/export/csv.php` | GET | Export warga binaan as CSV (no NIK) |
| `api/export/pdf.php` | GET | Print-ready HTML report (no NIK) |

---

## Frontend Module Map

### `modules/ibadah.js`
- **Entry:** `window.initIbadah()` — called by index.php on load
- **Shared state:** `window._ibadahList[]` — read by penduduk.js for client-side proximity preview
- **Key functions:**
  - `addToMap(data)` — creates draggable marker + radius circle
  - `buildInfoPopup(data)` — admin: editable kontak/radius/export links; viewer: read-only
  - `_ibadahHapus(id)` — two-step: operator-warning check → confirm → soft-delete
  - `_recalcAll()` — calls recalculate.php, disables all CRUD buttons during operation
  - `window._ibadahToggleLayer(bool)` / `_setBufferVisible(bool)` — layer controls

### `modules/penduduk.js`
- **Entry:** `window.initPenduduk()` — called by index.php on load
- **Shared state:** `window._pendudukList[]` — read by stats.js and heatmap.js
- **Key functions:**
  - `buildInfoPopup(data, ibadah)` — proximity section + NIK (auth only) + status buttons (operator) + riwayat
  - `findNearestIbadah(lat, lng)` — client-side Haversine mirror of PHP `calc_proximity()`
  - `applyFilters()` — AND-logic filter across 4 dimensions + search
  - `window._pendudukRecalcAll()` — client-side repaint after ibadah change (no server call)
  - `_updateBlankSpotCounter()` / `_filterBlankSpot()` — blank spot counter + click-to-filter

### `modules/stats.js`
- **Entry:** `window._showDashboard()` — opens modal
- `window._statsUpdate()` — re-fetches and re-renders (only if modal visible)
- Destroys and recreates Chart.js instance on each update to avoid memory leaks

### `modules/heatmap.js`
- **Entry:** `window._toggleHeatmap(bool)` — creates/destroys heatmap layer
- Intensity = `0.2 + 0.8 * (jumlah_jiwa / maxJiwa)` — proportional to household size
- `window._heatmapSetKategori(k)` — filter heatmap by category

### `modules/kebutuhan.js`
- **Entry:** loaded for authenticated users through `window.MAP_MODULES`
- Builds kebutuhan controls inside penduduk popups
- Calls `api/kebutuhan/ambil.php`, `api/kebutuhan/simpan.php`, and `api/kebutuhan/update_status.php`

---

## Core Algorithm: Proximity Engine

**Location:** [koneksi.php](../koneksi.php) — `calc_proximity($conn, $lat, $lng)`

**Logic:**
1. Query all `rumah_ibadah WHERE deleted_at IS NULL ORDER BY id ASC`
2. For each, compute Haversine distance
3. Track two candidates: `best_in_radius` (nearest within radius) and `best_overall` (nearest regardless)
4. Winner = `best_in_radius` if exists; else `best_overall` (blank spot)
5. Tie-breaking: `ORDER BY id ASC` + strict `<` comparison → lowest id wins on equal distance

**Triggers:**
- `api/penduduk/simpan.php` — on each new penduduk
- `api/penduduk/update_posisi.php` — when penduduk moved
- `api/ibadah/update_posisi.php` — after ibadah position change (loops ALL penduduk)
- `api/ibadah/update_radius.php` — after radius change (loops ALL penduduk)
- `api/ibadah/hapus.php` — after soft-delete (loops ALL penduduk)
- `api/ibadah/recalculate.php` — manual bulk trigger (admin "Hitung Ulang Semua")
- `api/import/csv.php` — per-row during import

**Client-side mirror:** `modules/penduduk.js` — `findNearestIbadah()` for instant visual feedback during drag/radius resize (no server call).

---

## RBAC Quick Reference

```
viewer (0)       → public/fallback read-only map, aggregated stats, PII masked
operator (1)     → + add/move penduduk, update aid status (own ibadah only)
administrator (2) → + everything: CRUD ibadah, manage users, import/export, recalculate
```

Operators are created by administrators in `pages/users.php` and must be linked to one `rumah_ibadah`.
The shared admin shell renders navigation by role: administrators see dashboard/master/import/export/user management, while operators see map, penduduk, kebutuhan, and status bantuan only.
Unauthenticated visitors are treated as `viewer`, so the admin user-management UI does not offer `viewer` for new accounts. The backend role remains for fallback RBAC and existing read-only accounts.

**Session flow:**
1. Unauthenticated → `index.php` renders as `viewer` (no redirect)
2. Login → `api/auth/login.php` → session set → if `must_change_password=1`, redirect to `auth/change_password.php`
3. Every page/API: `auth/helper.php:is_logged_in()` validates session + renews `last_activity`
4. Idle 2hr → session expired → treated as viewer on next request

---

## Legacy Status

The obsolete road/polyline, parcel/polygon, and business-location prototype features have been removed from the active app.

Active core:
- `rumah_ibadah`
- `penduduk_miskin`
- `users`
- `kebutuhan`
- `riwayat_bantuan`
- `riwayat_kebutuhan`
- `kontak_donatur`

---

## Running Tests

Tests are intended to run from CLI/local PHP. Browser access to `/tests/` is blocked by `.htaccess` for deployment safety.

Run selected static/smoke tests from the project root:

```powershell
& 'C:\Program Files\Xampp\php\php.exe' tests\run_all.php
```

Some database smoke tests load `../config.php` or `../koneksi.php` and should be run from the `tests` directory after importing `setup_database.sql`.

---

## Configuration Quick-Start

Edit [config.php](../config.php) to set:
```php
define('CENTER_LAT', -0.0557);   // Map center latitude
define('CENTER_LNG', 109.3487);  // Map center longitude  
define('ZOOM_LEVEL', 15);        // Initial zoom
define('DB_HOST', 'localhost');
define('DB_PORT', 3307);         // Local configured MySQL port
define('DB_NAME', 'db_webgis');
```

Default admin credentials (must change on first login): `admin` / `Admin1234`
