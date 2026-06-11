<?php
require_once '../../auth/helper.php';
require_once '../../includes/validation.php';

require_admin_post();
require_once '../../koneksi.php';

$nama = clean_string($_POST['nama_jalan'] ?? '', 150);
$statusJalan = validate_enum($_POST['status_jalan'] ?? '', ['Nasional', 'Provinsi', 'Kabupaten']);
$geojsonRaw = trim($_POST['geojson'] ?? '');
$decoded = decode_geojson($geojsonRaw);
$panjang = max(0, (float)($_POST['panjang_meter'] ?? 0));

if ($nama === '' || $statusJalan === null || $decoded === null || !validate_geojson_geometry($decoded, ['LineString'])) {
    json_error('Data jalan tidak valid.', 400);
}

$stmt = $conn->prepare("INSERT INTO data_jalan (nama_jalan, status_jalan, geojson, panjang_meter) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    error_log('Project 01 road insert prepare failed: ' . $conn->error);
    json_error('Gagal menyimpan data jalan.', 500);
}

$stmt->bind_param('sssd', $nama, $statusJalan, $geojsonRaw, $panjang);
if (!$stmt->execute()) {
    error_log('Project 01 road insert failed: ' . $stmt->error);
    json_error('Gagal menyimpan data jalan.', 500);
}

json_success([
    'message' => 'Data jalan berhasil disimpan',
    'data' => [
        'id' => $conn->insert_id,
        'nama_jalan' => $nama,
        'status_jalan' => $statusJalan,
        'geojson' => $geojsonRaw,
        'panjang_meter' => $panjang,
    ],
]);
