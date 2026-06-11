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

$update = file_get_contents($root . '/api/users/update.php');
$simpan = file_get_contents($root . '/api/users/simpan.php');
$toggle = file_get_contents($root . '/api/users/toggle_active.php');

check('users update rejects self-demotion', strpos($update, 'Tidak dapat menurunkan role akun sendiri') !== false);
check('users update prevents removing last active administrator', strpos($update, 'administrator aktif terakhir') !== false);
check('users update validates operator ibadah is active', strpos($update, 'rumah_ibadah') !== false && strpos($update, 'deleted_at IS NULL') !== false);
check('users update clears ibadah for non-operator', strpos($update, '$ibadah_id = null') !== false);

check('users simpan validates operator ibadah is active', strpos($simpan, 'rumah_ibadah') !== false && strpos($simpan, 'deleted_at IS NULL') !== false);
check('users toggle prevents deactivating last active administrator', strpos($toggle, 'administrator aktif terakhir') !== false);

echo "\n--- {$pass} passed, {$fail} failed ---\n";
exit($fail > 0 ? 1 : 0);
