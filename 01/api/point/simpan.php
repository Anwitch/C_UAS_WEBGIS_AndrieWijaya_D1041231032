<?php
require_once '../../auth/helper.php';
require_once '../../includes/validation.php';

require_admin_post();
require_once '../../koneksi.php';

$nama = clean_string($_POST['nama_tempat'] ?? '', 150);
$wa = clean_string($_POST['no_wa'] ?? '', 30);
$buka24 = (int)($_POST['buka_24jam'] ?? 0) === 1 ? 1 : 0;
$coords = validate_lat_lng($_POST['latitude'] ?? null, $_POST['longitude'] ?? null);

if ($nama === '' || $coords === null) {
    json_error('Nama tempat dan koordinat valid wajib diisi.', 400);
}

[$lat, $lng] = $coords;
$stmt = $conn->prepare("INSERT INTO lokasi_usaha (nama_tempat, no_wa, buka_24jam, latitude, longitude) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
    error_log('Project 01 point insert prepare failed: ' . $conn->error);
    json_error('Gagal menyimpan data.', 500);
}

$stmt->bind_param('ssidd', $nama, $wa, $buka24, $lat, $lng);
if (!$stmt->execute()) {
    error_log('Project 01 point insert failed: ' . $stmt->error);
    json_error('Gagal menyimpan data.', 500);
}

json_success([
    'message' => 'Data berhasil disimpan',
    'data' => [
        'id' => $conn->insert_id,
        'nama_tempat' => $nama,
        'no_wa' => $wa,
        'buka_24jam' => $buka24,
        'latitude' => $lat,
        'longitude' => $lng,
    ],
]);
