<?php
// api/parsil/simpan.php — Simpan data parsil tanah baru
header('Content-Type: application/json');
require_once '../../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$nama = trim($_POST['nama_parsil'] ?? '');
$status = trim($_POST['status_kepemilikan'] ?? '');
$geojson = trim($_POST['geojson'] ?? '');
$luas = (float) ($_POST['luas_m2'] ?? 0);

$allowed_status = ['SHM', 'HGB', 'HGU', 'HP'];

if (!$nama) {
    echo json_encode(['status' => 'error', 'message' => 'Nama parsil tidak boleh kosong']);
    exit;
}

if (!in_array($status, $allowed_status)) {
    echo json_encode(['status' => 'error', 'message' => 'Status kepemilikan tidak valid']);
    exit;
}

json_decode($geojson);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => 'error', 'message' => 'Data geometri tidak valid']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO data_parsil (nama_parsil, status_kepemilikan, geojson, luas_m2) VALUES (?, ?, ?, ?)");
$stmt->bind_param('sssd', $nama, $status, $geojson, $luas);

if ($stmt->execute()) {
    $id = $conn->insert_id;
    echo json_encode([
        'status' => 'success',
        'message' => 'Data parsil berhasil disimpan',
        'data' => [
            'id' => $id,
            'nama_parsil' => $nama,
            'status_kepemilikan' => $status,
            'geojson' => $geojson,
            'luas_m2' => $luas
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $stmt->error]);
}

$stmt->close();
$conn->close();