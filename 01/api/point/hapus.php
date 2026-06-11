<?php
require_once '../../auth/helper.php';

require_admin_post();
require_once '../../koneksi.php';

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    json_error('ID tidak valid.', 400);
}

$stmt = $conn->prepare("DELETE FROM lokasi_usaha WHERE id = ?");
if (!$stmt) {
    error_log('Project 01 point delete prepare failed: ' . $conn->error);
    json_error('Gagal menghapus data.', 500);
}

$stmt->bind_param('i', $id);
if (!$stmt->execute()) {
    error_log('Project 01 point delete failed: ' . $stmt->error);
    json_error('Gagal menghapus data.', 500);
}

if ($stmt->affected_rows <= 0) {
    json_error('Data tidak ditemukan.', 404);
}

json_success(['message' => 'Data berhasil dihapus']);
