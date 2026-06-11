<?php
header('Content-Type: application/json');
require_once '../../config.php';
require_once '../../auth/helper.php';
require_once '../../koneksi.php';

require_auth('operator');
require_csrf();

$id      = (int)($_POST['id']     ?? 0);
$status  = trim($_POST['status']  ?? '');
$catatan = trim($_POST['catatan'] ?? '');

$allowed = ['Belum Ditangani', 'Dalam Proses', 'Sudah Ditangani'];
if ($id <= 0 || !in_array($status, $allowed, true)) {
    echo json_encode(['status' => 'error', 'message' => 'Parameter tidak valid.']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT id, ibadah_id, status_bantuan, status_verifikasi FROM penduduk_miskin
     WHERE id=? AND is_active=1 AND deleted_at IS NULL"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan.']);
    exit;
}

// Operator hanya boleh update warga dalam ibadah-nya sendiri
if (!has_role('administrator')) {
    $op_ibadah = get_ibadah_id();
    if ($row['ibadah_id'] === null || (int)$row['ibadah_id'] !== (int)$op_ibadah) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki akses untuk warga ini.']);
        exit;
    }
}

if ($row['status_verifikasi'] !== 'Terverifikasi') {
    echo json_encode(['status' => 'error', 'message' => 'Data warga belum terverifikasi']);
    exit;
}

$old_status = $row['status_bantuan'];
$user_id    = (int)$_SESSION['user_id'];

$stmt = $conn->prepare(
    "UPDATE penduduk_miskin SET status_bantuan=?, updated_at=NOW() WHERE id=?"
);
$stmt->bind_param('si', $status, $id);
$stmt->execute();
$stmt->close();

$stmt = $conn->prepare(
    "INSERT INTO riwayat_bantuan (penduduk_id, operator_id, status_lama, status_baru, catatan)
     VALUES (?,?,?,?,?)"
);
$stmt->bind_param('iisss', $id, $user_id, $old_status, $status, $catatan);
$stmt->execute();
$stmt->close();

echo json_encode(['status' => 'success', 'status_baru' => $status]);
$conn->close();
