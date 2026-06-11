<?php
header('Content-Type: application/json');
require_once '../../config.php';
require_once '../../auth/helper.php';
require_once '../../koneksi.php';

$is_auth  = is_logged_in();
$is_privileged = $is_auth && has_role('operator');
$is_admin = $is_auth && has_role('administrator');
$public_verif_sql = $is_privileged ? "" : " AND status_verifikasi = 'Terverifikasi'";
$public_verif_pm_sql = $is_privileged ? "" : " AND pm.status_verifikasi = 'Terverifikasi'";
$operator_ibadah_id = null;
$operator_scope_sql = '';
$operator_scope_pm_sql = '';
if ($is_privileged && !$is_admin) {
    $operator_ibadah_id = (int)get_ibadah_id();
    if ($operator_ibadah_id > 0) {
        $operator_scope_sql = " AND ibadah_id = {$operator_ibadah_id}";
        $operator_scope_pm_sql = " AND pm.ibadah_id = {$operator_ibadah_id}";
    } else {
        $operator_scope_sql = " AND 1 = 0";
        $operator_scope_pm_sql = " AND 1 = 0";
    }
}

// ── Statistik dasar (semua role) ────────────────────────────────────────────
$r = $conn->query("
    SELECT
        COUNT(*)                                                              AS total_kk,
        COALESCE(SUM(jumlah_jiwa), 0)                                        AS total_jiwa,
        COALESCE(SUM(CASE WHEN is_blank_spot=1 THEN jumlah_jiwa ELSE 0 END), 0) AS jiwa_blank_spot,
        COALESCE(SUM(CASE WHEN is_blank_spot=0 THEN jumlah_jiwa ELSE 0 END), 0) AS jiwa_terjangkau
    FROM penduduk_miskin pm
    WHERE pm.is_active = 1 AND pm.deleted_at IS NULL {$public_verif_pm_sql} {$operator_scope_pm_sql}
");
$base = $r->fetch_assoc();

$total_jiwa      = (int)$base['total_jiwa'];
$jiwa_terjangkau = (int)$base['jiwa_terjangkau'];
$jiwa_blank_spot = (int)$base['jiwa_blank_spot'];
$pct_cakupan     = $total_jiwa > 0 ? round($jiwa_terjangkau / $total_jiwa * 100, 1) : 0;

// ── Sebaran kategori ────────────────────────────────────────────────────────
$kat_rows = $conn->query("
    SELECT kategori, COUNT(*) AS jml_kk, COALESCE(SUM(jumlah_jiwa),0) AS jml_jiwa
    FROM penduduk_miskin pm
    WHERE pm.is_active=1 AND pm.deleted_at IS NULL {$public_verif_pm_sql} {$operator_scope_pm_sql}
    GROUP BY kategori
");
$kategori = [];
while ($row = $kat_rows->fetch_assoc()) $kategori[] = $row;

// ── Total ibadah aktif ──────────────────────────────────────────────────────
if ($is_privileged && !$is_admin) {
    $total_ibadah = $operator_ibadah_id
        ? (int)$conn->query("SELECT COUNT(*) AS n FROM rumah_ibadah WHERE deleted_at IS NULL AND id = {$operator_ibadah_id}")->fetch_assoc()['n']
        : 0;
} else {
    $total_ibadah = (int)$conn->query(
        "SELECT COUNT(*) AS n FROM rumah_ibadah WHERE deleted_at IS NULL"
    )->fetch_assoc()['n'];
}

$payload = [
    'total_ibadah'    => $total_ibadah,
    'total_kk'        => (int)$base['total_kk'],
    'total_jiwa'      => $total_jiwa,
    'jiwa_terjangkau' => $jiwa_terjangkau,
    'jiwa_blank_spot' => $jiwa_blank_spot,
    'pct_cakupan'     => $pct_cakupan,
    'kategori'        => $kategori,
];

// ── Tabel rekapitulasi per ibadah (admin only) ──────────────────────────────
if ($is_admin) {
    $rekap_rows = $conn->query("
        SELECT
            ri.id,
            ri.nama,
            ri.jenis,
            COUNT(pm.id)                                                           AS jml_kk,
            COALESCE(SUM(pm.jumlah_jiwa), 0)                                      AS jml_jiwa,
            COALESCE(SUM(CASE WHEN pm.status_bantuan='Sudah Ditangani' THEN 1 ELSE 0 END), 0) AS jml_ditangani
        FROM rumah_ibadah ri
        LEFT JOIN penduduk_miskin pm
               ON pm.ibadah_id = ri.id
              AND pm.is_active  = 1
              AND pm.deleted_at IS NULL
        WHERE ri.deleted_at IS NULL
        GROUP BY ri.id
        ORDER BY jml_jiwa DESC
    ");
    $rekap = [];
    while ($row = $rekap_rows->fetch_assoc()) $rekap[] = $row;
    $payload['rekap_ibadah'] = $rekap;
}

// ── Kebutuhan stats (hanya jika tabel sudah ada) ───────────────────────────
$kebutuhan_exists = $conn->query(
    "SELECT 1 FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'kebutuhan' LIMIT 1"
)->num_rows > 0;

$payload['kk_kebutuhan_open'] = 0;

if ($kebutuhan_exists) {
    $payload['kk_kebutuhan_open'] = (int)$conn->query("
        SELECT COUNT(DISTINCT penduduk_id) AS n
        FROM kebutuhan k
        JOIN penduduk_miskin pm ON pm.id = k.penduduk_id
                                AND pm.is_active = 1
                                AND pm.deleted_at IS NULL
                                {$public_verif_pm_sql}
                                {$operator_scope_pm_sql}
        WHERE k.status = 'Belum Terpenuhi'
    ")->fetch_assoc()['n'];

    if ($is_admin) {
        $rekap_kat = $conn->query("
            SELECT kategori,
                COUNT(*)                                                   AS total,
                SUM(CASE WHEN status='Belum Terpenuhi' THEN 1 ELSE 0 END) AS belum,
                SUM(CASE WHEN status='Dalam Proses'    THEN 1 ELSE 0 END) AS proses,
                SUM(CASE WHEN status='Terpenuhi'       THEN 1 ELSE 0 END) AS terpenuhi
            FROM kebutuhan
            GROUP BY kategori ORDER BY belum DESC, kategori ASC
        ");
        $rekap_kebutuhan = [];
        while ($r = $rekap_kat->fetch_assoc()) $rekap_kebutuhan[] = $r;
        $payload['rekap_kebutuhan'] = $rekap_kebutuhan;

        $donatur_exists = $conn->query(
            "SELECT 1 FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'kontak_donatur' LIMIT 1"
        )->num_rows > 0;
        $payload['donatur_unread'] = $donatur_exists
            ? (int)$conn->query("SELECT COUNT(*) AS n FROM kontak_donatur WHERE is_read=0")->fetch_assoc()['n']
            : 0;
    }
}

echo json_encode(['status' => 'success', 'data' => $payload]);
$conn->close();
