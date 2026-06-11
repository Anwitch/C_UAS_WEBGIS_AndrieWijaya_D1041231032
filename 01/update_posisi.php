<?php
header('Content-Type: application/json');
require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan.']);
    exit;
}

$idRaw        = $_POST['id'] ?? null;
$latitudeRaw  = $_POST['latitude'] ?? null;
$longitudeRaw = $_POST['longitude'] ?? null;

if ($idRaw === null || $latitudeRaw === null || $longitudeRaw === null) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap.']);
    exit;
}

if (!is_numeric($idRaw) || !is_numeric($latitudeRaw) || !is_numeric($longitudeRaw)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Format data tidak valid.']);
    exit;
}

$id        = (int)$idRaw;
$latitude  = (float)$latitudeRaw;
$longitude = (float)$longitudeRaw;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
    exit;
}

$stmt = $conn->prepare(
    "UPDATE lokasi_usaha SET latitude = ?, longitude = ? WHERE id = ?"
);
$stmt->bind_param('ddi', $latitude, $longitude, $id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Posisi berhasil diperbarui.']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
