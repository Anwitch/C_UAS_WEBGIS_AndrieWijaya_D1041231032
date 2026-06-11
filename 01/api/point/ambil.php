<?php
// api/point/ambil.php — Ambil semua POI
header('Content-Type: application/json');
require_once '../../koneksi.php';

$result = $conn->query("SELECT id, nama_tempat, no_wa, buka_24jam, latitude, longitude FROM lokasi_usaha ORDER BY id DESC");

if (!$result) {
    error_log('Project 01 point read failed: ' . $conn->error);
    json_error('Gagal memuat data lokasi.', 500);
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    'status' => 'success',
    'total' => count($data),
    'data' => $data
]);

$conn->close();
