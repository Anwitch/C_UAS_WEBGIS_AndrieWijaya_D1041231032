<?php
header('Content-Type: text/plain');

$files = [
    'api/stats/ambil.php' => [
        'pm.status_verifikasi',
        '$public_verif_sql',
        '$public_verif_pm_sql',
    ],
    'api/papan/ambil.php' => [
        "pm.status_verifikasi = 'Terverifikasi'",
    ],
];

foreach ($files as $relative => $needles) {
    $path = __DIR__ . '/../' . $relative;
    $src = file_get_contents($path);

    if ($src === false) {
        echo "FAIL: cannot read {$relative}\n";
        exit(1);
    }

    foreach ($needles as $needle) {
        if (strpos($src, $needle) === false) {
            echo "FAIL: {$relative} missing public verification filter marker {$needle}\n";
            exit(1);
        }
    }
}

$board = file_get_contents(__DIR__ . '/../papan-kebutuhan.php');
if (strpos($board, 'data-kategori') === false) {
    echo "FAIL: papan-kebutuhan.php missing data-kategori handler\n";
    exit(1);
}
if (strpos($board, 'addEventListener') === false) {
    echo "FAIL: papan-kebutuhan.php missing delegated event listener\n";
    exit(1);
}
if (strpos($board, "openForm('\${esc(row.kategori)}')") !== false) {
    echo "FAIL: papan-kebutuhan.php still uses inline JS string category handler\n";
    exit(1);
}

echo "PASS: public stats and board endpoints filter unverified penduduk\n";
