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

$map = file_get_contents($root . '/pages/map.php');
$pageStart = file_get_contents($root . '/includes/page-start.php');
$index = file_get_contents($root . '/index.php');
$dashboard = file_get_contents($root . '/dashboard.php');
$laporan = file_get_contents($root . '/pages/laporan.php');

check(
    'pages/map.php requires login before admin layout',
    strpos($map, "if (!is_logged_in()") !== false
    && strpos($map, "header('Location: ../auth/login.php')") !== false
);
check('sidebar has administrator branch', strpos($pageStart, "\$_nav_role === 'administrator'") !== false);
check('sidebar has operator branch', strpos($pageStart, "\$_nav_role === 'operator'") !== false);
check('admin-only import is inside administrator branch', strpos($pageStart, "pages/import.php") !== false && strpos($pageStart, "\$_nav_role === 'administrator'") !== false);
check('operator map dropdown avoids administrator dashboard', strpos($index, "has_role('administrator')") !== false && strpos($index, 'pages/status-bantuan.php') !== false);
check('dashboard has no href dead link', strpos($dashboard, 'href="#"') === false);
check('laporan export buttons are disabled before selection', strpos($laporan, 'aria-disabled="true"') !== false);

echo "\n--- {$pass} passed, {$fail} failed ---\n";
exit($fail > 0 ? 1 : 0);
