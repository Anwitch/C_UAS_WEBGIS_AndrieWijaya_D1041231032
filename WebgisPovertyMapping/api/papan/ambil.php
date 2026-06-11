<?php
header('Content-Type: application/json');
require_once '../../config.php';
require_once '../../koneksi.php';

// Publik — tidak perlu auth
// Agregasi kebutuhan Belum Terpenuhi per kategori × rumah ibadah (area)

$rows = $conn->query("
    SELECT
        k.kategori,
        COALESCE(ri.nama, 'Di luar radius ibadah') AS area,
        COUNT(DISTINCT k.penduduk_id)               AS jumlah_kk
    FROM kebutuhan k
    JOIN penduduk_miskin pm ON pm.id = k.penduduk_id
                            AND pm.is_active = 1
                            AND pm.deleted_at IS NULL
                            AND pm.status_verifikasi = 'Terverifikasi'
    LEFT JOIN rumah_ibadah ri ON ri.id = pm.ibadah_id AND ri.deleted_at IS NULL
    WHERE k.status = 'Belum Terpenuhi'
    GROUP BY k.kategori, pm.ibadah_id, ri.nama
    ORDER BY jumlah_kk DESC, k.kategori ASC
");

$data = [];
while ($r = $rows->fetch_assoc()) {
    $data[] = [
        'kategori'  => $r['kategori'],
        'area'      => $r['area'],
        'jumlah_kk' => (int)$r['jumlah_kk'],
    ];
}

echo json_encode(['status' => 'success', 'data' => $data]);
$conn->close();
