<?php
header('Content-Type: text/plain');

$file = __DIR__ . '/../index.php';
$src = file_get_contents($file);

if ($src === false) {
    echo "FAIL: cannot read index.php\n";
    exit(1);
}

$must_not_contain = [
    'const TOTAL_MODULES = 4',
    'loaded === TOTAL_MODULES',
    "filemtime(__FILE__)",
];

foreach ($must_not_contain as $needle) {
    if (strpos($src, $needle) !== false) {
        echo "FAIL: index.php still contains stale loader marker {$needle}\n";
        exit(1);
    }
}

$must_contain = [
    '$map_modules',
    'modules/kebutuhan.js',
    'window.MAP_MODULES',
    'window.MAP_MODULES.length',
    "filemtime(__DIR__ . '/' . \$module)",
];

foreach ($must_contain as $needle) {
    if (strpos($src, $needle) === false) {
        echo "FAIL: index.php missing module loader marker {$needle}\n";
        exit(1);
    }
}

echo "PASS: frontend module loader uses dynamic module count and per-file cache busting\n";
