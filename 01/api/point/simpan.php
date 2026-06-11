<?php
// api/point/simpan.php — Simpan POI baru
header('Content-Type: application/json');
require_once '../../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$nama = trim($_POST['nama_tempat'] ?? '');
$wa = trim($_POST['no_wa'] ?? '');
$buka24 = isset($_POST['buka_24jam']) ? (int) $_POST['buka_24jam'] : 0;
$lat = (float) ($_POST['latitude'] ?? 0);
$lng = (float) ($_POST['longitude'] ?? 0);

if (!$nama) {
    echo json_encode(['status' => 'error', 'message' => 'Nama tempat tidak boleh kosong']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO lokasi_usaha (nama_tempat, no_wa, buka_24jam, latitude, longitude) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param('ssidd', $nama, $wa, $buka24, $lat, $lng);

if ($stmt->execute()) {
    $id = $conn->insert_id;
    echo json_encode([
        'status' => 'success',
        'message' => 'Data berhasil disimpan',
        'data' => [
            'id' => $id,
            'nama_tempat' => $nama,
            'no_wa' => $wa,
            'buka_24jam' => $buka24,
            'latitude' => $lat,
            'longitude' => $lng
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $stmt->error]);
}

$stmt->close();
$conn->close();