<?php
header('Content-Type: text/plain');
require_once '../config.php';
require_once '../koneksi.php';

$pass = 0; $fail = 0;
function check($label, $ok) {
    global $pass, $fail;
    if ($ok) { echo "✅ $label\n"; $pass++; }
    else      { echo "❌ $label\n"; $fail++; }
}

// ── Tabel kebutuhan ────────────────────────────────────────────────────────
$cols = [];
$r = $conn->query("SHOW COLUMNS FROM kebutuhan");
while ($row = $r->fetch_assoc()) $cols[] = $row['Field'];
check('kebutuhan.id exists',          in_array('id',          $cols));
check('kebutuhan.penduduk_id exists', in_array('penduduk_id', $cols));
check('kebutuhan.kategori exists',    in_array('kategori',    $cols));
check('kebutuhan.deskripsi exists',   in_array('deskripsi',   $cols));
check('kebutuhan.status exists',      in_array('status',      $cols));
check('kebutuhan.created_by exists',  in_array('created_by',  $cols));

// ── Tabel riwayat_kebutuhan ────────────────────────────────────────────────
$cols2 = [];
$r2 = $conn->query("SHOW COLUMNS FROM riwayat_kebutuhan");
while ($row = $r2->fetch_assoc()) $cols2[] = $row['Field'];
check('riwayat_kebutuhan.kebutuhan_id exists', in_array('kebutuhan_id', $cols2));
check('riwayat_kebutuhan.operator_id exists',  in_array('operator_id',  $cols2));
check('riwayat_kebutuhan.status_baru exists',  in_array('status_baru',  $cols2));

// ── Tabel kontak_donatur ───────────────────────────────────────────────────
$cols3 = [];
$r3 = $conn->query("SHOW COLUMNS FROM kontak_donatur");
while ($row = $r3->fetch_assoc()) $cols3[] = $row['Field'];
check('kontak_donatur.nama exists',    in_array('nama',    $cols3));
check('kontak_donatur.kontak exists',  in_array('kontak',  $cols3));
check('kontak_donatur.is_read exists', in_array('is_read', $cols3));

// ── Index kebutuhan ────────────────────────────────────────────────────────
$idx = $conn->query("SHOW INDEX FROM kebutuhan");
$idx_names = [];
while ($row = $idx->fetch_assoc()) $idx_names[] = $row['Key_name'];
check('kebutuhan penduduk index exists', in_array('idx_k_penduduk', $idx_names) || in_array('idx_penduduk', $idx_names));
check('kebutuhan status index exists',   in_array('idx_k_status',   $idx_names) || in_array('idx_status',   $idx_names));

// ── Default status = Belum Terpenuhi ─────────────────────────────────────
$default_status = $conn->query(
    "SELECT COLUMN_DEFAULT FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='kebutuhan' AND COLUMN_NAME='status'"
)->fetch_assoc()['COLUMN_DEFAULT'];
$default_status = trim((string)$default_status, "'");
check("kebutuhan.status default = 'Belum Terpenuhi'", $default_status === 'Belum Terpenuhi');

// ── kontak_donatur.is_read default = 0 ───────────────────────────────────
$default_read = $conn->query(
    "SELECT COLUMN_DEFAULT FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='kontak_donatur' AND COLUMN_NAME='is_read'"
)->fetch_assoc()['COLUMN_DEFAULT'];
check("kontak_donatur.is_read default = 0", $default_read === '0');

// ── ambil.php LEFT JOIN returns kebutuhan_open column ─────────────────────
$test_sql = "
    SELECT pm.id, COALESCE(kstat.kebutuhan_open, 0) AS kebutuhan_open
    FROM penduduk_miskin pm
    LEFT JOIN (
        SELECT penduduk_id, SUM(CASE WHEN status='Belum Terpenuhi' THEN 1 ELSE 0 END) AS kebutuhan_open
        FROM kebutuhan GROUP BY penduduk_id
    ) kstat ON kstat.penduduk_id = pm.id
    WHERE pm.is_active = 1 AND pm.deleted_at IS NULL
    LIMIT 1
";
$q = $conn->query($test_sql);
check('kebutuhan LEFT JOIN query executes without error', $q !== false);

echo "\n--- $pass passed, $fail failed ---\n";
