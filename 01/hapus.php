<?php
header('Content-Type: application/json');
require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan.']);
    exit;
}

$idRaw = $_POST['id'] ?? null;

if ($idRaw === null || !is_numeric($idRaw) || (int)$idRaw <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
    exit;
}

$id = (int)$idRaw;

$stmt = $conn->prepare("DELETE FROM lokasi_usaha WHERE id = ?");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Persiapan query gagal: ' . $conn->error]);
    exit;
}

$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Lokasi berhasil dihapus.']);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Lokasi tidak ditemukan.']);
    }
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
