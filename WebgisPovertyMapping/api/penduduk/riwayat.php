<?php
header('Content-Type: application/json');
require_once '../../config.php';
require_once '../../auth/helper.php';
require_once '../../koneksi.php';

require_auth('operator');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
    exit;
}

// Operator hanya boleh lihat riwayat warga dalam ibadah-nya
if (!has_role('administrator')) {
    $op_ibadah = get_ibadah_id();
    $stmt = $conn->prepare(
        "SELECT ibadah_id FROM penduduk_miskin
         WHERE id=? AND is_active=1 AND deleted_at IS NULL"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$r || (int)$r['ibadah_id'] !== (int)$op_ibadah) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
        exit;
    }
}

$stmt = $conn->prepare("
    SELECT r.id, r.status_lama, r.status_baru, r.catatan, r.created_at,
           u.nama_lengkap AS user_nama
    FROM riwayat_bantuan r
    LEFT JOIN users u ON u.id = r.operator_id
    WHERE r.penduduk_id = ?
    ORDER BY r.created_at DESC
    LIMIT 20
");
$stmt->bind_param('i', $id);
$stmt->execute();
$res  = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;
$stmt->close();

echo json_encode(['status' => 'success', 'data' => $rows]);
$conn->close();
