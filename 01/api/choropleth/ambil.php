<?php
require_once '../../koneksi.php';
header('Content-Type: application/json');

$result = $conn->query(
    "SELECT id, nama, deskripsi, attribute_key, palette, is_visible, created_at
     FROM choropleth_layers ORDER BY created_at ASC"
);

if ($result === false) {
    echo json_encode(['status' => 'error', 'message' => 'Tabel belum ada. Jalankan setup_database.sql terlebih dahulu. (' . $conn->error . ')']);
    $conn->close(); exit;
}

$rows = [];
while ($r = $result->fetch_assoc()) {
    $rows[] = [
        'id'            => (int)$r['id'],
        'nama'          => $r['nama'],
        'deskripsi'     => $r['deskripsi'],
        'attribute_key' => $r['attribute_key'],
        'palette'       => $r['palette'],
        'is_visible'    => (bool)$r['is_visible'],
        'created_at'    => $r['created_at'],
    ];
}
$conn->close();

echo json_encode(['status' => 'success', 'data' => $rows, 'total' => count($rows)]);
