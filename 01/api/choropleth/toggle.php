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

$stmt = $conn->prepare("UPDATE choropleth_layers SET is_visible = 1 - is_visible WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->close();

$stmt2 = $conn->prepare("SELECT is_visible FROM choropleth_layers WHERE id = ?");
$stmt2->bind_param('i', $id);
$stmt2->execute();
$row = $stmt2->get_result()->fetch_assoc();
$stmt2->close();
$conn->close();

echo json_encode(['status' => 'success', 'is_visible' => (bool)$row['is_visible']]);
