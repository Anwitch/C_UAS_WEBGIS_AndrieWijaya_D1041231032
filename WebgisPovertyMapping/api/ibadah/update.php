<?php
header('Content-Type: application/json');
require_once '../../config.php';
require_once '../../auth/helper.php';
require_once '../../includes/validation.php';
require_once '../../koneksi.php';
require_auth('administrator');
require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']); exit;
}

$id     = (int)   ($_POST['id']       ?? 0);
$nama   = trim(    $_POST['nama']      ?? '');
$jenis  = trim(    $_POST['jenis']     ?? 'Masjid');
$alamat = trim(    $_POST['alamat']    ?? '');
$kontak = trim(    $_POST['kontak']    ?? '');
$coord = validate_lat_lng($_POST['lat'] ?? null, $_POST['lng'] ?? null);
if (!$coord['ok']) {
    echo json_encode(['status' => 'error', 'message' => $coord['message']]);
    $conn->close(); exit;
}
$lat    = $coord['lat'];
$lng    = $coord['lng'];
$radius = (int)   ($_POST['radius_m'] ?? 500);

$valid_jenis = ['Masjid', 'Mushola', 'Gereja', 'Pura', 'Vihara', 'Klenteng'];

if (!$id)                             { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']); exit; }
if (!$nama)                           { echo json_encode(['status' => 'error', 'message' => 'Nama wajib diisi']); exit; }
if (!in_array($jenis, $valid_jenis))  { echo json_encode(['status' => 'error', 'message' => 'Jenis tidak valid']); exit; }
if ($radius < 100 || $radius > 5000) { echo json_encode(['status' => 'error', 'message' => 'Radius minimum 100 meter, maksimum 5.000 meter']); exit; }

$active_stmt = $conn->prepare("SELECT id FROM rumah_ibadah WHERE id = ? AND deleted_at IS NULL LIMIT 1");
$active_stmt->bind_param('i', $id);
$active_stmt->execute();
$active_row = $active_stmt->get_result()->fetch_assoc();
$active_stmt->close();
if (!$active_row) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan atau sudah dihapus']);
    $conn->close(); exit;
}

$conn->begin_transaction();
try {
    $stmt = $conn->prepare(
        "UPDATE rumah_ibadah SET nama=?, jenis=?, alamat=?, kontak=?, lat=?, lng=?, radius_m=? WHERE id=? AND deleted_at IS NULL"
    );
    if (!$stmt) {
        throw new RuntimeException('prepare update failed: ' . $conn->error);
    }
    $stmt->bind_param('ssssddii', $nama, $jenis, $alamat, $kontak, $lat, $lng, $radius, $id);
    if (!$stmt->execute()) {
        throw new RuntimeException('update failed: ' . $stmt->error);
    }
    $changed = $stmt->affected_rows > 0;
    $stmt->close();

    // Recalculate proximity for all active penduduk (radius may have changed)
    if ($changed) {
        $warga = $conn->query("SELECT id, lat, lng FROM penduduk_miskin WHERE is_active=1 AND deleted_at IS NULL");
        if (!$warga) {
            throw new RuntimeException('read penduduk failed: ' . $conn->error);
        }
        while ($w = $warga->fetch_assoc()) {
            if ($w['lat'] === null || $w['lng'] === null) continue;
            $p = calc_proximity($conn, (float)$w['lat'], (float)$w['lng']);
            $s = $conn->prepare("UPDATE penduduk_miskin SET ibadah_id=?, jarak_m=?, is_blank_spot=? WHERE id=?");
            if (!$s) {
                throw new RuntimeException('prepare recalc failed: ' . $conn->error);
            }
            $s->bind_param('idii', $p['ibadah_id'], $p['jarak_m'], $p['is_blank_spot'], $w['id']);
            if (!$s->execute()) {
                throw new RuntimeException('recalc update failed: ' . $s->error);
            }
            $s->close();
        }
    }

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Rumah ibadah berhasil diperbarui']);
} catch (Throwable $e) {
    $conn->rollback();
    error_log('ibadah/update transaction failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memproses perubahan rumah ibadah.']);
}
$conn->close();
