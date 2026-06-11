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

$penduduk = file_get_contents($root . '/api/penduduk/ambil.php');
$stats = file_get_contents($root . '/api/stats/ambil.php');
$index = file_get_contents($root . '/index.php');
$heatmap = file_get_contents($root . '/modules/heatmap.js');

check(
    'penduduk defines privileged role gate',
    strpos($penduduk, '$is_privileged = $is_authenticated && has_role(\'operator\');') !== false
);

check(
    'penduduk public verification filter depends on privileged role',
    strpos($penduduk, '$public_verif_sql = $is_privileged ? "" : " AND pm.status_verifikasi = \'Terverifikasi\'";') !== false
);

check(
    'penduduk sensitive data still requires operator role',
    strpos($penduduk, '$can_see_sensitive = $is_privileged;') !== false
);

check(
    'stats defines privileged role gate',
    strpos($stats, '$is_privileged = $is_auth && has_role(\'operator\');') !== false
);

check(
    'stats public verification filter depends on privileged role',
    strpos($stats, '$public_verif_sql = $is_privileged ? "" : " AND status_verifikasi = \'Terverifikasi\'";') !== false
);

check(
    'public heatmap section is rendered only for admin or operator',
    strpos($index, '<?php if ($is_admin || $is_op): ?>') !== false
        && strpos($index, 'Heatmap Kepadatan') !== false
);

check(
    'public viewer does not render penduduk reachability layer chips',
    strpos($index, '<?php if ($is_admin || $is_op): ?>' . "\r\n" . '                            <label class="layer-chip" title="Sembunyikan warga terjangkau (hijau)">') !== false
        || strpos($index, '<?php if ($is_admin || $is_op): ?>' . "\n" . '                            <label class="layer-chip" title="Sembunyikan warga terjangkau (hijau)">') !== false
);

check(
    'public viewer does not render penduduk reachability dropdown',
    strpos($index, '<?php if ($is_admin || $is_op): ?>' . "\r\n" . '                            <select id="filterKeterjangkauan"') !== false
        || strpos($index, '<?php if ($is_admin || $is_op): ?>' . "\n" . '                            <select id="filterKeterjangkauan"') !== false
);

check(
    'heatmap module guards against public viewer activation',
    strpos($heatmap, '!window.APP_USER?.isOp') !== false
        && strpos($heatmap, '!window.APP_USER?.isAdmin') !== false
);

echo "\n--- {$pass} passed, {$fail} failed ---\n";
exit($fail > 0 ? 1 : 0);
