<?php
/**
 * ambil_data.php
 * Mengambil semua data dari tabel lokasi_usaha dan mengembalikannya sebagai JSON
 * Digunakan oleh frontend untuk menampilkan marker di peta saat halaman dimuat
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once 'koneksi.php';

$result = $conn->query(
    "SELECT id, nama_tempat, no_wa, buka_24jam, latitude, longitude
     FROM lokasi_usaha
     ORDER BY id DESC"
);

if (!$result) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Query gagal: ' . $conn->error]);
    exit;
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'id'          => (int)$row['id'],
        'nama_tempat' => $row['nama_tempat'],
        'no_wa'       => $row['no_wa'],
        'buka_24jam'  => (int)$row['buka_24jam'],
        'latitude'    => (float)$row['latitude'],
        'longitude'   => (float)$row['longitude']
    ];
}

echo json_encode([
    'status' => 'success',
    'total'  => count($data),
    'data'   => $data
]);

$conn->close();