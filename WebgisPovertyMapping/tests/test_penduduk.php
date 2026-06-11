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

// Test 1: kolom baru ada
$cols = [];
$r = $conn->query("SHOW COLUMNS FROM penduduk_miskin");
while ($row = $r->fetch_assoc()) $cols[] = $row['Field'];
check('penduduk_miskin.catatan exists',        in_array('catatan', $cols));
check('penduduk_miskin.status_bantuan exists', in_array('status_bantuan', $cols));
check('penduduk_miskin.is_active exists',      in_array('is_active', $cols));
check('penduduk_miskin.deleted_at exists',     in_array('deleted_at', $cols));

// Test 2: NIK UNIQUE constraint ada
$r = $conn->query("SHOW INDEX FROM penduduk_miskin WHERE Key_name = 'uq_nik'");
check('NIK unique index ada', $r->num_rows > 0);

// Test 3: ambil query hanya mengembalikan is_active=1 dan deleted_at IS NULL
$conn->query(
    "INSERT IGNORE INTO penduduk_miskin (nama_kk, jumlah_jiwa, kategori, lat, lng, is_active)
     VALUES ('__test_inactive__', 1, 'Miskin', 0, 0, 0)"
);
$test_id = $conn->insert_id;
$n_active = (int)$conn->query(
    "SELECT COUNT(*) AS n FROM penduduk_miskin WHERE is_active=1 AND deleted_at IS NULL"
)->fetch_assoc()['n'];
$n_all = (int)$conn->query(
    "SELECT COUNT(*) AS n FROM penduduk_miskin"
)->fetch_assoc()['n'];
check('inactive warga tidak tampil di query aktif', $n_all > $n_active);

// Cleanup
if ($test_id) $conn->query("DELETE FROM penduduk_miskin WHERE id = $test_id");

echo "\n--- $pass passed, $fail failed ---\n";
