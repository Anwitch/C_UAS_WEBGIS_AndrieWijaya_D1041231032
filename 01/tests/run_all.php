<?php

$tests = [
    __DIR__ . DIRECTORY_SEPARATOR . 'test_layer_panes.php',
    __DIR__ . DIRECTORY_SEPARATOR . 'test_public_hardening_static.php',
];

foreach ($tests as $test) {
    passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($test), $exitCode);
    if ($exitCode !== 0) {
        exit($exitCode);
    }
}

echo "PASS: all Project 01 tests passed\n";
