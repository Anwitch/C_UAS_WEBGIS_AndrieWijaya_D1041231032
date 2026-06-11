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

$conn->begin_transaction();
try {
    $warga_list = $conn->query(
        "SELECT id, lat, lng FROM penduduk_miskin WHERE is_active=1 AND deleted_at IS NULL"
    );
    if (!$warga_list) {
        throw new RuntimeException('read penduduk failed: ' . $conn->error);
    }

    $updated = 0;
    while ($warga = $warga_list->fetch_assoc()) {
        $p = calc_proximity($conn, (float)$warga['lat'], (float)$warga['lng']);
        $s = $conn->prepare(
            "UPDATE penduduk_miskin SET ibadah_id=?, jarak_m=?, is_blank_spot=? WHERE id=?"
        );
        if (!$s) {
            throw new RuntimeException('prepare recalc failed: ' . $conn->error);
        }
        $s->bind_param('idii', $p['ibadah_id'], $p['jarak_m'], $p['is_blank_spot'], $warga['id']);
        if (!$s->execute()) {
            throw new RuntimeException('recalc update failed: ' . $s->error);
        }
        $s->close();
        $updated++;
    }

    $conn->commit();
    echo json_encode([
        'status'  => 'success',
        'updated' => $updated,
        'message' => "$updated data penduduk berhasil diperbarui",
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    error_log('ibadah/recalculate transaction failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menghitung ulang proximity.']);
}
$conn->close();
