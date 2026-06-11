<?php
header('Content-Type: application/json');
require_once '../../config.php';
require_once '../../auth/helper.php';
require_once '../../includes/validation.php';
require_once '../../koneksi.php';
require_auth('administrator');
require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']); exit;
}

$nama   = trim($_POST['nama']     ?? '');
$jenis  = trim($_POST['jenis']    ?? 'Masjid');
$alamat = trim($_POST['alamat']   ?? '');
$kontak = trim($_POST['kontak']   ?? '');
$coord = validate_lat_lng($_POST['lat'] ?? null, $_POST['lng'] ?? null);
if (!$coord['ok']) {
    echo json_encode(['status' => 'error', 'message' => $coord['message']]); exit;
}
$lat    = $coord['lat'];
$lng    = $coord['lng'];
$radius = (int)   ($_POST['radius_m'] ?? 500);

if (!$nama) {
    echo json_encode(['status' => 'error', 'message' => 'Nama ibadah tidak boleh kosong']); exit;
}
if ($radius < 100 || $radius > 5000) {
    echo json_encode(['status' => 'error', 'message' => 'Radius minimum 100 meter, maksimum 5.000 meter']); exit;
}

$valid_jenis = ['Masjid','Mushola','Gereja','Pura','Vihara','Klenteng'];
if (!in_array($jenis, $valid_jenis)) $jenis = 'Masjid';

$stmt = $conn->prepare(
    "INSERT INTO rumah_ibadah (nama, jenis, alamat, lat, lng, radius_m, kontak) VALUES (?,?,?,?,?,?,?)"
);
$stmt->bind_param('sssddis', $nama, $jenis, $alamat, $lat, $lng, $radius, $kontak);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Data berhasil disimpan',
        'data' => ['id'=>$conn->insert_id,'nama'=>$nama,'jenis'=>$jenis,
                   'alamat'=>$alamat,'kontak'=>$kontak,'lat'=>$lat,'lng'=>$lng,'radius_m'=>$radius]]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: '.$stmt->error]);
}
$stmt->close(); $conn->close();
