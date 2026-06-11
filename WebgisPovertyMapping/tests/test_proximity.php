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

// Test 1: haversine helper ada
check('haversine_distance() ada', function_exists('haversine_distance'));

// Test 2: jarak titik ke dirinya sendiri = 0
$d = haversine_distance(-0.0557, 109.3487, -0.0557, 109.3487);
check('haversine jarak sama = 0', $d < 0.001);

// Test 3: ~1 derajat latitude ≈ 111 km
$d2 = haversine_distance(0, 0, 1, 0);
check('haversine ~111km per derajat', $d2 > 110000 && $d2 < 112000);

// Test 4: tie-breaking — dua ibadah di titik identik → id lebih kecil menang
$conn->query("INSERT INTO rumah_ibadah (nama, jenis, lat, lng, radius_m)
              VALUES ('__test_A__','Masjid', -0.05, 109.34, 5000)");
$id_a = $conn->insert_id;
$conn->query("INSERT INTO rumah_ibadah (nama, jenis, lat, lng, radius_m)
              VALUES ('__test_B__','Masjid', -0.05, 109.34, 5000)");
$id_b = $conn->insert_id;

$p = calc_proximity($conn, -0.05, 109.34);
check('tie-breaking: ibadah id terkecil menang', (int)$p['ibadah_id'] === (int)$id_a);
check('tie-breaking: is_blank_spot=0', $p['is_blank_spot'] === 0);

// Cleanup
$conn->query("DELETE FROM rumah_ibadah WHERE nama IN ('__test_A__','__test_B__')");

echo "\n--- $pass passed, $fail failed ---\n";
