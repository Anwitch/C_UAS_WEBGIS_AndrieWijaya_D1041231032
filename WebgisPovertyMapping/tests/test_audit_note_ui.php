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

$statusPage = file_get_contents($root . '/pages/status-bantuan.php');
$kebutuhanPage = file_get_contents($root . '/pages/kebutuhan.php');
$pendudukJs = file_get_contents($root . '/modules/penduduk.js');
$kebutuhanJs = file_get_contents($root . '/modules/kebutuhan.js');

check('status page sends catatan', strpos($statusPage, "fd.append('catatan'") !== false);
check('kebutuhan page sends catatan', strpos($kebutuhanPage, "fd.append('catatan'") !== false);
check('map penduduk status sends catatan', strpos($pendudukJs, "fd.append('catatan'") !== false);
check('map kebutuhan status sends catatan', strpos($kebutuhanJs, "fd.append('catatan'") !== false);
check('status UI asks for note', strpos($statusPage, 'prompt(') !== false || strpos($statusPage, 'catatan') !== false);
check('status page cancels update when note prompt is cancelled', strpos($statusPage, 'catatan === null') !== false);
check('kebutuhan page cancels update when note prompt is cancelled', strpos($kebutuhanPage, 'catatan === null') !== false);
check('map penduduk cancels update when note prompt is cancelled', strpos($pendudukJs, 'catatan === null') !== false);
check('map kebutuhan cancels update when note prompt is cancelled', strpos($kebutuhanJs, 'catatan === null') !== false);

echo "\n--- {$pass} passed, {$fail} failed ---\n";
exit($fail > 0 ? 1 : 0);
