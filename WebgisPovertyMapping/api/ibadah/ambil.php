<?php
header('Content-Type: application/json');
require_once '../../config.php';
require_once '../../auth/helper.php';
require_once '../../koneksi.php';

// Public viewer boleh akses — tidak perlu require_auth

// Hanya tampilkan warga yang sudah Terverifikasi di agregat publik
$sql = "
    SELECT ri.id, ri.nama, ri.jenis, ri.alamat, ri.lat, ri.lng,
           ri.radius_m, ri.kontak, ri.created_at, ri.updated_at,
           COUNT(pm.id)                           AS total_kk,
           COALESCE(SUM(pm.jumlah_jiwa), 0)       AS total_jiwa,
           COALESCE(SUM(CASE WHEN pm.is_blank_spot = 0 THEN pm.jumlah_jiwa ELSE 0 END), 0) AS jiwa_terjangkau,
           COALESCE(SUM(CASE WHEN pm.is_blank_spot = 1 THEN pm.jumlah_jiwa ELSE 0 END), 0) AS jiwa_blankspot
    FROM rumah_ibadah ri
    LEFT JOIN penduduk_miskin pm
           ON pm.ibadah_id        = ri.id
          AND pm.is_active        = 1
          AND pm.deleted_at       IS NULL
          AND pm.status_verifikasi = 'Terverifikasi'
    WHERE ri.deleted_at IS NULL
    GROUP BY ri.id
    ORDER BY ri.created_at DESC
";

$result = $conn->query($sql);
$data   = [];
while ($row = $result->fetch_assoc()) $data[] = $row;

echo json_encode(['status' => 'success', 'total' => count($data), 'data' => $data]);
$conn->close();
