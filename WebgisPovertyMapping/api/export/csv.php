<?php
require_once '../../config.php';
require_once '../../auth/helper.php';
require_once '../../koneksi.php';
require_auth('administrator');

$ibadah_id = (int)($_GET['ibadah_id'] ?? 0);
if (!$ibadah_id) {
    http_response_code(400);
    die('ibadah_id diperlukan');
}

$r   = $conn->prepare("SELECT nama FROM rumah_ibadah WHERE id=? AND deleted_at IS NULL");
$r->bind_param('i', $ibadah_id);
$r->execute();
$row = $r->get_result()->fetch_assoc();
$r->close();

if (!$row) {
    http_response_code(404);
    die('Rumah ibadah tidak ditemukan');
}

$nama_ibadah = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $row['nama']);
$tanggal     = date('Y-m-d');
$filename    = "binaan_{$nama_ibadah}_{$tanggal}.csv";

header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"$filename\"");

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");

function csv_safe_cell($value): string {
    $text = (string)($value ?? '');
    $trimmed = ltrim($text);
    if ($trimmed !== '' && in_array($trimmed[0], ['=', '+', '-', '@'], true)) {
        return "'" . $text;
    }
    return $text;
}

fputcsv($out, ['nama_kk', 'jumlah_jiwa', 'kategori', 'alamat', 'status_bantuan', 'catatan']);

$stmt = $conn->prepare("
    SELECT nama_kk, jumlah_jiwa, kategori, alamat, status_bantuan, catatan
    FROM penduduk_miskin
    WHERE ibadah_id=? AND is_active=1 AND deleted_at IS NULL
    ORDER BY nama_kk
");
$stmt->bind_param('i', $ibadah_id);
$stmt->execute();
$res = $stmt->get_result();
while ($w = $res->fetch_assoc()) {
    fputcsv($out, [
        csv_safe_cell($w['nama_kk']),
        csv_safe_cell($w['jumlah_jiwa']),
        csv_safe_cell($w['kategori']),
        csv_safe_cell($w['alamat'] ?? ''),
        csv_safe_cell($w['status_bantuan']),
        csv_safe_cell($w['catatan'] ?? ''),
    ]);
}
$stmt->close();
fclose($out);
$conn->close();
