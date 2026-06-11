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

$id = (int) ($_POST['id'] ?? 0);
if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']);
    exit;
}

$stmt = $conn->prepare(
    "UPDATE penduduk_miskin SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL"
);
$stmt->bind_param('i', $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['status' => 'success', 'message' => 'Data berhasil dihapus']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan atau sudah dihapus']);
}

$stmt->close();
$conn->close();
