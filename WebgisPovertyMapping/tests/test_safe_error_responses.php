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

$koneksi = file_get_contents($root . '/koneksi.php');
$ambil = file_get_contents($root . '/api/penduduk/ambil.php');
$simpan = file_get_contents($root . '/api/penduduk/simpan.php');

check('db connection logs internal error', strpos($koneksi, 'error_log') !== false);
check('db connection does not append connect_error to response', strpos($koneksi, "'message' => 'Koneksi database gagal: ' . \$conn->connect_error") === false);
check('penduduk ambil logs query error', strpos($ambil, 'error_log') !== false);
check('penduduk ambil does not expose conn error in JSON', strpos($ambil, "'message' => \$conn->error") === false);
check('penduduk simpan logs stmt error', strpos($simpan, 'error_log') !== false);
check('penduduk simpan does not expose stmt error in JSON', strpos($simpan, "'message' => 'Gagal menyimpan: ' . \$stmt->error") === false);

echo "\n--- {$pass} passed, {$fail} failed ---\n";
exit($fail > 0 ? 1 : 0);
