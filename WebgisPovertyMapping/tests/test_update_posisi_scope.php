<?php
header('Content-Type: text/plain');

$file = __DIR__ . '/../api/penduduk/update_posisi.php';
$src = file_get_contents($file);

if ($src === false) {
    echo "FAIL: cannot read api/penduduk/update_posisi.php\n";
    exit(1);
}

$checks = [
    "non-admin branch" => "has_role('administrator')",
    "operator ibadah lookup" => "get_ibadah_id()",
    "scope query" => "SELECT id FROM penduduk_miskin",
    "ibadah scope condition" => "ibadah_id = ?",
    "forbidden status" => "http_response_code(403)",
];

foreach ($checks as $label => $needle) {
    if (strpos($src, $needle) === false) {
        echo "FAIL: missing {$label} scope guard in update_posisi.php\n";
        exit(1);
    }
}

echo "PASS: update_posisi operator scope guard is present\n";
