<?php
require_once '../../auth/helper.php';
require_once '../../includes/validation.php';

require_admin_post();
require_once '../../koneksi.php';

$nama = clean_string($_POST['nama_parsil'] ?? '', 150);
$status = validate_enum($_POST['status_kepemilikan'] ?? '', ['SHM', 'HGB', 'HGU', 'HP']);
$geojsonRaw = trim($_POST['geojson'] ?? '');
$decoded = decode_geojson($geojsonRaw);
$luas = max(0, (float)($_POST['luas_m2'] ?? 0));

if ($nama === '' || $status === null || $decoded === null || !validate_geojson_geometry($decoded, ['Polygon'])) {
    json_error('Data parsil tidak valid.', 400);
}

$stmt = $conn->prepare("INSERT INTO data_parsil (nama_parsil, status_kepemilikan, geojson, luas_m2) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    error_log('Project 01 parcel insert prepare failed: ' . $conn->error);
    json_error('Gagal menyimpan data parsil.', 500);
}

$stmt->bind_param('sssd', $nama, $status, $geojsonRaw, $luas);
if (!$stmt->execute()) {
    error_log('Project 01 parcel insert failed: ' . $stmt->error);
    json_error('Gagal menyimpan data parsil.', 500);
}

json_success([
    'message' => 'Data parsil berhasil disimpan',
    'data' => [
        'id' => $conn->insert_id,
        'nama_parsil' => $nama,
        'status_kepemilikan' => $status,
        'geojson' => $geojsonRaw,
        'luas_m2' => $luas,
    ],
]);
