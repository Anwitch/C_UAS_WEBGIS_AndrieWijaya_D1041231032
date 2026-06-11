<?php
header('Content-Type: text/plain');

$file = __DIR__ . '/../api/penduduk/verifikasi.php';
$src = file_get_contents($file);

if ($src === false) {
    echo "FAIL: cannot read api/penduduk/verifikasi.php\n";
    exit(1);
}

if (strpos($src, "bind_param('sisi'") === false) {
    echo "FAIL: verifikasi bind_param must use 'sisi' for status, admin_id, catatan, id\n";
    exit(1);
}

echo "PASS: verifikasi bind_param order is correct\n";
