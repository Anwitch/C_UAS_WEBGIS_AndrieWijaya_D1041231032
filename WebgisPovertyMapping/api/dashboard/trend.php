<?php
/**
 * api/dashboard/trend.php
 * Hitung tren persen cakupan 6 bulan terakhir.
 * Metode: untuk tiap bulan, query penduduk yang created_at <= akhir bulan itu
 * (kumulatif), lalu hitung % jiwa terjangkau vs total jiwa.
 */
require_once '../../config.php';
require_once '../../auth/helper.php';
require_once '../../koneksi.php';
require_auth('administrator');

header('Content-Type: application/json; charset=utf-8');

// Build last 6 months (oldest first)
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $year  = (int) date('Y');
    $month = (int) date('n') - $i;
    while ($month <= 0) { $month += 12; $year--; }
    while ($month > 12) { $month -= 12; $year++; }

    $last_day = date('Y-m-d', mktime(0, 0, 0, $month + 1, 0, $year)); // last day of month

    $month_names = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
    $months[] = [
        'label'   => $month_names[$month - 1] . ' ' . $year,
        'end_dt'  => $last_day,
    ];
}

$rows = [];
$stmt = $conn->prepare(
    "SELECT
        COALESCE(SUM(jumlah_jiwa), 0) AS total_jiwa,
        COALESCE(SUM(CASE WHEN is_blank_spot = 0 THEN jumlah_jiwa ELSE 0 END), 0) AS jiwa_terjangkau
     FROM penduduk_miskin
     WHERE is_active = 1
       AND deleted_at IS NULL
       AND created_at <= ?"
);

foreach ($months as $m) {
    $end = $m['end_dt'];
    $stmt->bind_param('s', $end);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();

    $total      = (int) ($r['total_jiwa']      ?? 0);
    $terjangkau = (int) ($r['jiwa_terjangkau'] ?? 0);
    $pct        = $total > 0 ? round($terjangkau / $total * 100, 2) : 0;

    $rows[] = [
        'bulan'           => $m['label'],
        'total_jiwa'      => $total,
        'jiwa_terjangkau' => $terjangkau,
        'pct_cakupan'     => $pct,
    ];
}
$stmt->close();

echo json_encode(['status' => 'success', 'data' => $rows]);
