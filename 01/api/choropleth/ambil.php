<?php
require_once '../../koneksi.php';
header('Content-Type: application/json');

$result = $conn->query(
    "SELECT id, nama, deskripsi, attribute_key, palette, is_visible, created_at
     FROM choropleth_layers ORDER BY created_at ASC"
);

if ($result === false) {
    error_log('Project 01 choropleth metadata read failed: ' . $conn->error);
    json_error('Gagal memuat layer.', 500);
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

json_success(['data' => $rows, 'total' => count($rows)]);
