<?php
// api/jalan/simpan.php — Simpan data jalan baru
header('Content-Type: application/json');
require_once '../../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$nama = trim($_POST['nama_jalan'] ?? '');
$status_jalan = trim($_POST['status_jalan'] ?? '');
$geojson = trim($_POST['geojson'] ?? '');
$panjang = (float) ($_POST['panjang_meter'] ?? 0);

$allowed_status = ['Nasional', 'Provinsi', 'Kabupaten'];

if (!$nama) {
    echo json_encode(['status' => 'error', 'message' => 'Nama jalan tidak boleh kosong']);
    exit;
}

if (!in_array($status_jalan, $allowed_status)) {
    echo json_encode(['status' => 'error', 'message' => 'Status jalan tidak valid']);
    exit;
}

// Validasi JSON
json_decode($geojson);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => 'error', 'message' => 'Data geometri tidak valid']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO data_jalan (nama_jalan, status_jalan, geojson, panjang_meter) VALUES (?, ?, ?, ?)");
$stmt->bind_param('sssd', $nama, $status_jalan, $geojson, $panjang);

if ($stmt->execute()) {
    $id = $conn->insert_id;
    echo json_encode([
        'status' => 'success',
        'message' => 'Data jalan berhasil disimpan',
        'data' => [
            'id' => $id,
            'nama_jalan' => $nama,
            'status_jalan' => $status_jalan,
            'geojson' => $geojson,
            'panjang_meter' => $panjang
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $stmt->error]);
}

$stmt->close();
$conn->close();