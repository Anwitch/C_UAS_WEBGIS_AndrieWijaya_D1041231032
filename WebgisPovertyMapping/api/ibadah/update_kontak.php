<?php
// api/ibadah/update_kontak.php — Update kontak rumah ibadah
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

$id     = (int) ($_POST['id'] ?? 0);
$kontak = trim($_POST['kontak'] ?? '');

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']);
    exit;
}

$active_stmt = $conn->prepare("SELECT id FROM rumah_ibadah WHERE id = ? AND deleted_at IS NULL LIMIT 1");
$active_stmt->bind_param('i', $id);
$active_stmt->execute();
$active_row = $active_stmt->get_result()->fetch_assoc();
$active_stmt->close();
if (!$active_row) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan atau sudah dihapus']);
    exit;
}

$stmt = $conn->prepare("UPDATE rumah_ibadah SET kontak = ? WHERE id = ? AND deleted_at IS NULL");
$stmt->bind_param('si', $kontak, $id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Kontak berhasil diperbarui']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengupdate kontak: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
