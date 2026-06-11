<?php
header('Content-Type: application/json');
require_once '../../config.php';
require_once '../../auth/helper.php';
require_once '../../koneksi.php';

$is_authenticated = is_logged_in();
$is_privileged = $is_authenticated && has_role('operator');
$role             = $is_authenticated ? get_role() : 'viewer';
$is_admin         = $is_authenticated && has_role('administrator');
$can_see_sensitive = $is_privileged;
$can_see_coords    = $is_privileged;
$public_verif_sql = $is_privileged ? "" : " AND pm.status_verifikasi = 'Terverifikasi'";

// Publik: hanya data Terverifikasi
// Operator: semua data ibadahnya sendiri (semua status)
// Admin: semua data tanpa filter
$operator_scope_sql = '';

if ($is_privileged && !$is_admin) {
    $linked_ibadah_id = get_ibadah_id();
    $operator_scope_sql = $linked_ibadah_id
        ? ' AND pm.ibadah_id = ' . (int)$linked_ibadah_id
        : ' AND 1 = 0';
    // operator lihat semua status miliknya (termasuk Pending/Ditolak)
}

// Cek apakah tabel kebutuhan sudah ada (migrasi F14 sudah dijalankan)
$kebutuhan_exists = $conn->query(
    "SELECT 1 FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'kebutuhan' LIMIT 1"
)->num_rows > 0;

if ($kebutuhan_exists) {
    $sql = "
        SELECT
            pm.id, pm.nama_kk, pm.nik, pm.kategori, pm.alamat, pm.catatan,
            pm.jumlah_jiwa, pm.lat, pm.lng, pm.ibadah_id, pm.jarak_m,
            pm.is_blank_spot, pm.status_bantuan, pm.status_verifikasi, pm.created_at,
            ri.nama  AS nama_ibadah,
            ri.jenis AS jenis_ibadah,
            COALESCE(kstat.kebutuhan_open,     0) AS kebutuhan_open,
            COALESCE(kstat.kebutuhan_proses,   0) AS kebutuhan_proses,
            COALESCE(kstat.kebutuhan_terpenuhi,0) AS kebutuhan_terpenuhi,
            kstat.kebutuhan_kategori_open
        FROM penduduk_miskin pm
        LEFT JOIN rumah_ibadah ri ON pm.ibadah_id = ri.id AND ri.deleted_at IS NULL
        LEFT JOIN (
            SELECT
                penduduk_id,
                SUM(CASE WHEN status='Belum Terpenuhi' THEN 1 ELSE 0 END) AS kebutuhan_open,
                SUM(CASE WHEN status='Dalam Proses'    THEN 1 ELSE 0 END) AS kebutuhan_proses,
                SUM(CASE WHEN status='Terpenuhi'       THEN 1 ELSE 0 END) AS kebutuhan_terpenuhi,
                GROUP_CONCAT(DISTINCT CASE WHEN status='Belum Terpenuhi' THEN kategori ELSE NULL END
                             ORDER BY kategori SEPARATOR ',') AS kebutuhan_kategori_open
            FROM kebutuhan
            GROUP BY penduduk_id
        ) kstat ON kstat.penduduk_id = pm.id
        WHERE pm.is_active = 1 AND pm.deleted_at IS NULL {$public_verif_sql} {$operator_scope_sql}
        ORDER BY pm.created_at DESC
    ";
} else {
    $sql = "
        SELECT
            pm.id, pm.nama_kk, pm.nik, pm.kategori, pm.alamat, pm.catatan,
            pm.jumlah_jiwa, pm.lat, pm.lng, pm.ibadah_id, pm.jarak_m,
            pm.is_blank_spot, pm.status_bantuan, pm.status_verifikasi, pm.created_at,
            ri.nama  AS nama_ibadah,
            ri.jenis AS jenis_ibadah,
            0 AS kebutuhan_open, 0 AS kebutuhan_proses,
            0 AS kebutuhan_terpenuhi, NULL AS kebutuhan_kategori_open
        FROM penduduk_miskin pm
        LEFT JOIN rumah_ibadah ri ON pm.ibadah_id = ri.id AND ri.deleted_at IS NULL
        WHERE pm.is_active = 1 AND pm.deleted_at IS NULL {$public_verif_sql} {$operator_scope_sql}
        ORDER BY pm.created_at DESC
    ";
}

$result = $conn->query($sql);

if (!$result) {
    error_log('penduduk/ambil query failed: ' . $conn->error);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memuat data penduduk.']);
    exit;
}

$data = [];
while ($row = $result->fetch_assoc()) {
    if (!$can_see_sensitive) {
        // Publik tidak boleh lihat data pribadi
        $row['nik']     = null;
        $row['nama_kk'] = null;
        $row['alamat']  = null;
        $row['catatan'] = null;
    }
    if (!$can_see_coords) {
        // Publik tidak boleh lihat koordinat rumah warga
        $row['lat'] = null;
        $row['lng'] = null;
    }
    $data[] = $row;
}

echo json_encode(['status' => 'success', 'total' => count($data), 'data' => $data]);
$conn->close();
