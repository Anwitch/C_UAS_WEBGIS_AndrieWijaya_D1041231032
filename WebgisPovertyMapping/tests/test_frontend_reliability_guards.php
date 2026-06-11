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

$index = file_get_contents($root . '/index.php');
$heatmap = file_get_contents($root . '/modules/heatmap.js');
$ibadah = file_get_contents($root . '/modules/ibadah.js');
$penduduk = file_get_contents($root . '/modules/penduduk.js');
$stats = file_get_contents($root . '/modules/stats.js');
$kebutuhan = file_get_contents($root . '/modules/kebutuhan.js');
$pagePenduduk = file_get_contents($root . '/pages/penduduk.php');
$pageStatusBantuan = file_get_contents($root . '/pages/status-bantuan.php');
$pageKebutuhan = file_get_contents($root . '/pages/kebutuhan.php');
$dashboard = file_get_contents($root . '/dashboard.php');
$pageUsers = file_get_contents($root . '/pages/users.php');
$pageAnalisis = file_get_contents($root . '/pages/analisis.php');

check('module loader has onerror handler', strpos($index, 's.onerror = function ()') !== false);
check('module loader reports failed script source', strpos($index, 'Gagal memuat modul') !== false);
check('heatmap filters numeric coordinates', strpos($heatmap, 'Number.isFinite(lat) && Number.isFinite(lng)') !== false);
check('heatmap rejects null coordinate values', strpos($heatmap, 'rawLat !== null') !== false && strpos($heatmap, 'rawLng !== null') !== false);
check('heatmap rejects blank coordinate values', strpos($heatmap, "rawLat !== ''") !== false && strpos($heatmap, "rawLng !== ''") !== false);
check('recalc UI uses tb-icon selector', strpos($ibadah, "querySelector('.tb-icon')") !== false);
check('recalc UI no longer uses ft-icon selector', strpos($ibadah, "querySelector('.ft-icon')") === false);
check('global data changed dispatcher is defined', strpos($index, 'window.dispatchDataChanged') !== false && strpos($index, 'webgis:data-changed') !== false);
check('global refreshAllData is defined', strpos($index, 'window.refreshAllData') !== false);
check('ibadah exposes reload hook', strpos($ibadah, 'window._ibadahReload') !== false);
check('stats update guards missing modalStats', strpos($stats, 'const modal = document.getElementById(\'modalStats\')') !== false && strpos($stats, 'if (!modal) return;') !== false);
check('stats show/hide guards missing modalStats', strpos($stats, 'if (!modal) return;') !== false && strpos($stats, 'modal.classList.add(\'show\')') !== false && strpos($stats, 'modal.classList.remove(\'show\')') !== false);
check('penduduk exposes all dataset', strpos($penduduk, 'window._pendudukAll') !== false);
check('penduduk exposes visible dataset', strpos($penduduk, 'window._pendudukVisible') !== false);
check('heatmap uses all penduduk dataset by default', strpos($heatmap, 'window._pendudukAll') !== false);
check('heatmap blocks public viewer use', strpos($heatmap, '!window.APP_USER?.isOp') !== false && strpos($heatmap, '!window.APP_USER?.isAdmin') !== false);
check('penduduk popup passes verification status to kebutuhan section', strpos($penduduk, '_kebutuhanBuildSection(data.id, data.kebutuhan_open, sv)') !== false);
check('penduduk popup shows waiting message for unverified workflow actions', strpos($penduduk, 'Menunggu verifikasi admin') !== false);
check('kebutuhan popup section accepts verification status', strpos($kebutuhan, 'function (pendudukId, kebutuhanOpen, statusVerifikasi') !== false);
check('kebutuhan popup disables mutation controls for unverified warga', strpos($kebutuhan, "statusVerifikasi === 'Terverifikasi'") !== false && strpos($kebutuhan, 'Menunggu verifikasi admin') !== false);
check('penduduk page disables status bantuan for unverified warga', strpos($pagePenduduk, 'Menunggu verifikasi admin') !== false && strpos($pagePenduduk, 'disabled') !== false);
check('status bantuan page filters only verified warga', strpos($pageStatusBantuan, "status_verifikasi === 'Terverifikasi'") !== false);
check('kebutuhan page filters only verified warga', strpos($pageKebutuhan, "status_verifikasi === 'Terverifikasi'") !== false);
check('dashboard notification panel uses unique id', strpos($dashboard, 'id="dash-notif-list"') !== false && strpos($dashboard, "getElementById('dash-notif-list')") !== false);
check('dashboard boot waits for external scripts', strpos($dashboard, "window.addEventListener('load'") !== false && strpos($dashboard, 'loadStats();') !== false && strpos($dashboard, 'loadTren();') !== false);
check('dashboard notifications use canonical endpoint', strpos($dashboard, "fetch('api/notif/ambil.php") !== false && strpos($dashboard, 'loadDashboardNotifs();') !== false);
check('dashboard no longer derives notification badge from donor unread only', strpos($dashboard, 'const unread = parseInt(d.donatur_unread') === false);
check('dashboard notification rows are clickable links', strpos($dashboard, '<a href="${esc(it.page || \'#\')}" class="notif-item">') !== false);
check('penduduk table verification asks confirmation', strpos($pagePenduduk, 'Konfirmasi Verifikasi') !== false && strpos($pagePenduduk, 'await showConfirm') !== false && strpos($pagePenduduk, 'if (!ok) return;') !== false);
check('penduduk map verification asks confirmation', strpos($penduduk, 'Konfirmasi Verifikasi') !== false && strpos($penduduk, 'showDeleteConfirm') !== false && strpos($penduduk, 'if (!confirmed) return;') !== false);
check('user active toggle asks confirmation', strpos($pageUsers, 'await showConfirm') !== false && strpos($pageUsers, 'Nonaktifkan Akun') !== false && strpos($pageUsers, 'Aktifkan Akun') !== false && strpos($pageUsers, 'if (!ok) return;') !== false);
check('analysis recalculate asks confirmation', strpos($pageAnalisis, 'Hitung Ulang Proximity') !== false && strpos($pageAnalisis, 'await showConfirm') !== false && strpos($pageAnalisis, 'if (!ok) return;') !== false);
check('map recalculate asks confirmation', strpos($ibadah, 'Konfirmasi Hitung Ulang Proximity') !== false && strpos($ibadah, "btnLabel: 'Ya, Hitung Ulang'") !== false && strpos($ibadah, 'showDeleteConfirm') !== false && strpos($ibadah, 'if (!confirmed) return;') !== false);

echo "\n--- {$pass} passed, {$fail} failed ---\n";
exit($fail > 0 ? 1 : 0);
