<?php
require_once '../../koneksi.php';
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    json_error('ID tidak valid.', 400);
}

$stmt = $conn->prepare("SELECT geojson FROM choropleth_layers WHERE id = ? AND is_visible = 1");
if (!$stmt) {
    error_log('Project 01 choropleth geojson prepare failed: ' . $conn->error);
    json_error('Gagal memuat GeoJSON.', 500);
}
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$row) {
    json_error('Layer tidak ditemukan.', 404);
}

echo $row['geojson'];
