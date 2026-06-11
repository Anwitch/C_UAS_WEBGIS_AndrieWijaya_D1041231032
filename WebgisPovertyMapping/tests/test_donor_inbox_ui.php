<?php
header('Content-Type: text/plain');

$root = realpath(__DIR__ . '/..');
$pass = 0; $fail = 0;

function check($label, $ok) {
    global $pass, $fail;
    if ($ok) { echo "PASS: {$label}\n"; $pass++; }
    else     { echo "FAIL: {$label}\n"; $fail++; }
}

$src = file_get_contents($root . '/pages/kebutuhan.php');

check('kebutuhan page has tab-nav element',           strpos($src, 'class="tab-nav"') !== false);
check('kebutuhan page has Pesan Donatur tab button',  strpos($src, 'Pesan Donatur') !== false);
check('kebutuhan page has donaturBadge element',      strpos($src, 'donaturBadge') !== false);
check('kebutuhan page has tabKebutuhan panel',        strpos($src, 'tabKebutuhan') !== false);
check('kebutuhan page has tabDonatur panel',          strpos($src, 'tabDonatur') !== false);
check('kebutuhan page has switchTab function',        strpos($src, 'function switchTab') !== false);
check('kebutuhan page has loadDonorInbox function',   strpos($src, 'function loadDonorInbox') !== false);
check('kebutuhan page has renderDonorInbox function', strpos($src, 'function renderDonorInbox') !== false);
check('kebutuhan page has buildKontakUrl function',   strpos($src, 'function buildKontakUrl') !== false);
check('kebutuhan page has hapusDonatur function',     strpos($src, 'function hapusDonatur') !== false);
check('kebutuhan page has markAllRead function',      strpos($src, 'function markAllRead') !== false);
check('kebutuhan page fetches donatur API',           strpos($src, 'api/papan/donatur.php') !== false);
check('kebutuhan page has admin-only guard',          strpos($src, '_IS_ADMIN') !== false || strpos($src, 'is_admin') !== false);
check('kebutuhan page has wa.me contact URL logic',   strpos($src, 'wa.me') !== false);

echo "\n--- {$pass} passed, {$fail} failed ---\n";
exit($fail > 0 ? 1 : 0);
