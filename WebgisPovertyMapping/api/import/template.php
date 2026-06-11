<?php
require_once '../../config.php';
require_once '../../auth/helper.php';
require_auth('administrator');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="template_penduduk_miskin.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 agar Excel membaca dengan benar

fputcsv($out, ['nama_kk', 'nik', 'jumlah_jiwa', 'kategori', 'alamat', 'catatan', 'lat', 'lng']);
fputcsv($out, ['Budi Santoso',  '3271011234567890', '4', 'Miskin',        'Jl. Merdeka No. 1',  'Rumah semi permanen', '-0.0557', '109.3487']);
fputcsv($out, ['Siti Rahayu',   '',                 '2', 'Sangat Miskin', 'Jl. Damai No. 5',    '',                    '-0.0560', '109.3490']);
fclose($out);
