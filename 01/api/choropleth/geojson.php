<?php
require_once '../../koneksi.php';
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']); exit;
}

$stmt = $conn->prepare("SELECT geojson FROM choropleth_layers WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$row) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Layer tidak ditemukan.']); exit;
}

echo $row['geojson'];
