<?php
require_once '../../koneksi.php';
require_admin_json();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method tidak valid.']); exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']); exit;
}

$stmt = $conn->prepare("DELETE FROM choropleth_layers WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();
$conn->close();

if ($affected === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Layer tidak ditemukan.']); exit;
}

echo json_encode(['status' => 'success']);
