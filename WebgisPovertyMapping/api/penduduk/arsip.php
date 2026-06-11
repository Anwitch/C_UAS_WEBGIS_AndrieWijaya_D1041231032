<?php
header('Content-Type: application/json');
require_once '../../config.php';
require_once '../../auth/helper.php';
require_once '../../koneksi.php';

require_auth('administrator');

// Return inactive (is_active=0) AND soft-deleted warga for admin audit
$stmt = $conn->prepare("
    SELECT pm.id, pm.nama_kk, pm.jumlah_jiwa, pm.kategori, pm.status_bantuan,
           pm.is_active, pm.deleted_at,
           ri.nama AS nama_ibadah,
           (SELECT COUNT(*) FROM riwayat_bantuan WHERE penduduk_id = pm.id) AS jml_riwayat
    FROM penduduk_miskin pm
    LEFT JOIN rumah_ibadah ri ON ri.id = pm.ibadah_id
    WHERE pm.is_active = 0 OR pm.deleted_at IS NOT NULL
    ORDER BY COALESCE(pm.deleted_at, pm.updated_at) DESC
    LIMIT 200
");
$stmt->execute();
$res  = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;
$stmt->close();

echo json_encode(['status' => 'success', 'data' => $rows]);
$conn->close();
