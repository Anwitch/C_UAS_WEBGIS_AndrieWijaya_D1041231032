<?php
header('Content-Type: application/json');
require_once '../../config.php';
require_once '../../auth/helper.php';
require_once '../../koneksi.php';
require_auth('administrator');
require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']); exit;
}

$id = (int) ($_POST['id'] ?? 0);
if (!$id) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']); exit; }

$active_stmt = $conn->prepare("SELECT id FROM rumah_ibadah WHERE id = ? AND deleted_at IS NULL LIMIT 1");
$active_stmt->bind_param('i', $id);
$active_stmt->execute();
$active_row = $active_stmt->get_result()->fetch_assoc();
$active_stmt->close();
if (!$active_row) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan atau sudah dihapus']); exit;
}

// Cek operator yang terkait
$stmt = $conn->prepare(
    "SELECT nama_lengkap FROM users WHERE ibadah_id = ? AND is_active = 1 LIMIT 5"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$ops_result = $stmt->get_result();
$operators  = [];
while ($op = $ops_result->fetch_assoc()) $operators[] = $op['nama_lengkap'];
$stmt->close();

// Jika belum konfirmasi dan ada operator terkait, kembalikan warning
$confirmed = ($_POST['confirmed'] ?? '0') === '1';
if (!$confirmed && count($operators) > 0) {
    echo json_encode([
        'status'    => 'warning',
        'operators' => $operators,
        'message'   => 'Rumah ibadah ini dikaitkan dengan akun Operator: ' . implode(', ', $operators)
                       . '. Operator tidak akan bisa login sampai dikaitkan ke rumah ibadah lain.',
    ]);
    exit;
}

$conn->begin_transaction();
try {
    // Soft delete
    $stmt = $conn->prepare("UPDATE rumah_ibadah SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL");
    if (!$stmt) {
        throw new RuntimeException('prepare delete failed: ' . $conn->error);
    }
    $stmt->bind_param('i', $id);

    if (!$stmt->execute()) {
        throw new RuntimeException('delete failed: ' . $stmt->error);
    }
    $deleted = $stmt->affected_rows > 0;
    $stmt->close();

    if (!$deleted) {
        $conn->rollback();
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan atau sudah dihapus']);
        $conn->close();
        exit;
    }

    $recalc = $conn->query("SELECT id, lat, lng FROM penduduk_miskin WHERE is_active=1 AND deleted_at IS NULL");
    if (!$recalc) {
        throw new RuntimeException('read penduduk failed: ' . $conn->error);
    }
    while ($warga = $recalc->fetch_assoc()) {
        _recalc_proximity($conn, $warga['id'], $warga['lat'], $warga['lng']);
    }
    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Rumah ibadah berhasil dihapus']);
} catch (Throwable $e) {
    $conn->rollback();
    error_log('ibadah/hapus transaction failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus rumah ibadah.']);
}

function _recalc_proximity($conn, $penduduk_id, $lat, $lng) {
    $p = calc_proximity($conn, (float)$lat, (float)$lng);
    $s = $conn->prepare(
        "UPDATE penduduk_miskin SET ibadah_id=?, jarak_m=?, is_blank_spot=? WHERE id=?"
    );
    if (!$s) {
        throw new RuntimeException('prepare recalc failed: ' . $conn->error);
    }
    $s->bind_param('idii', $p['ibadah_id'], $p['jarak_m'], $p['is_blank_spot'], $penduduk_id);
    if (!$s->execute()) {
        throw new RuntimeException('recalc update failed: ' . $s->error);
    }
    $s->close();
}
$conn->close();
