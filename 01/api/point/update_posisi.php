<?php
require_once '../../auth/helper.php';
require_once '../../includes/validation.php';

require_admin_post();
require_once '../../koneksi.php';

$id = (int)($_POST['id'] ?? 0);
$coords = validate_lat_lng($_POST['latitude'] ?? null, $_POST['longitude'] ?? null);

if ($id <= 0 || $coords === null) {
    json_error('ID atau koordinat tidak valid.', 400);
}

[$lat, $lng] = $coords;
$stmt = $conn->prepare("UPDATE lokasi_usaha SET latitude = ?, longitude = ? WHERE id = ?");
if (!$stmt) {
    error_log('Project 01 point position prepare failed: ' . $conn->error);
    json_error('Gagal memperbarui posisi.', 500);
}

$stmt->bind_param('ddi', $lat, $lng, $id);
if (!$stmt->execute()) {
    error_log('Project 01 point position update failed: ' . $stmt->error);
    json_error('Gagal memperbarui posisi.', 500);
}

if ($stmt->affected_rows <= 0) {
    json_error('Data tidak ditemukan atau posisi tidak berubah.', 404);
}

json_success(['message' => 'Posisi diperbarui']);
