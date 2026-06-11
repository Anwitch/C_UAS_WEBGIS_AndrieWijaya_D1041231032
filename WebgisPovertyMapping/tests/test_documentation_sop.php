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

$sopPath = $root . '/docs/business-process-sop.md';
check('business SOP exists', file_exists($sopPath));

$sop = file_exists($sopPath) ? file_get_contents($sopPath) : '';
foreach (['Administrator', 'Operator', 'Publik', 'Donatur', 'Verifikasi', 'Proximity', 'Recalculate', 'Catatan'] as $needle) {
    check("SOP mentions {$needle}", strpos($sop, $needle) !== false);
}

$readme = file_get_contents($root . '/README.md');
check('README links business SOP', strpos($readme, 'docs/business-process-sop.md') !== false);

$design = file_get_contents($root . '/DESIGN.md');
check('DESIGN status is not ambiguous', strpos($design, 'Status dokumen') !== false && strpos($design, 'artifact') !== false);

echo "\n--- {$pass} passed, {$fail} failed ---\n";
exit($fail > 0 ? 1 : 0);
