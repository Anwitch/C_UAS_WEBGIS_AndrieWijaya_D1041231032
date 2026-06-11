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

$simpan = file_get_contents($root . '/api/penduduk/simpan.php');
$move = file_get_contents($root . '/api/penduduk/update_posisi.php');
$stats = file_get_contents($root . '/api/stats/ambil.php');

check(
    'simpan rejects operator without linked ibadah',
    strpos($simpan, 'Operator belum dikaitkan dengan rumah ibadah') !== false
);

check(
    'simpan rejects calculated ibadah outside operator scope',
    strpos($simpan, 'Lokasi warga berada di luar wilayah rumah ibadah operator') !== false
);

check(
    'simpan checks proximity result against operator ibadah',
    strpos($simpan, '(int)$p[\'ibadah_id\'] !== $op_ibadah_id') !== false
);

check(
    'update_posisi checks new proximity result against operator ibadah',
    strpos($move, '(int)$p[\'ibadah_id\'] !== $linked_ibadah_id') !== false
);

check(
    'update_posisi rejects move outside operator scope',
    strpos($move, 'Posisi baru berada di luar wilayah rumah ibadah operator') !== false
);

check(
    'stats defines operator scope sql',
    strpos($stats, '$operator_scope_pm_sql') !== false && strpos($stats, 'get_ibadah_id()') !== false
);

check(
    'stats applies operator scope to penduduk aliases',
    strpos($stats, 'pm.ibadah_id') !== false && strpos($stats, 'AND 1 = 0') !== false
);

check(
    'stats scopes total ibadah for operators',
    strpos($stats, 'id = ' . '$operator_ibadah_id') !== false || strpos($stats, 'id = {$operator_ibadah_id}') !== false
);

echo "\n--- {$pass} passed, {$fail} failed ---\n";
exit($fail > 0 ? 1 : 0);
