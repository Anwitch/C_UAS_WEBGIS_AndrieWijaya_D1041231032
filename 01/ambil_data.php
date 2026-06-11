<?php
/**
 * ambil_data.php
 * Mengambil semua data dari tabel lokasi_usaha dan mengembalikannya sebagai JSON
 * Digunakan oleh frontend untuk menampilkan marker di peta saat halaman dimuat
 */

header('Content-Type: application/json');
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
    error_log('Project 01 legacy point read failed: ' . $conn->error);
    json_error('Gagal memuat data.', 500);
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
