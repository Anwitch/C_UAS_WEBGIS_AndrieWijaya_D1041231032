<?php
header('Content-Type: application/json');
require_once '../../config.php';
require_once '../../auth/helper.php';
require_once '../../koneksi.php';

require_auth('operator');
require_csrf();

$penduduk_id = (int)($_POST['penduduk_id'] ?? 0);
$kategori    = trim($_POST['kategori']    ?? '');
$deskripsi   = trim($_POST['deskripsi']   ?? '');

$valid_kategori = ['Sembako','Biaya Sekolah','Biaya Kesehatan','Modal Usaha',
                   'Renovasi Rumah','Perlengkapan Rumah','Pakaian','Lainnya'];

if ($penduduk_id <= 0 || !in_array($kategori, $valid_kategori, true)) {
    echo json_encode(['status' => 'error', 'message' => 'Parameter tidak valid.']);
    exit;
}

// Verifikasi akses warga
$stmt = $conn->prepare(
    "SELECT ibadah_id, status_verifikasi FROM penduduk_miskin
     WHERE id=? AND is_active=1 AND deleted_at IS NULL"
);
$stmt->bind_param('i', $penduduk_id);
$stmt->execute();
$warga = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$warga) {
    echo json_encode(['status' => 'error', 'message' => 'Data warga tidak ditemukan.']);
    exit;
}

if (!has_role('administrator')) {
    $op_ibadah = get_ibadah_id();
    if ($warga['ibadah_id'] === null || (int)$warga['ibadah_id'] !== (int)$op_ibadah) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki akses untuk warga ini.']);
        exit;
    }
}

if ($warga['status_verifikasi'] !== 'Terverifikasi') {
    echo json_encode(['status' => 'error', 'message' => 'Data warga belum terverifikasi']);
    exit;
}

$user_id    = (int)$_SESSION['user_id'];
$deskripsi  = $deskripsi !== '' ? mb_substr($deskripsi, 0, 300) : null;

$stmt = $conn->prepare(
    "INSERT INTO kebutuhan (penduduk_id, kategori, deskripsi, created_by) VALUES (?,?,?,?)"
);
$stmt->bind_param('issi', $penduduk_id, $kategori, $deskripsi, $user_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'id' => $conn->insert_id]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $stmt->error]);
}
$stmt->close();
$conn->close();
