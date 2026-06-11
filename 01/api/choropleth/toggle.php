<?php
require_once '../../auth/helper.php';

require_admin_post();
require_once '../../koneksi.php';

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    json_error('ID tidak valid.', 400);
}

$stmt = $conn->prepare("UPDATE choropleth_layers SET is_visible = 1 - is_visible WHERE id = ?");
if (!$stmt) {
    error_log('Project 01 choropleth toggle prepare failed: ' . $conn->error);
    json_error('Gagal mengubah visibilitas layer.', 500);
}

$stmt->bind_param('i', $id);
if (!$stmt->execute()) {
    error_log('Project 01 choropleth toggle failed: ' . $stmt->error);
    json_error('Gagal mengubah visibilitas layer.', 500);
}
$stmt->close();

$stmt2 = $conn->prepare("SELECT is_visible FROM choropleth_layers WHERE id = ?");
if (!$stmt2) {
    error_log('Project 01 choropleth visibility prepare failed: ' . $conn->error);
    json_error('Gagal memuat status layer.', 500);
}

$stmt2->bind_param('i', $id);
$stmt2->execute();
$row = $stmt2->get_result()->fetch_assoc();

if (!$row) {
    json_error('Layer tidak ditemukan.', 404);
}

json_success(['is_visible' => (bool)$row['is_visible']]);
