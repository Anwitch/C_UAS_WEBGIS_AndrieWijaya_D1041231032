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

$usersPage = file_get_contents($root . '/pages/users.php');
$userCreate = file_get_contents($root . '/api/users/simpan.php');
$userUpdate = file_get_contents($root . '/api/users/update.php');

check('users page does not offer enabled viewer role option', strpos($usersPage, '<option value="viewer">Viewer</option>') === false);
check('users page keeps disabled legacy viewer option for existing accounts', strpos($usersPage, '<option value="viewer" disabled>Viewer') !== false);
check('new account modal defaults to operator, not viewer', strpos($usersPage, "document.getElementById('fRole').value               = isEdit ? user.role : 'operator';") !== false);
check('backend still accepts viewer role for compatibility', strpos($userCreate, "'viewer'") !== false && strpos($userUpdate, "'viewer'") !== false);
check('public map access remains unauthenticated', strpos(file_get_contents($root . '/index.php'), "\$role = is_logged_in() ? get_role() : 'viewer';") !== false);

echo "\n--- {$pass} passed, {$fail} failed ---\n";
exit($fail > 0 ? 1 : 0);
