<?php
header('Content-Type: application/json');
require_once '../../config.php';
require_once '../../auth/helper.php';
require_once '../../includes/validation.php';
require_once '../../koneksi.php';
require_auth('administrator');
require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$mode     = trim($_POST['mode'] ?? 'preview'); // 'preview' | 'import'
$max_rows = 500;

$file = $_FILES['csv_file'] ?? null;
if (!$file || empty($file['tmp_name'])) {
    echo json_encode(['status' => 'error', 'message' => 'File CSV tidak ditemukan']);
    exit;
}

if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Upload CSV gagal']);
    exit;
}

$max_bytes = 2 * 1024 * 1024;
if (($file['size'] ?? 0) <= 0 || $file['size'] > $max_bytes) {
    echo json_encode(['status' => 'error', 'message' => 'Ukuran CSV maksimal 2 MB']);
    exit;
}

$ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
if ($ext !== 'csv') {
    echo json_encode(['status' => 'error', 'message' => 'File harus berformat .csv']);
    exit;
}

$handle = fopen($file['tmp_name'], 'r');
if (!$handle) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal membuka file']);
    exit;
}

// Deteksi dan lewati BOM UTF-8
$bom = fread($handle, 3);
if ($bom !== "\xEF\xBB\xBF") rewind($handle);

$header = fgetcsv($handle);
if (!$header) {
    echo json_encode(['status' => 'error', 'message' => 'File kosong atau format salah']);
    exit;
}

$header = array_map(fn($h) => strtolower(trim($h)), $header);
$required_cols = ['nama_kk', 'jumlah_jiwa', 'kategori', 'lat', 'lng'];
foreach ($required_cols as $col) {
    if (!in_array($col, $header)) {
        echo json_encode([
            'status'  => 'error',
            'message' => "Kolom '$col' tidak ditemukan. Gunakan template yang disediakan.",
        ]);
        exit;
    }
}

$col = array_flip($header);

$rows_ok    = [];
$rows_error = [];
$row_num    = 1;

while (($raw = fgetcsv($handle)) !== false) {
    $row_num++;
    if ($row_num > $max_rows + 1) {
        $rows_error[] = ['row' => $row_num, 'error' => 'Batas 500 baris terlampaui'];
        continue;
    }
    if (count($raw) < count($header)) {
        $rows_error[] = ['row' => $row_num, 'error' => 'Jumlah kolom tidak sesuai'];
        continue;
    }

    $get = fn($name) => isset($col[$name]) ? trim($raw[$col[$name]] ?? '') : '';

    $nama_kk     = $get('nama_kk');
    $nik         = $get('nik');
    $jumlah_jiwa = max(1, (int)$get('jumlah_jiwa'));
    $kategori    = $get('kategori');
    $alamat      = $get('alamat');
    $catatan     = $get('catatan');

    $lat_raw = $get('lat');
    $lng_raw = $get('lng');

    $errors = [];
    if (!$nama_kk)                     $errors[] = 'nama_kk kosong';

    $coord = validate_lat_lng($lat_raw, $lng_raw);
    if (!$coord['ok']) {
        $errors[] = $coord['message'];
    } else {
        $lat = $coord['lat'];
        $lng = $coord['lng'];
    }

    $valid_kat = ['Sangat Miskin', 'Miskin', 'Hampir Miskin'];
    if (!in_array($kategori, $valid_kat)) {
        if ($kategori === '') $kategori = 'Miskin';
        else $errors[] = "kategori '$kategori' tidak valid";
    }

    if ($nik !== '' && !preg_match('/^\d{16}$/', $nik)) {
        $errors[] = 'NIK harus 16 digit jika diisi';
    }

    if ($errors) {
        $rows_error[] = ['row' => $row_num, 'nama_kk' => $nama_kk, 'error' => implode('; ', $errors)];
        continue;
    }

    $rows_ok[] = compact('nama_kk', 'nik', 'jumlah_jiwa', 'kategori', 'alamat', 'catatan', 'lat', 'lng');
}
fclose($handle);

if ($mode === 'preview') {
    echo json_encode([
        'status'      => 'success',
        'total_ok'    => count($rows_ok),
        'total_error' => count($rows_error),
        'preview'     => array_slice($rows_ok, 0, 10),
        'errors'      => $rows_error,
    ]);
    exit;
}

// Mode import: INSERT semua baris valid
$imported = 0;
$skipped  = 0;
$status_verif = 'Terverifikasi';
$verified_by = get_user_id();
$verified_at = date('Y-m-d H:i:s');

foreach ($rows_ok as $r) {
    // Cek duplikat NIK pada semua data, termasuk arsip/inactive.
    if ($r['nik'] !== '') {
        $chk = $conn->prepare(
            "SELECT id FROM penduduk_miskin WHERE nik=? LIMIT 1"
        );
        $chk->bind_param('s', $r['nik']);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $skipped++;
            $chk->close();
            continue;
        }
        $chk->close();
    }

    // PRD-correct proximity (sama dengan simpan.php)
    $p = calc_proximity($conn, $r['lat'], $r['lng']);

    $nik_val = $r['nik'] !== '' ? $r['nik'] : null;
    $stmt = $conn->prepare(
        "INSERT INTO penduduk_miskin
           (nama_kk, nik, jumlah_jiwa, kategori, alamat, catatan, lat, lng, ibadah_id, jarak_m, is_blank_spot,
            status_verifikasi, verified_by, verified_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        'ssisssddidisis',
        $r['nama_kk'], $nik_val, $r['jumlah_jiwa'], $r['kategori'],
        $r['alamat'], $r['catatan'], $r['lat'], $r['lng'],
        $p['ibadah_id'], $p['jarak_m'], $p['is_blank_spot'],
        $status_verif, $verified_by, $verified_at
    );
    if ($stmt->execute()) $imported++;
    $stmt->close();
}

echo json_encode([
    'status'   => 'success',
    'imported' => $imported,
    'skipped'  => $skipped,
    'message'  => "$imported baris berhasil diimport sebagai Terverifikasi, $skipped dilewati (NIK pernah terdaftar/duplikat)",
]);
$conn->close();
