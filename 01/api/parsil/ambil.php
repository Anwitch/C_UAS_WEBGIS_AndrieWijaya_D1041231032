<?php
// api/parsil/ambil.php — Ambil semua data parsil tanah
header('Content-Type: application/json');
require_once '../../koneksi.php';

$result = $conn->query("SELECT id, nama_parsil, status_kepemilikan, geojson, luas_m2 FROM data_parsil ORDER BY id DESC");

if (!$result) {
    error_log('Project 01 parcel read failed: ' . $conn->error);
    json_error('Gagal memuat data parsil.', 500);
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
