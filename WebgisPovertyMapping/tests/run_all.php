<?php
header('Content-Type: text/plain');

$tests = [
    'test_csrf_enforcement.php',
    'test_session_cookie_security.php',
    'test_operator_scope_endpoints.php',
    'test_operator_scope_strict.php',
    'test_viewer_public_filters.php',
    'test_user_management_guards.php',
    'test_frontend_reliability_guards.php',
    'test_deployment_docs.php',
    'test_webroot_test_access_guard.php',
    'test_navigation_role_guards.php',
    'test_public_donor_rate_limit.php',
    'test_csv_hardening.php',
    'test_safe_error_responses.php',
    'test_audit_note_ui.php',
    'test_documentation_sop.php',
    'test_recalculate_transaction_guard.php',
    'test_donor_inbox_api.php',
    'test_donor_inbox_ui.php',
];

$php = PHP_BINARY;
$failed = 0;

foreach ($tests as $test) {
    $path = __DIR__ . DIRECTORY_SEPARATOR . $test;
    if (!file_exists($path)) {
        echo "SKIP: {$test} not found\n";
        continue;
    }

    echo "\n=== {$test} ===\n";
    passthru('"' . $php . '" "' . $path . '"', $code);
    if ($code !== 0) {
        $failed++;
    }
}

echo "\n=== SUMMARY ===\n";
echo $failed === 0 ? "ALL PASSED\n" : "{$failed} test file(s) failed\n";
exit($failed > 0 ? 1 : 0);
