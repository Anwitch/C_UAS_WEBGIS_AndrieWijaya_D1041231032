<?php
header('Content-Type: application/json');
require_once '../../config.php';
require_once '../../auth/helper.php';
require_once '../../includes/validation.php';
require_once '../../koneksi.php';
require_auth('operator');
require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$id  = (int)   ($_POST['id']  ?? 0);
$coord = validate_lat_lng($_POST['lat'] ?? null, $_POST['lng'] ?? null);
if (!$coord['ok']) {
    echo json_encode(['status' => 'error', 'message' => $coord['message']]);
    exit;
}
$lat = $coord['lat'];
$lng = $coord['lng'];

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']);
    exit;
}

if (!has_role('administrator')) {
    $linked_ibadah_id = get_ibadah_id();
    if (!$linked_ibadah_id) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Akses ditolak untuk data warga ini']);
        exit;
    }

    $scope = $conn->prepare(
        "SELECT id FROM penduduk_miskin
         WHERE id = ? AND ibadah_id = ? AND is_active = 1 AND deleted_at IS NULL"
    );
    $scope->bind_param('ii', $id, $linked_ibadah_id);
    $scope->execute();
    $allowed = $scope->get_result()->fetch_assoc();
    $scope->close();

    if (!$allowed) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Akses ditolak untuk data warga ini']);
        exit;
    }
}

// PRD-correct proximity calculation
$p = calc_proximity($conn, $lat, $lng);

if (!has_role('administrator')) {
    if (empty($p['ibadah_id']) || (int)$p['ibadah_id'] !== $linked_ibadah_id) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Posisi baru berada di luar wilayah rumah ibadah operator'
        ]);
        exit;
    }
}

$stmt = $conn->prepare(
    "UPDATE penduduk_miskin
     SET lat = ?, lng = ?, ibadah_id = ?, jarak_m = ?, is_blank_spot = ?
     WHERE id = ? AND deleted_at IS NULL"
);
$stmt->bind_param('ddidii', $lat, $lng, $p['ibadah_id'], $p['jarak_m'], $p['is_blank_spot'], $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode([
        'status'  => 'success',
        'message' => 'Posisi berhasil diperbarui',
        'data'    => [
            'ibadah_id'    => $p['ibadah_id'],
            'jarak_m'      => $p['jarak_m'],
            'is_blank_spot' => $p['is_blank_spot'],
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal update atau data tidak ditemukan']);
}

$stmt->close();
$conn->close();
