<?php
require_once '../../koneksi.php';
require_admin_json();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method tidak valid.']); exit;
}

$nama      = trim($_POST['nama'] ?? '');
$deskripsi = trim($_POST['deskripsi'] ?? '');
$attr_key  = trim($_POST['attribute_key'] ?? '');
$palette   = in_array($_POST['palette'] ?? '', ['blue', 'orange', 'green', 'purple'])
             ? $_POST['palette'] : 'blue';

if (!$nama || !$attr_key) {
    echo json_encode(['status' => 'error', 'message' => 'Nama layer dan atribut wajib diisi.']); exit;
}

if (empty($_FILES['geojson']['tmp_name']) || $_FILES['geojson']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'File GeoJSON tidak ditemukan atau gagal diupload.']); exit;
}

if ($_FILES['geojson']['size'] > 10 * 1024 * 1024) {
    echo json_encode(['status' => 'error', 'message' => 'File terlalu besar (maksimum 10 MB).']); exit;
}

$raw = file_get_contents($_FILES['geojson']['tmp_name']);
if ($raw === false) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal membaca file.']); exit;
}

$gj = json_decode($raw, true);
if (!$gj || !isset($gj['type']) || $gj['type'] !== 'FeatureCollection') {
    echo json_encode(['status' => 'error', 'message' => 'File bukan GeoJSON FeatureCollection yang valid.']); exit;
}
if (empty($gj['features'])) {
    echo json_encode(['status' => 'error', 'message' => 'GeoJSON tidak memiliki fitur/data.']); exit;
}

$first_props = $gj['features'][0]['properties'] ?? [];
if (!array_key_exists($attr_key, $first_props)) {
    $available = implode(', ', array_keys($first_props));
    echo json_encode(['status' => 'error', 'message' => "Atribut '{$attr_key}' tidak ditemukan. Tersedia: {$available}"]); exit;
}

$stmt = $conn->prepare("INSERT INTO choropleth_layers (nama, deskripsi, geojson, attribute_key, palette) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param('sssss', $nama, $deskripsi, $raw, $attr_key, $palette);
$stmt->execute();
$id = $conn->insert_id;
$stmt->close();
$conn->close();

echo json_encode(['status' => 'success', 'id' => $id, 'nama' => $nama]);
