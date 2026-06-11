<?php
/**
 * simpan.php
 * Menerima data POST dari form dan menyimpannya ke tabel lokasi_usaha
 * Mengembalikan respons dalam format JSON
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'koneksi.php';

// Hanya terima metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan.']);
    exit;
}

// Ambil dan sanitasi input
$nama_tempat = trim($_POST['nama_tempat'] ?? '');
$no_wa       = trim($_POST['no_wa'] ?? '');
$buka_24jam  = isset($_POST['buka_24jam']) ? (int)$_POST['buka_24jam'] : 0;
$latitude    = $_POST['latitude']  ?? '';
$longitude   = $_POST['longitude'] ?? '';

// Validasi input wajib
if (empty($nama_tempat) || empty($latitude) || empty($longitude)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Nama tempat, latitude, dan longitude wajib diisi.']);
    exit;
}

// Validasi format angka koordinat
if (!is_numeric($latitude) || !is_numeric($longitude)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Format koordinat tidak valid.']);
    exit;
}

$latitude  = (float)$latitude;
$longitude = (float)$longitude;
$buka_24jam = ($buka_24jam === 1) ? 1 : 0;

// Prepared statement untuk mencegah SQL Injection
$stmt = $conn->prepare(
    "INSERT INTO lokasi_usaha (nama_tempat, no_wa, buka_24jam, latitude, longitude)
     VALUES (?, ?, ?, ?, ?)"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Persiapan query gagal: ' . $conn->error]);
    exit;
}

// bind_param dengan tipe data yang benar
$stmt->bind_param(
    'ssidd',
    $nama_tempat,
    $no_wa,
    $buka_24jam,
    $latitude,
    $longitude
);

if ($stmt->execute()) {
    $new_id = $conn->insert_id;
    echo json_encode([
        'status'  => 'success',
        'message' => 'Data berhasil disimpan.',
        'data'    => [
            'id'          => $new_id,
            'nama_tempat' => $nama_tempat,
            'no_wa'       => $no_wa,
            'buka_24jam'  => $buka_24jam,
            'latitude'    => $latitude,
            'longitude'   => $longitude
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data: ' . $stmt->error]);
}

$stmt->close();
$conn->close();