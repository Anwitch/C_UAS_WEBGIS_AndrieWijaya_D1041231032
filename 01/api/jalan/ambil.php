<?php
// api/jalan/ambil.php — Ambil semua data jalan
header('Content-Type: application/json');
require_once '../../koneksi.php';

$result = $conn->query("SELECT id, nama_jalan, status_jalan, geojson, panjang_meter FROM data_jalan ORDER BY id DESC");

if (!$result) {
    error_log('Project 01 road read failed: ' . $conn->error);
    json_error('Gagal memuat data jalan.', 500);
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
