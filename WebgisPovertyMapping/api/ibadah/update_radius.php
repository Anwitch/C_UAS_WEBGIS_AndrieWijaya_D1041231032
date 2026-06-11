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

$id     = (int) ($_POST['id']       ?? 0);
$radius = (int) ($_POST['radius_m'] ?? 500);

if (!$id) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']); exit; }

if ($radius < 100 || $radius > 5000) {
    echo json_encode(['status' => 'error', 'message' => 'Radius minimum 100 meter, maksimum 5.000 meter']); exit;
}

$active_stmt = $conn->prepare("SELECT id FROM rumah_ibadah WHERE id = ? AND deleted_at IS NULL LIMIT 1");
$active_stmt->bind_param('i', $id);
$active_stmt->execute();
$active_row = $active_stmt->get_result()->fetch_assoc();
$active_stmt->close();
if (!$active_row) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan atau sudah dihapus']);
    exit;
}

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("UPDATE rumah_ibadah SET radius_m = ? WHERE id = ? AND deleted_at IS NULL");
    if (!$stmt) {
        throw new RuntimeException('prepare update failed: ' . $conn->error);
    }
    $stmt->bind_param('ii', $radius, $id);

    if (!$stmt->execute()) {
        throw new RuntimeException('update failed: ' . $stmt->error);
    }
    $changed = $stmt->affected_rows > 0;
    $stmt->close();

    // Recalculate proximity for all active penduduk
    if ($changed) {
        $warga_list = $conn->query(
            "SELECT id, lat, lng FROM penduduk_miskin WHERE is_active=1 AND deleted_at IS NULL"
        );
        if (!$warga_list) {
            throw new RuntimeException('read penduduk failed: ' . $conn->error);
        }
        while ($warga = $warga_list->fetch_assoc()) {
            $p = calc_proximity($conn, (float)$warga['lat'], (float)$warga['lng']);
            $s = $conn->prepare("UPDATE penduduk_miskin SET ibadah_id=?, jarak_m=?, is_blank_spot=? WHERE id=?");
            if (!$s) {
                throw new RuntimeException('prepare recalc failed: ' . $conn->error);
            }
            $s->bind_param('idii', $p['ibadah_id'], $p['jarak_m'], $p['is_blank_spot'], $warga['id']);
            if (!$s->execute()) {
                throw new RuntimeException('recalc update failed: ' . $s->error);
            }
            $s->close();
        }
    }

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Radius berhasil diperbarui', 'radius_m' => $radius]);
} catch (Throwable $e) {
    $conn->rollback();
    error_log('ibadah/update_radius transaction failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memproses perubahan radius.']);
}
$conn->close();
