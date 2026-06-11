<?php
require_once '../../config.php';
require_once '../../auth/helper.php';
require_once '../../koneksi.php';
require_auth('administrator');

$ibadah_id = (int)($_GET['ibadah_id'] ?? 0);
if (!$ibadah_id) { http_response_code(400); die('ibadah_id diperlukan'); }

$r  = $conn->prepare("SELECT * FROM rumah_ibadah WHERE id=? AND deleted_at IS NULL");
$r->bind_param('i', $ibadah_id);
$r->execute();
$ib = $r->get_result()->fetch_assoc();
$r->close();
if (!$ib) { http_response_code(404); die('Tidak ditemukan'); }

$s = $conn->prepare("
    SELECT COUNT(*) AS jml_kk,
           COALESCE(SUM(jumlah_jiwa), 0) AS jml_jiwa,
           COALESCE(SUM(CASE WHEN status_bantuan='Sudah Ditangani' THEN 1 ELSE 0 END), 0) AS ditangani
    FROM penduduk_miskin
    WHERE ibadah_id=? AND is_active=1 AND deleted_at IS NULL
");
$s->bind_param('i', $ibadah_id);
$s->execute();
$st = $s->get_result()->fetch_assoc();
$s->close();

$pct = $st['jml_kk'] > 0 ? round($st['ditangani'] / $st['jml_kk'] * 100) : 0;

$w  = $conn->prepare("
    SELECT nama_kk, jumlah_jiwa, kategori, status_bantuan
    FROM penduduk_miskin
    WHERE ibadah_id=? AND is_active=1 AND deleted_at IS NULL
    ORDER BY nama_kk
");
$w->bind_param('i', $ibadah_id);
$w->execute();
$warga_res  = $w->get_result();
$warga_list = [];
while ($row = $warga_res->fetch_assoc()) $warga_list[] = $row;
$w->close();
$conn->close();

$tanggal = date('d F Y');
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Binaan — <?= htmlspecialchars($ib['nama']) ?></title>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family: Arial, sans-serif; font-size: 12px; color: #111; padding: 20px; }
  .header { border-bottom: 2px solid #333; margin-bottom: 16px; padding-bottom: 10px; }
  .header h1 { font-size: 16px; margin-bottom: 4px; }
  .header p  { font-size: 11px; color: #555; margin-top: 2px; }
  .cards { display: flex; gap: 16px; margin-bottom: 16px; }
  .card  { border: 1px solid #ddd; border-radius: 6px; padding: 10px 16px; text-align: center; flex: 1; }
  .card .val { font-size: 22px; font-weight: 700; color: #1a1a1a; }
  .card .lbl { font-size: 10px; color: #777; margin-top: 2px; }
  table { width: 100%; border-collapse: collapse; }
  th, td { border: 1px solid #ddd; padding: 6px 10px; text-align: left; font-size: 11px; }
  th { background: #f0f0f0; font-weight: 700; }
  tr:nth-child(even) td { background: #fafafa; }
  .status-done    { color: #166534; font-weight: 700; }
  .status-process { color: #92400e; font-weight: 700; }
  .status-none    { color: #6b7280; }
  .footer { margin-top: 20px; font-size: 10px; color: #999; text-align: right; }
  @media print { body { padding: 0; } }
</style>
</head>
<body>
<div class="header">
    <h1>Laporan Warga Binaan — <?= htmlspecialchars($ib['nama']) ?></h1>
    <p><?= htmlspecialchars($ib['jenis']) ?> · <?= htmlspecialchars($ib['alamat'] ?? '-') ?></p>
    <p>Digenerate: <?= $tanggal ?></p>
</div>
<div class="cards">
    <div class="card"><div class="val"><?= $st['jml_kk']  ?></div><div class="lbl">Total KK</div></div>
    <div class="card"><div class="val"><?= $st['jml_jiwa']?></div><div class="lbl">Total Jiwa</div></div>
    <div class="card"><div class="val"><?= $pct ?>%</div>         <div class="lbl">Sudah Ditangani</div></div>
</div>
<table>
    <thead>
        <tr><th>#</th><th>Nama KK</th><th>Jiwa</th><th>Kategori</th><th>Status Bantuan</th></tr>
    </thead>
    <tbody>
    <?php foreach ($warga_list as $i => $warga): ?>
        <?php $cls = match($warga['status_bantuan']) {
            'Sudah Ditangani' => 'status-done',
            'Dalam Proses'    => 'status-process',
            default           => 'status-none',
        }; ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($warga['nama_kk']) ?></td>
            <td><?= $warga['jumlah_jiwa'] ?></td>
            <td><?= $warga['kategori'] ?></td>
            <td class="<?= $cls ?>"><?= $warga['status_bantuan'] ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<div class="footer"><?= APP_NAME ?> · <?= $tanggal ?></div>
<script>window.onload = () => window.print();</script>
</body>
</html>
