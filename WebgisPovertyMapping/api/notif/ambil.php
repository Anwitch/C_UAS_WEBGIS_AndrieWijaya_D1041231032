<?php
header('Content-Type: application/json');
require_once '../../config.php';
require_once '../../auth/helper.php';
require_once '../../koneksi.php';

if (!is_logged_in()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$is_admin = has_role('administrator');
$is_op    = has_role('operator');
$items    = [];

// ── Admin only ───────────────────────────────────────────────────────
if ($is_admin) {
    $r = $conn->query("
        SELECT COUNT(*) AS n FROM penduduk_miskin
        WHERE is_active=1 AND deleted_at IS NULL AND status_verifikasi='Pending'
    ");
    if ($r && ($n = (int)$r->fetch_assoc()['n'])) {
        $items[] = [
            'type'  => 'pending_verif',
            'label' => "$n penduduk menunggu verifikasi",
            'count' => $n,
            'page'  => 'pages/penduduk.php',
            'icon'  => 'clock',
        ];
    }

    $de = $conn->query(
        "SELECT 1 FROM information_schema.TABLES
         WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='kontak_donatur' LIMIT 1"
    )->num_rows > 0;
    if ($de) {
        $r = $conn->query("SELECT COUNT(*) AS n FROM kontak_donatur WHERE is_read=0");
        if ($r && ($n = (int)$r->fetch_assoc()['n'])) {
            $items[] = [
                'type'  => 'donatur',
                'label' => "$n pesan donatur belum dibaca",
                'count' => $n,
                'page'  => 'pages/kebutuhan.php',
                'icon'  => 'mail',
            ];
        }
    }
}

// ── Admin + Operator ────────────────────────────────────────────────
if ($is_admin || $is_op) {
    $scope = '';
    if ($is_op && !$is_admin) {
        $iid = (int)get_ibadah_id();
        $scope = $iid > 0 ? " AND pm.ibadah_id = $iid" : " AND 1=0";
    }

    $ke = $conn->query(
        "SELECT 1 FROM information_schema.TABLES
         WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='kebutuhan' LIMIT 1"
    )->num_rows > 0;
    if ($ke) {
        $r = $conn->query("
            SELECT COUNT(*) AS n FROM kebutuhan k
            JOIN penduduk_miskin pm
              ON pm.id = k.penduduk_id AND pm.is_active=1 AND pm.deleted_at IS NULL
            WHERE k.status = 'Belum Terpenuhi' $scope
        ");
        if ($r && ($n = (int)$r->fetch_assoc()['n'])) {
            $items[] = [
                'type'  => 'kebutuhan',
                'label' => "$n kebutuhan belum terpenuhi",
                'count' => $n,
                'page'  => 'pages/kebutuhan.php',
                'icon'  => 'heart',
            ];
        }
    }

    $r = $conn->query("
        SELECT COUNT(*) AS n FROM penduduk_miskin pm
        WHERE pm.is_active=1 AND pm.deleted_at IS NULL
          AND pm.status_verifikasi = 'Terverifikasi'
          AND pm.status_bantuan    = 'Belum Ditangani'
          $scope
    ");
    if ($r && ($n = (int)$r->fetch_assoc()['n'])) {
        $items[] = [
            'type'  => 'belum_ditangani',
            'label' => "$n data belum ditangani",
            'count' => $n,
            'page'  => 'pages/status-bantuan.php',
            'icon'  => 'clipboard-list',
        ];
    }
}

echo json_encode([
    'status' => 'success',
    'items'  => $items,
    'total'  => array_sum(array_column($items, 'count')),
]);
