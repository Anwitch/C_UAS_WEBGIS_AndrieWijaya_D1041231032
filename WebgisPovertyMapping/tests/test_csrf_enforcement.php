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

$helper = file_get_contents($root . '/auth/helper.php');
check('csrf_token function exists', strpos($helper, 'function csrf_token(): string') !== false);
check('require_csrf function exists', strpos($helper, 'function require_csrf(): void') !== false);
check('require_csrf uses hash_equals', strpos($helper, 'hash_equals') !== false);

$must_have = [
    'api/ibadah/simpan.php',
    'api/ibadah/hapus.php',
    'api/ibadah/update.php',
    'api/ibadah/update_posisi.php',
    'api/ibadah/update_radius.php',
    'api/ibadah/update_kontak.php',
    'api/ibadah/recalculate.php',
    'api/penduduk/simpan.php',
    'api/penduduk/update_posisi.php',
    'api/penduduk/update_status.php',
    'api/penduduk/hapus.php',
    'api/penduduk/nonaktifkan.php',
    'api/penduduk/verifikasi.php',
    'api/kebutuhan/simpan.php',
    'api/kebutuhan/update_status.php',
    'api/users/simpan.php',
    'api/users/update.php',
    'api/users/reset_password.php',
    'api/users/toggle_active.php',
    'api/import/csv.php',
    'api/auth/change_password.php',
    'api/papan/donatur.php',
];

foreach ($must_have as $file) {
    $content = file_get_contents($root . '/' . $file);
    check("{$file} calls require_csrf", strpos($content, 'require_csrf();') !== false);
}

$index = file_get_contents($root . '/index.php');
$pageStart = file_get_contents($root . '/includes/page-start.php');
$changePassword = file_get_contents($root . '/auth/change_password.php');

check('index exposes csrf token on APP_USER', strpos($index, 'csrfToken: <?= json_encode($logged_in ? csrf_token() : null) ?>') !== false);
check('index defines appendCsrf helper', strpos($index, 'function appendCsrf(fd)') !== false);
check('dashboard pages expose APP_CSRF_TOKEN', strpos($pageStart, 'window.APP_CSRF_TOKEN = <?= json_encode(is_logged_in() ? csrf_token() : null) ?>;') !== false);
check('change password appends csrf token', strpos($changePassword, "fd.append('csrf_token',") !== false);
check('donor mark-read uses POST with csrf', strpos($index, "fetch('api/papan/donatur.php', { method: 'POST', body: appendCsrf(fd) })") !== false);
$donorEndpoint = file_get_contents($root . '/api/papan/donatur.php');
check('donor mark-read is POST-only', strpos($donorEndpoint, "\$_SERVER['REQUEST_METHOD'] === 'POST'") !== false);
check('donor mark-read requires csrf before update', strpos($donorEndpoint, 'require_csrf()') !== false && strpos($donorEndpoint, 'UPDATE kontak_donatur SET is_read=1') !== false);

$frontend_files = [
    'index.php',
    'modules/ibadah.js',
    'modules/penduduk.js',
    'modules/kebutuhan.js',
    'pages/analisis.php',
    'pages/ibadah.php',
    'pages/import.php',
    'pages/kebutuhan.php',
    'pages/penduduk.php',
    'pages/status-bantuan.php',
    'pages/users.php',
];

foreach ($frontend_files as $file) {
    $content = file_get_contents($root . '/' . $file);
    check("{$file} uses appendCsrf", strpos($content, 'appendCsrf(') !== false);
}

echo "\n--- {$pass} passed, {$fail} failed ---\n";
exit($fail > 0 ? 1 : 0);
