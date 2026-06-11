<?php
// tests/test_auth_helper.php — Test fungsi di auth/helper.php
// Jalankan via browser: http://localhost/webgis/WebgisPovertyMapping/tests/test_auth_helper.php
if (PHP_SAPI === 'cli') {
    $session_path = __DIR__ . '/../tmp/test-sessions';
    if (!is_dir($session_path)) {
        mkdir($session_path, 0777, true);
    }
    session_save_path($session_path);
}
require_once '../config.php';

$pass = 0; $fail = 0;
function check($label, $ok) {
    global $pass, $fail;
    if ($ok) { echo "✅ $label\n"; $pass++; }
    else      { echo "❌ $label\n"; $fail++; }
}

require_once '../auth/helper.php';

// Test 1: tanpa session → is_logged_in() return false
$_SESSION = [];
check('is_logged_in() false tanpa session', is_logged_in() === false);

// Test 2: session ada → is_logged_in() return true
$_SESSION['user_id']       = 1;
$_SESSION['role']          = 'administrator';
$_SESSION['last_activity'] = time();
check('is_logged_in() true dengan session',      is_logged_in() === true);
check('get_role() returns administrator',         get_role() === 'administrator');
check('has_role(administrator) true',             has_role('administrator') === true);
check('administrator satisfies operator role',    has_role('operator') === true);

// Test 3: operator hanya bisa akses role operator dan viewer
$_SESSION['role'] = 'operator';
check('operator: has_role(operator) true',        has_role('operator') === true);
check('operator: has_role(administrator) false',  has_role('administrator') === false);

// Test 4: get_ibadah_id
$_SESSION['ibadah_id'] = 5;
check('get_ibadah_id() returns 5', get_ibadah_id() === 5);

// Test 5: viewer has lowest access
$_SESSION['role'] = 'viewer';
check('viewer: has_role(viewer) true',         has_role('viewer') === true);
check('viewer: has_role(operator) false',      has_role('operator') === false);

echo "\n--- $pass passed, $fail failed ---\n";
