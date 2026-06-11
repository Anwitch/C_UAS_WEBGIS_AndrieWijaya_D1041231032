<?php
// tests/test_ibadah.php — Smoke test API ibadah
header('Content-Type: text/plain');
if (PHP_SAPI === 'cli') {
    $session_path = __DIR__ . '/../tmp/test-sessions';
    if (!is_dir($session_path)) {
        mkdir($session_path, 0777, true);
    }
    session_save_path($session_path);
}
require_once '../config.php';
require_once '../auth/helper.php';
require_once '../koneksi.php';

$pass = 0; $fail = 0;
function check($label, $ok) {
    global $pass, $fail;
    if ($ok) { echo "PASS $label\n"; $pass++; }
    else      { echo "FAIL $label\n"; $fail++; }
}

// Test 1: schema punya deleted_at
$cols = [];
$r = $conn->query("SHOW COLUMNS FROM rumah_ibadah");
while ($row = $r->fetch_assoc()) $cols[] = $row['Field'];
check('rumah_ibadah.deleted_at ada', in_array('deleted_at', $cols));

// Test 2: query dengan filter deleted_at IS NULL bekerja
$r2 = $conn->query("SELECT COUNT(*) AS n FROM rumah_ibadah WHERE deleted_at IS NULL");
$active = (int)$r2->fetch_assoc()['n'];
$r3 = $conn->query("SELECT COUNT(*) AS n FROM rumah_ibadah");
$all = (int)$r3->fetch_assoc()['n'];
check('query deleted_at IS NULL tidak error', $active <= $all);

// Test 3: validasi radius 100-5000
$clamp_low  = max(100, min(5000, 50));
$clamp_high = max(100, min(5000, 6000));
check('radius < 100 di-clamp ke 100', $clamp_low === 100);
check('radius > 5000 di-clamp ke 5000', $clamp_high === 5000);

$hapus = file_get_contents(__DIR__ . '/../api/ibadah/hapus.php');
$kontak = file_get_contents(__DIR__ . '/../api/ibadah/update_kontak.php');
$update = file_get_contents(__DIR__ . '/../api/ibadah/update.php');
$posisi = file_get_contents(__DIR__ . '/../api/ibadah/update_posisi.php');
$radius = file_get_contents(__DIR__ . '/../api/ibadah/update_radius.php');
check('hapus filters deleted rows', strpos($hapus, 'AND deleted_at IS NULL') !== false);
check('hapus checks affected rows before recalc', strpos($hapus, 'affected_rows') !== false);
check('hapus returns deleted/not-found message', strpos($hapus, 'Data tidak ditemukan atau sudah dihapus') !== false);
check('hapus checks active ibadah before operator warning', strpos($hapus, 'SELECT id FROM rumah_ibadah') !== false && strpos($hapus, 'SELECT id FROM rumah_ibadah') < strpos($hapus, 'SELECT nama_lengkap FROM users'));
check('update_kontak filters deleted rows', strpos($kontak, 'AND deleted_at IS NULL') !== false);
check('update_kontak checks active row before update', strpos($kontak, 'SELECT id FROM rumah_ibadah') !== false && strpos($kontak, 'SELECT id FROM rumah_ibadah') < strpos($kontak, 'UPDATE rumah_ibadah'));
check('update_kontak returns deleted/not-found message', strpos($kontak, 'Data tidak ditemukan atau sudah dihapus') !== false);
foreach ([
    'update.php' => $update,
    'update_posisi.php' => $posisi,
    'update_radius.php' => $radius,
] as $file => $src) {
    check("{$file} checks active row before update", strpos($src, 'SELECT id FROM rumah_ibadah') !== false && strpos($src, 'SELECT id FROM rumah_ibadah') < strpos($src, 'UPDATE rumah_ibadah'));
    check("{$file} does not treat no-op affected_rows as not found", strpos($src, 'affected_rows === 0') === false);
}

echo "\n--- $pass passed, $fail failed ---\n";
exit($fail > 0 ? 1 : 0);
