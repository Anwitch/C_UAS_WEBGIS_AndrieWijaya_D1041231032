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

$nama_kk     = trim($_POST['nama_kk']     ?? '');
$nik         = trim($_POST['nik']         ?? '');
$jumlah_jiwa = max(1, (int)($_POST['jumlah_jiwa'] ?? 1));
$kategori    = trim($_POST['kategori']    ?? 'Miskin');
$alamat      = trim($_POST['alamat']      ?? '');
$catatan     = trim($_POST['catatan']     ?? '');
$coord = validate_lat_lng($_POST['lat'] ?? null, $_POST['lng'] ?? null);
if (!$coord['ok']) {
    echo json_encode(['status' => 'error', 'message' => $coord['message']]);
    exit;
}
$lat = $coord['lat'];
$lng = $coord['lng'];

if (!$nama_kk) {
    echo json_encode(['status' => 'error', 'message' => 'Nama Kepala Keluarga tidak boleh kosong']);
    exit;
}

$valid_kategori = ['Sangat Miskin', 'Miskin', 'Hampir Miskin'];
if (!in_array($kategori, $valid_kategori)) $kategori = 'Miskin';

// Validasi & dedup NIK
$nik_val = $nik !== '' ? $nik : null;
if ($nik_val !== null) {
    if (!preg_match('/^\d{16}$/', $nik_val)) {
        echo json_encode(['status' => 'error', 'message' => 'NIK harus 16 digit angka']);
        exit;
    }
    $stmt_nik = $conn->prepare(
        "SELECT id, nama_kk FROM penduduk_miskin
         WHERE nik = ? LIMIT 1"
    );
    $stmt_nik->bind_param('s', $nik_val);
    $stmt_nik->execute();
    $existing = $stmt_nik->get_result()->fetch_assoc();
    $stmt_nik->close();
    if ($existing) {
        echo json_encode([
            'status'  => 'error',
            'message' => "NIK {$nik_val} pernah terdaftar atas nama {$existing['nama_kk']}. Cek arsip sebelum menambah ulang.",
        ]);
        exit;
    }
}

// PRD-correct proximity calculation
$p = calc_proximity($conn, $lat, $lng);

if (!has_role('administrator')) {
    $op_ibadah_id = get_ibadah_id();
    if (!$op_ibadah_id) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Operator belum dikaitkan dengan rumah ibadah'
        ]);
        exit;
    }
    if (empty($p['ibadah_id']) || (int)$p['ibadah_id'] !== $op_ibadah_id) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Lokasi warga berada di luar wilayah rumah ibadah operator'
        ]);
        exit;
    }
}

// Admin langsung Terverifikasi; operator masuk Pending untuk direview
$status_verif = has_role('administrator') ? 'Terverifikasi' : 'Pending';
$verified_by  = has_role('administrator') ? get_user_id()  : null;
$verified_at  = has_role('administrator') ? date('Y-m-d H:i:s') : null;

$stmt = $conn->prepare(
    "INSERT INTO penduduk_miskin
        (nama_kk, nik, jumlah_jiwa, kategori, alamat, catatan, lat, lng,
         ibadah_id, jarak_m, is_blank_spot, status_verifikasi, verified_by, verified_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param(
    'ssisssddidisis',
    $nama_kk, $nik_val, $jumlah_jiwa, $kategori, $alamat, $catatan,
    $lat, $lng, $p['ibadah_id'], $p['jarak_m'], $p['is_blank_spot'],
    $status_verif, $verified_by, $verified_at
);

if ($stmt->execute()) {
    $new_id = $conn->insert_id;

    $nama_ibadah  = null;
    $jenis_ibadah = null;
    if ($p['ibadah_id']) {
        $s2 = $conn->prepare("SELECT nama, jenis FROM rumah_ibadah WHERE id = ? AND deleted_at IS NULL");
        $s2->bind_param('i', $p['ibadah_id']);
        $s2->execute();
        $ri = $s2->get_result()->fetch_assoc();
        $s2->close();
        if ($ri) { $nama_ibadah = $ri['nama']; $jenis_ibadah = $ri['jenis']; }
    }

    echo json_encode([
        'status'  => 'success',
        'message' => 'Data berhasil disimpan',
        'data'    => [
            'id'            => $new_id,
            'nama_kk'       => $nama_kk,
            'nik'           => $nik_val,
            'kategori'      => $kategori,
            'alamat'        => $alamat,
            'catatan'       => $catatan,
            'jumlah_jiwa'   => $jumlah_jiwa,
            'lat'           => $lat,
            'lng'           => $lng,
            'ibadah_id'          => $p['ibadah_id'],
            'jarak_m'            => $p['jarak_m'],
            'is_blank_spot'      => $p['is_blank_spot'],
            'status_verifikasi'  => $status_verif,
            'nama_ibadah'        => $nama_ibadah,
            'jenis_ibadah'       => $jenis_ibadah,
        ]
    ]);
} else {
    error_log('penduduk/simpan insert failed: ' . $stmt->error);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data penduduk.']);
}

$stmt->close();
$conn->close();
