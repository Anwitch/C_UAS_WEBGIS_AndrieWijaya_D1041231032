<?php
header('Content-Type: application/json');
require_once '../../config.php';
require_once '../../auth/helper.php';
require_once '../../koneksi.php';

require_auth('operator');

$penduduk_id = (int)($_GET['penduduk_id'] ?? 0);
if ($penduduk_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Parameter tidak valid.']);
    exit;
}

// Operator hanya boleh lihat kebutuhan warga dalam ibadah-nya
if (!has_role('administrator')) {
    $op_ibadah = get_ibadah_id();
    $stmt = $conn->prepare(
        "SELECT ibadah_id FROM penduduk_miskin
         WHERE id=? AND is_active=1 AND deleted_at IS NULL"
    );
    $stmt->bind_param('i', $penduduk_id);
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
    SELECT k.id, k.kategori, k.deskripsi, k.status, k.created_at,
           u.nama_lengkap AS created_by_nama
    FROM kebutuhan k
    LEFT JOIN users u ON u.id = k.created_by
    WHERE k.penduduk_id = ?
    ORDER BY k.created_at DESC
");
$stmt->bind_param('i', $penduduk_id);
$stmt->execute();
$res  = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;
$stmt->close();

echo json_encode(['status' => 'success', 'data' => $rows]);
$conn->close();
