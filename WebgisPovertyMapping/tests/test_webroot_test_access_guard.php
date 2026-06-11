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

$htaccess = $root . '/.htaccess';
check('.htaccess exists at project root', file_exists($htaccess));

$content = file_exists($htaccess) ? file_get_contents($htaccess) : '';
check(
    '.htaccess blocks tests directory',
    strpos($content, 'RedirectMatch 403 ^/webgis/WebgisPovertyMapping/tests(/|$)') !== false
    || strpos($content, 'RewriteRule ^tests(/|$) - [F,L]') !== false
);
check('.htaccess blocks tmp test sessions', strpos($content, 'RewriteRule ^tmp(/|$) - [F,L]') !== false);

$deploy = file_get_contents($root . '/docs/deployment-xampp.md');
check(
    'deployment docs mention blocking tests directory',
    strpos($deploy, 'Blokir folder tests') !== false
    || strpos($deploy, 'block tests') !== false
);

echo "\n--- {$pass} passed, {$fail} failed ---\n";
exit($fail > 0 ? 1 : 0);
