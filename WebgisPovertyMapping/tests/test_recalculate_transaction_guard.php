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

foreach ([
    'api/ibadah/update.php',
    'api/ibadah/update_posisi.php',
    'api/ibadah/update_radius.php',
    'api/ibadah/hapus.php',
    'api/ibadah/recalculate.php',
] as $file) {
    $content = file_get_contents($root . '/' . $file);
    check("{$file} starts transaction", strpos($content, 'begin_transaction') !== false);
    check("{$file} commits transaction", strpos($content, 'commit()') !== false);
    check("{$file} rolls back transaction", strpos($content, 'rollback()') !== false);
}

echo "\n--- {$pass} passed, {$fail} failed ---\n";
exit($fail > 0 ? 1 : 0);
