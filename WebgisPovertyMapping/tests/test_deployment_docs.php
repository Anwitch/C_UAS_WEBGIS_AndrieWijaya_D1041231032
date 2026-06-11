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

$docPath = $root . '/docs/deployment-xampp.md';
check('deployment XAMPP doc exists', file_exists($docPath));

if (file_exists($docPath)) {
    $doc = file_get_contents($docPath);
    foreach ([
        'Apache',
        'PHP',
        'MySQL',
        'MariaDB',
        'DB_PORT',
        '3307',
        'setup_database.sql',
        'admin',
        'Admin1234',
        'Leaflet',
        'Chart.js',
        'leaflet.heat',
        'Google Fonts',
        'OpenStreetMap',
        'Nominatim',
        'session timeout',
        'mirror lokal',
        'reverse proxy',
        'Secure',
        'Lucide',
    ] as $needle) {
        check("deployment doc mentions {$needle}", strpos($doc, $needle) !== false);
    }
}

$readme = file_get_contents($root . '/README.md');
$nav = file_get_contents($root . '/docs/codebase-navigation.md');
check('README links deployment doc', strpos($readme, 'docs/deployment-xampp.md') !== false);
check('navigation links deployment doc', strpos($nav, 'deployment-xampp.md') !== false);

echo "\n--- {$pass} passed, {$fail} failed ---\n";
exit($fail > 0 ? 1 : 0);
