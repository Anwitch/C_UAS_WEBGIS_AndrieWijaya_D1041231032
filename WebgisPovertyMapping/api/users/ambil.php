<?php
header('Content-Type: application/json');
require_once '../../config.php';
require_once '../../auth/helper.php';
require_once '../../koneksi.php';
require_auth('administrator');

$sql = "
    SELECT u.id, u.username, u.nama_lengkap, u.role, u.ibadah_id,
           u.is_active, u.must_change_password, u.created_at,
           ri.nama AS nama_ibadah
    FROM users u
    LEFT JOIN rumah_ibadah ri ON u.ibadah_id = ri.id AND ri.deleted_at IS NULL
    ORDER BY u.created_at DESC
";
$result = $conn->query($sql);
$data = [];
while ($row = $result->fetch_assoc()) $data[] = $row;

echo json_encode(['status' => 'success', 'data' => $data]);
$conn->close();
