<?php
header('Content-Type: text/plain');

$files = [
    'api/penduduk/update_status.php',
    'api/penduduk/riwayat.php',
    'api/kebutuhan/ambil.php',
    'api/kebutuhan/simpan.php',
    'api/kebutuhan/update_status.php',
];

foreach ($files as $relative) {
    $path = __DIR__ . '/../' . $relative;
    $src = file_get_contents($path);

    if ($src === false) {
        echo "FAIL: cannot read {$relative}\n";
        exit(1);
    }

    $checks = [
        'operator auth' => "require_auth('operator')",
        'admin bypass' => "has_role('administrator')",
        'operator ibadah lookup' => 'get_ibadah_id()',
        'forbidden status code' => 'http_response_code(403)',
    ];

    foreach ($checks as $label => $needle) {
        if (strpos($src, $needle) === false) {
            echo "FAIL: {$relative} missing {$label}\n";
            exit(1);
        }
    }
}

foreach ([
    'api/kebutuhan/simpan.php',
    'api/kebutuhan/update_status.php',
    'api/penduduk/update_status.php',
] as $relative) {
    $src = file_get_contents(__DIR__ . '/../' . $relative);
    if (strpos($src, 'status_verifikasi') === false) {
        echo "FAIL: {$relative} missing verification status guard\n";
        exit(1);
    }
    if (strpos($src, 'Terverifikasi') === false) {
        echo "FAIL: {$relative} missing Terverifikasi comparison\n";
        exit(1);
    }
    if (strpos($src, 'Data warga belum terverifikasi') === false) {
        echo "FAIL: {$relative} missing unverified warga error message\n";
        exit(1);
    }
    $guardPos = strpos($src, "!== 'Terverifikasi'");
    $writeNeedle = str_contains($relative, 'simpan.php')
        ? 'INSERT INTO kebutuhan'
        : (str_contains($relative, 'kebutuhan') ? 'UPDATE kebutuhan' : 'UPDATE penduduk_miskin');
    $writePos = strpos($src, $writeNeedle);
    if ($guardPos === false || $writePos === false || $guardPos > $writePos) {
        echo "FAIL: {$relative} verification guard must run before {$writeNeedle}\n";
        exit(1);
    }
}

echo "PASS: operator scoped endpoints include auth, scope checks, and 403 responses\n";
