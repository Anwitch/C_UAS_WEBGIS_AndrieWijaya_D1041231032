<?php
// api/point/update_posisi.php — Update koordinat POI (drag marker)
header('Content-Type: application/json');
require_once '../../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
$lat = (float) ($_POST['latitude'] ?? 0);
$lng = (float) ($_POST['longitude'] ?? 0);

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']);
    exit;
}

$stmt = $conn->prepare("UPDATE lokasi_usaha SET latitude = ?, longitude = ? WHERE id = ?");
$stmt->bind_param('ddi', $lat, $lng, $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['status' => 'success', 'message' => 'Posisi diperbarui']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui posisi']);
}

$stmt->close();
$conn->close();