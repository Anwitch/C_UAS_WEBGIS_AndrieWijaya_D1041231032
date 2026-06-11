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

$index = file_get_contents($root . '/index.php');
$ibadah = file_get_contents($root . '/modules/ibadah.js');

check('APP_USER exposes operator ibadah id', strpos($index, 'ibadahId: <?= json_encode($is_op ? get_ibadah_id() : null) ?>') !== false);
check('ibadah module can detect current operator marker', strpos($ibadah, 'function isOwnOperatorIbadah(id)') !== false);
check('operator highlight uses gold accent', strpos($ibadah, 'OPERATOR_HIGHLIGHT') !== false && strpos($ibadah, '#f59e0b') !== false);
check('operator marker icon receives own marker flag', strpos($ibadah, 'createIbadahIcon(jenis, ownOperatorIbadah)') !== false);
check('operator circle uses highlighted style', strpos($ibadah, 'ownOperatorIbadah ? OPERATOR_HIGHLIGHT.stroke : clr.stroke') !== false);
check('operator marker gets higher z-index', strpos($ibadah, 'ownOperatorIbadah ? 1000 : 100') !== false);
check('operator popup shows own ibadah badge', strpos($ibadah, 'Rumah ibadah Anda') !== false);

echo "\n--- {$pass} passed, {$fail} failed ---\n";
exit($fail > 0 ? 1 : 0);
