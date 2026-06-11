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

$config = file_get_contents($root . '/config.php');
check('config defines map minimum latitude', strpos($config, 'MAP_MIN_LAT') !== false);
check('config defines map maximum latitude', strpos($config, 'MAP_MAX_LAT') !== false);
check('config defines map minimum longitude', strpos($config, 'MAP_MIN_LNG') !== false);
check('config defines map maximum longitude', strpos($config, 'MAP_MAX_LNG') !== false);

$helper = $root . '/includes/validation.php';
check('shared validation helper exists', file_exists($helper));

if (file_exists($helper)) {
    require_once $root . '/config.php';
    require_once $helper;

    check('validate_lat_lng function exists', function_exists('validate_lat_lng'));
    if (function_exists('validate_lat_lng')) {
        check('valid Pontianak coordinate accepted', validate_lat_lng('-0.0557', '109.3487')['ok'] === true);
        check('blank coordinate rejected', validate_lat_lng('', '109.3487')['ok'] === false);
        check('non numeric coordinate rejected', validate_lat_lng('abc', '109.3487')['ok'] === false);
        check('zero-zero coordinate rejected by study bounds', validate_lat_lng('0', '0')['ok'] === false);
        check('latitude outside earth range rejected', validate_lat_lng('91', '109.3487')['ok'] === false);
        check('longitude outside earth range rejected', validate_lat_lng('-0.0557', '181')['ok'] === false);
    }
}

$mutationFiles = [
    'api/penduduk/simpan.php',
    'api/penduduk/update_posisi.php',
    'api/ibadah/simpan.php',
    'api/ibadah/update.php',
    'api/ibadah/update_posisi.php',
    'api/import/csv.php',
];

foreach ($mutationFiles as $relative) {
    $src = file_get_contents($root . '/' . $relative);
    check("{$relative} loads validation helper", strpos($src, 'includes/validation.php') !== false);
    check("{$relative} calls validate_lat_lng", strpos($src, 'validate_lat_lng(') !== false);
}

$ibadahUpdate = file_get_contents($root . '/api/ibadah/update.php');
check(
    'ibadah update validates submitted coordinates directly',
    strpos($ibadahUpdate, "validate_lat_lng(\$_POST['lat'] ?? null, \$_POST['lng'] ?? null)") !== false
);
check(
    'ibadah update does not substitute existing coordinates for blank input',
    strpos($ibadahUpdate, 'SELECT lat, lng FROM rumah_ibadah') === false
);

echo "\n--- {$pass} passed, {$fail} failed ---\n";
exit($fail > 0 ? 1 : 0);
