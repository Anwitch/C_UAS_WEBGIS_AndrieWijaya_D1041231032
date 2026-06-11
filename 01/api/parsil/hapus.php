<?php
// api/parsil/hapus.php — Hapus data parsil tanah
header('Content-Type: application/json');
require_once '../../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM data_parsil WHERE id = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['status' => 'success', 'message' => 'Data parsil berhasil dihapus']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan atau gagal dihapus']);
}

$stmt->close();
$conn->close();