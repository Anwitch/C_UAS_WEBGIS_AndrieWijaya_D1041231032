<?php
header('Content-Type: application/json');
require_once '../../config.php';
require_once '../../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$now = time();
$rate_window = 60;
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rate_dir = dirname(__DIR__, 2) . '/tmp/donor-rate-limit';
if (!is_dir($rate_dir)) {
    @mkdir($rate_dir, 0775, true);
}
$rate_file = $rate_dir . '/' . hash('sha256', $client_ip) . '.txt';
$last_submit = max(
    (int)($_SESSION['donor_last_submit'] ?? 0),
    is_file($rate_file) ? (int)filemtime($rate_file) : 0
);
if ($last_submit > 0 && ($now - $last_submit) < $rate_window) {
    http_response_code(429);
    echo json_encode([
        'status' => 'error',
        'message' => 'Terlalu sering mengirim. Coba lagi dalam 1 menit.',
    ]);
    exit;
}

$nama           = trim($_POST['nama']           ?? '');
$kontak         = trim($_POST['kontak']         ?? '');
$kategori_minat = trim($_POST['kategori_minat'] ?? '');
$pesan          = trim($_POST['pesan']          ?? '');

if (!$nama || !$kontak) {
    echo json_encode(['status' => 'error', 'message' => 'Nama dan kontak wajib diisi.']);
    exit;
}

$valid_kategori = ['Sembako','Biaya Sekolah','Biaya Kesehatan','Modal Usaha',
                   'Renovasi Rumah','Perlengkapan Rumah','Pakaian','Lainnya'];
$kat = in_array($kategori_minat, $valid_kategori, true) ? $kategori_minat : null;
$pesan_val  = $pesan  !== '' ? mb_substr($pesan,  0, 1000) : null;
$nama_safe  = mb_substr($nama,   0, 150);
$kontak_safe = mb_substr($kontak, 0, 150);

$stmt = $conn->prepare(
    "INSERT INTO kontak_donatur (nama, kontak, kategori_minat, pesan) VALUES (?,?,?,?)"
);
$stmt->bind_param('ssss', $nama_safe, $kontak_safe, $kat, $pesan_val);

if ($stmt->execute()) {
    $_SESSION['donor_last_submit'] = time();
    @file_put_contents($rate_file, (string)$_SESSION['donor_last_submit'], LOCK_EX);
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan.']);
}
$stmt->close();
$conn->close();
