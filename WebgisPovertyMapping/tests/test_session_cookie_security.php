<?php
header('Content-Type: text/plain');

$root = realpath(__DIR__ . '/..');
$helper = file_get_contents($root . '/auth/helper.php');

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

check('cookie httponly enabled', strpos($helper, "'httponly' => true") !== false);
check('cookie samesite strict enabled', strpos($helper, "'samesite' => 'Strict'") !== false);
check('cookie secure detects HTTPS', strpos($helper, "'secure' => (!empty(\$_SERVER['HTTPS']) && \$_SERVER['HTTPS'] !== 'off')") !== false);

echo "\n--- {$pass} passed, {$fail} failed ---\n";
exit($fail > 0 ? 1 : 0);
