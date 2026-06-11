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

$endpoint = file_get_contents($root . '/api/papan/kontak_donatur.php');

check('donor endpoint starts session for rate limit', strpos($endpoint, 'session_start') !== false || strpos($endpoint, 'auth/helper.php') !== false);
check('donor endpoint records last submission time', strpos($endpoint, 'donor_last_submit') !== false);
check('donor endpoint returns 429 on too many submissions', strpos($endpoint, 'http_response_code(429)') !== false);
check('donor endpoint has simple time window', strpos($endpoint, 'time()') !== false && strpos($endpoint, '60') !== false);
check('donor endpoint uses client IP for server-side rate limit', strpos($endpoint, 'REMOTE_ADDR') !== false && strpos($endpoint, 'hash(') !== false);
check('donor endpoint stores server-side rate limit file', strpos($endpoint, 'file_put_contents') !== false && strpos($endpoint, 'donor-rate-limit') !== false);

echo "\n--- {$pass} passed, {$fail} failed ---\n";
exit($fail > 0 ? 1 : 0);
