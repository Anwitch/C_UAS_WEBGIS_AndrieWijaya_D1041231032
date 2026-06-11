<?php
header('Content-Type: application/json');
require_once '../../config.php';
require_once '../../auth/helper.php';
require_once '../../koneksi.php';

require_auth('operator');
require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$id          = (int)($_POST['id']          ?? 0);
$nama_kk     = trim($_POST['nama_kk']     ?? '');
$nik         = trim($_POST['nik']         ?? '');
$jumlah_jiwa = max(1, (int)($_POST['jumlah_jiwa'] ?? 1));
$kategori    = trim($_POST['kategori']    ?? 'Miskin');
$alamat      = trim($_POST['alamat']      ?? '');
$catatan     = trim($_POST['catatan']     ?? '');

if ($id <= 0 || !$nama_kk) {
    echo json_encode(['status' => 'error', 'message' => 'ID dan nama kepala keluarga wajib diisi.']);
    exit;
}

$valid_kategori = ['Sangat Miskin', 'Miskin', 'Hampir Miskin'];
if (!in_array($kategori, $valid_kategori)) $kategori = 'Miskin';

// Ambil data existing untuk scope check
$stmt = $conn->prepare(
    "SELECT ibadah_id FROM penduduk_miskin WHERE id=? AND is_active=1 AND deleted_at IS NULL"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$warga = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$warga) {
    echo json_encode(['status' => 'error', 'message' => 'Data warga tidak ditemukan.']);
    exit;
}

// Operator hanya boleh edit warga dalam ibadah-nya
if (!has_role('administrator')) {
    $op_ibadah = get_ibadah_id();
    if ($warga['ibadah_id'] === null || (int)$warga['ibadah_id'] !== (int)$op_ibadah) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki akses untuk warga ini.']);
        exit;
    }
}

// Validasi & dedup NIK (exclude diri sendiri)
$nik_val = $nik !== '' ? $nik : null;
if ($nik_val !== null) {
    if (!preg_match('/^\d{16}$/', $nik_val)) {
        echo json_encode(['status' => 'error', 'message' => 'NIK harus 16 digit angka.']);
        exit;
    }
    $stmt_nik = $conn->prepare(
        "SELECT id, nama_kk FROM penduduk_miskin WHERE nik=? AND id != ? LIMIT 1"
    );
    $stmt_nik->bind_param('si', $nik_val, $id);
    $stmt_nik->execute();
    $existing = $stmt_nik->get_result()->fetch_assoc();
    $stmt_nik->close();
    if ($existing) {
        echo json_encode([
            'status'  => 'error',
            'message' => "NIK {$nik_val} sudah terdaftar atas nama {$existing['nama_kk']}.",
        ]);
        exit;
    }
}

$catatan_val = $catatan !== '' ? $catatan : null;

$stmt = $conn->prepare(
    "UPDATE penduduk_miskin
     SET nama_kk=?, nik=?, jumlah_jiwa=?, kategori=?, alamat=?, catatan=?, updated_at=NOW()
     WHERE id=? AND is_active=1 AND deleted_at IS NULL"
);
$stmt->bind_param('ssisssi', $nama_kk, $nik_val, $jumlah_jiwa, $kategori, $alamat, $catatan_val, $id);

if ($stmt->execute() && $stmt->affected_rows >= 0) {
    echo json_encode(['status' => 'success', 'message' => 'Data berhasil diperbarui.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui data: ' . $stmt->error]);
}
$stmt->close();
$conn->close();
