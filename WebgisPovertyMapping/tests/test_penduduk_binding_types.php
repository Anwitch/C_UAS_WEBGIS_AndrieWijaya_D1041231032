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
$import = file_get_contents($root . '/api/import/csv.php');

check(
    'penduduk simpan bind types keep jarak_m double and is_blank_spot integer',
    strpos($simpan, "bind_param(\n    'ssisssddidisis'") !== false
);

check(
    'import csv bind types include verification fields after proximity fields',
    strpos($import, "bind_param(\n        'ssisssddidisis'") !== false
);

check(
    'import csv inserts verification metadata',
    strpos($import, 'status_verifikasi, verified_by, verified_at') !== false
        && strpos($import, "\$status_verif = 'Terverifikasi'") !== false
        && strpos($import, '$verified_by = get_user_id()') !== false
);

check(
    'penduduk simpan duplicate NIK checks archived records too',
    preg_match('/SELECT\s+id,\s+nama_kk\s+FROM\s+penduduk_miskin\s+WHERE\s+nik\s+=\s+\?\s+LIMIT\s+1/s', $simpan) === 1
);

check(
    'penduduk simpan duplicate NIK message points admin to archive',
    strpos($simpan, 'pernah terdaftar') !== false && strpos($simpan, 'arsip') !== false
);

check(
    'import csv duplicate NIK checks archived records too',
    preg_match('/SELECT\s+id\s+FROM\s+penduduk_miskin\s+WHERE\s+nik=\?\s+LIMIT\s+1/s', $import) === 1
);

echo "\n--- {$pass} passed, {$fail} failed ---\n";
exit($fail > 0 ? 1 : 0);
