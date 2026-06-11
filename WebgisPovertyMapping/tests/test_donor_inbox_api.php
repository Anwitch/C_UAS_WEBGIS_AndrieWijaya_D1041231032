<?php
header('Content-Type: text/plain');

$root = realpath(__DIR__ . '/..');
$pass = 0; $fail = 0;

function check($label, $ok) {
    global $pass, $fail;
    if ($ok) { echo "PASS: {$label}\n"; $pass++; }
    else     { echo "FAIL: {$label}\n"; $fail++; }
}

$api   = file_get_contents($root . '/api/papan/donatur.php');
$index = file_get_contents($root . '/index.php');

check('donatur API reads $action from POST', strpos($api, '$action') !== false);
check('donatur API handles action=hapus', strpos($api, "'hapus'") !== false && strpos($api, 'DELETE FROM kontak_donatur') !== false);
check('donatur API handles action=mark_one', strpos($api, "'mark_one'") !== false && strpos($api, 'UPDATE kontak_donatur SET is_read=1 WHERE id') !== false);
check('donatur API uses prepared statement (bind_param)', strpos($api, 'bind_param') !== false);
check('donatur API casts id to int', strpos($api, '(int)') !== false);
check('donatur API requires CSRF on all POST', strpos($api, 'require_csrf') !== false);
check('markDonorRead in index.php sends action=mark_all', strpos($index, "append('action', 'mark_all')") !== false);

echo "\n--- {$pass} passed, {$fail} failed ---\n";
exit($fail > 0 ? 1 : 0);
