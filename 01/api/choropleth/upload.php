<?php
require_once '../../auth/helper.php';
require_once '../../includes/validation.php';

require_admin_post();
require_once '../../koneksi.php';

$nama = clean_string($_POST['nama'] ?? '', 150);
$deskripsi = clean_string($_POST['deskripsi'] ?? '', 500);
$attrKey = clean_string($_POST['attribute_key'] ?? '', 100);
$palette = validate_enum($_POST['palette'] ?? '', ['blue', 'orange', 'green', 'purple']) ?? 'blue';

if ($nama === '' || $attrKey === '') {
    json_error('Nama layer dan atribut wajib diisi.', 400);
}

if (empty($_FILES['geojson']['tmp_name']) || $_FILES['geojson']['error'] !== UPLOAD_ERR_OK) {
    json_error('File GeoJSON tidak ditemukan atau gagal diupload.', 400);
}

if ($_FILES['geojson']['size'] > MAX_GEOJSON_BYTES) {
    json_error('File terlalu besar.', 400);
}

$raw = file_get_contents($_FILES['geojson']['tmp_name']);
if ($raw === false) {
    json_error('Gagal membaca file.', 400);
}

$geojson = decode_geojson($raw);
if ($geojson === null || !validate_feature_collection($geojson, $attrKey)) {
    json_error('GeoJSON tidak valid atau atribut choropleth tidak lengkap.', 400);
}

$stmt = $conn->prepare("INSERT INTO choropleth_layers (nama, deskripsi, geojson, attribute_key, palette) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
    error_log('Project 01 choropleth upload prepare failed: ' . $conn->error);
    json_error('Gagal menyimpan layer.', 500);
}

$stmt->bind_param('sssss', $nama, $deskripsi, $raw, $attrKey, $palette);
if (!$stmt->execute()) {
    error_log('Project 01 choropleth upload failed: ' . $stmt->error);
    json_error('Gagal menyimpan layer.', 500);
}

json_success(['id' => $conn->insert_id, 'nama' => $nama]);
