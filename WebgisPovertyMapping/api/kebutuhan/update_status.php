<?php
header('Content-Type: application/json');
require_once '../../config.php';
require_once '../../auth/helper.php';
require_once '../../koneksi.php';

require_auth('operator');
require_csrf();

$id     = (int)($_POST['id']     ?? 0);
$status = trim($_POST['status']  ?? '');
$catatan = trim($_POST['catatan'] ?? '');

$valid_status = ['Belum Terpenuhi', 'Dalam Proses', 'Terpenuhi'];
if ($id <= 0 || !in_array($status, $valid_status, true)) {
    echo json_encode(['status' => 'error', 'message' => 'Parameter tidak valid.']);
    exit;
}

// Ambil kebutuhan + ibadah warga untuk cek akses
$stmt = $conn->prepare("
    SELECT k.id, k.status AS status_lama, pm.ibadah_id, pm.status_verifikasi
    FROM kebutuhan k
    JOIN penduduk_miskin pm ON pm.id = k.penduduk_id
                            AND pm.is_active = 1
                            AND pm.deleted_at IS NULL
    WHERE k.id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan.']);
    exit;
}

if (!has_role('administrator')) {
    $op_ibadah = get_ibadah_id();
    if ($row['ibadah_id'] === null || (int)$row['ibadah_id'] !== (int)$op_ibadah) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
        exit;
    }
}

if ($row['status_verifikasi'] !== 'Terverifikasi') {
    echo json_encode(['status' => 'error', 'message' => 'Data warga belum terverifikasi']);
    exit;
}

$user_id    = (int)$_SESSION['user_id'];
$old_status = $row['status_lama'];

$stmt = $conn->prepare(
    "UPDATE kebutuhan SET status=?, updated_by=?, updated_at=NOW() WHERE id=?"
);
$stmt->bind_param('sii', $status, $user_id, $id);
$stmt->execute();
$stmt->close();

$stmt = $conn->prepare(
    "INSERT INTO riwayat_kebutuhan (kebutuhan_id, operator_id, status_lama, status_baru, catatan)
     VALUES (?,?,?,?,?)"
);
$catatan_val = $catatan !== '' ? $catatan : null;
$stmt->bind_param('iisss', $id, $user_id, $old_status, $status, $catatan_val);
$stmt->execute();
$stmt->close();

echo json_encode(['status' => 'success', 'status_baru' => $status]);
$conn->close();
