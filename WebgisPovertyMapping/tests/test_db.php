<?php
// tests/test_db.php — Verifikasi schema database PRD v1.0
// Jalankan via browser: http://localhost/webgis/WebgisPovertyMapping/tests/test_db.php
require_once '../koneksi.php';

$pass = 0; $fail = 0;
function check($label, $ok) {
    global $pass, $fail;
    if ($ok) { echo "✅ $label\n"; $pass++; }
    else      { echo "❌ $label\n"; $fail++; }
}

// --- rumah_ibadah ---
$cols = [];
$r = $conn->query("SHOW COLUMNS FROM rumah_ibadah");
while ($row = $r->fetch_assoc()) $cols[] = $row['Field'];

check('rumah_ibadah.updated_at exists', in_array('updated_at', $cols));
check('rumah_ibadah.deleted_at exists', in_array('deleted_at', $cols));

// --- penduduk_miskin ---
$cols = [];
$r = $conn->query("SHOW COLUMNS FROM penduduk_miskin");
while ($row = $r->fetch_assoc()) $cols[] = $row['Field'];

check('penduduk_miskin.catatan exists',        in_array('catatan',        $cols));
check('penduduk_miskin.status_bantuan exists', in_array('status_bantuan', $cols));
check('penduduk_miskin.is_active exists',      in_array('is_active',      $cols));
check('penduduk_miskin.updated_at exists',     in_array('updated_at',     $cols));
check('penduduk_miskin.deleted_at exists',     in_array('deleted_at',     $cols));

// --- tabel users ---
$t = $conn->query("SHOW TABLES LIKE 'users'");
check('table users exists', $t->num_rows > 0);

// --- tabel riwayat_bantuan ---
$t = $conn->query("SHOW TABLES LIKE 'riwayat_bantuan'");
check('table riwayat_bantuan exists', $t->num_rows > 0);

// --- users columns ---
if ($conn->query("SHOW TABLES LIKE 'users'")->num_rows > 0) {
    $cols = [];
    $r = $conn->query("SHOW COLUMNS FROM users");
    while ($row = $r->fetch_assoc()) $cols[] = $row['Field'];
    check('users.username exists',             in_array('username',             $cols));
    check('users.role exists',                 in_array('role',                 $cols));
    check('users.must_change_password exists', in_array('must_change_password', $cols));
    check('users.login_attempts exists',       in_array('login_attempts',       $cols));
    check('users.locked_until exists',         in_array('locked_until',         $cols));
    check('users.ibadah_id exists',            in_array('ibadah_id',            $cols));
}

echo "\n--- $pass passed, $fail failed ---\n";
