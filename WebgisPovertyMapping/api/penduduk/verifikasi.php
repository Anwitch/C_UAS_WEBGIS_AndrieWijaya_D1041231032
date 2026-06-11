<?php
header('Content-Type: application/json');
require_once '../../config.php';
require_once '../../auth/helper.php';
require_once '../../koneksi.php';
require_auth('administrator');
require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$id      = (int)trim($_POST['id']     ?? 0);
$aksi    = trim($_POST['aksi']        ?? ''); // 'approve' | 'reject'
$catatan = trim($_POST['catatan']     ?? '');

if ($id <= 0 || !in_array($aksi, ['approve', 'reject'], true)) {
    echo json_encode(['status' => 'error', 'message' => 'Parameter tidak valid.']);
    exit;
}

$status_baru = $aksi === 'approve' ? 'Terverifikasi' : 'Ditolak';
$admin_id    = get_user_id();
$catatan_val = $catatan !== '' ? mb_substr($catatan, 0, 500) : null;

$stmt = $conn->prepare("
    UPDATE penduduk_miskin
    SET status_verifikasi  = ?,
        verified_by        = ?,
        verified_at        = NOW(),
        catatan_verifikasi = ?
    WHERE id = ? AND deleted_at IS NULL
");
$stmt->bind_param('sisi', $status_baru, $admin_id, $catatan_val, $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['status' => 'success', 'status_verifikasi' => $status_baru, 'id' => $id]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan atau tidak ada perubahan.']);
}
$stmt->close();
$conn->close();
