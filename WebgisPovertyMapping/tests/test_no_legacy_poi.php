<?php
header('Content-Type: text/plain');

$root = realpath(__DIR__ . '/..');
$pass = 0;
$fail = 0;

function check($label, $ok) {
    global $pass, $fail;
    if ($ok) {
        echo "PASS: {$label}\n";
        $pass++;
    } else {
        echo "FAIL: {$label}\n";
        $fail++;
    }
}

$removed_paths = [
    'modules/point.js',
    'api/point',
    'ambil_data.php',
    'simpan.php',
    'hapus.php',
    'update_posisi.php',
];

foreach ($removed_paths as $path) {
    check("{$path} removed", !file_exists($root . '/' . $path));
}

$scan_files = [
    'index.php',
    'modules/penduduk.js',
    'includes/page-start.php',
    'setup_database.sql',
    'README.md',
    'docs/codebase-navigation.md',
];

$forbidden = [
    'lokasi_usaha',
    'modules/point.js',
    'api/point',
    'initPoint',
    '_point',
    'ambil_data.php',
    'nama_tempat',
    'searchPoi',
    'Data POI',
    'Pengaturan Sistem',
    'Aktivitas & Audit Log',
    'POI/business',
];

foreach ($scan_files as $file) {
    $content = file_get_contents($root . '/' . $file);
    $content = str_replace('test_no_legacy_poi.php', 'test_no_legacy_regression.php', $content);
    foreach ($forbidden as $needle) {
        check("{$file} has no {$needle}", strpos($content, $needle) === false);
    }
}

$nav = file_get_contents($root . '/includes/page-start.php');
check('sidebar has no visible placeholder href menu item', strpos($nav, "_sb_item('#'") === false);

echo "\n--- {$pass} passed, {$fail} failed ---\n";
exit($fail > 0 ? 1 : 0);
