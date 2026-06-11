<?php
header('Content-Type: application/json');
require_once '../../config.php';
require_once '../../auth/helper.php';
require_once '../../koneksi.php';
require_auth('administrator');
require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']); exit;
}

$id           = (int) ($_POST['id']           ?? 0);
$nama_lengkap = trim($_POST['nama_lengkap']   ?? '');
$role         = trim($_POST['role']           ?? '');
$ibadah_id    = ($_POST['ibadah_id'] ?? '') !== '' ? (int)$_POST['ibadah_id'] : null;

if (!$id || !$nama_lengkap || !in_array($role, ['administrator','operator','viewer'])) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']); exit;
}
if ($role === 'operator' && !$ibadah_id) {
    echo json_encode(['status' => 'error', 'message' => 'Operator harus dikaitkan ke rumah ibadah']); exit;
}
if ($role !== 'operator') {
    $ibadah_id = null;
}

$current_stmt = $conn->prepare("SELECT role, is_active FROM users WHERE id = ? LIMIT 1");
$current_stmt->bind_param('i', $id);
$current_stmt->execute();
$current = $current_stmt->get_result()->fetch_assoc();
$current_stmt->close();
if (!$current) {
    echo json_encode(['status' => 'error', 'message' => 'Akun tidak ditemukan']); exit;
}

if ($id === get_user_id() && $current['role'] === 'administrator' && $role !== 'administrator') {
    echo json_encode(['status' => 'error', 'message' => 'Tidak dapat menurunkan role akun sendiri']); exit;
}

if ($current['role'] === 'administrator' && (int)$current['is_active'] === 1 && $role !== 'administrator') {
    $admin_stmt = $conn->prepare(
        "SELECT COUNT(*) AS n FROM users WHERE role='administrator' AND is_active=1 AND id <> ?"
    );
    $admin_stmt->bind_param('i', $id);
    $admin_stmt->execute();
    $other_admins = (int)$admin_stmt->get_result()->fetch_assoc()['n'];
    $admin_stmt->close();
    if ($other_admins === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Tidak dapat mengubah administrator aktif terakhir']); exit;
    }
}

if ($role === 'operator') {
    $ri_stmt = $conn->prepare("SELECT id FROM rumah_ibadah WHERE id = ? AND deleted_at IS NULL LIMIT 1");
    $ri_stmt->bind_param('i', $ibadah_id);
    $ri_stmt->execute();
    $ri = $ri_stmt->get_result()->fetch_assoc();
    $ri_stmt->close();
    if (!$ri) {
        echo json_encode(['status' => 'error', 'message' => 'Rumah ibadah operator tidak valid atau sudah dihapus']); exit;
    }
}

$stmt = $conn->prepare("UPDATE users SET nama_lengkap=?, role=?, ibadah_id=? WHERE id=?");
$stmt->bind_param('ssii', $nama_lengkap, $role, $ibadah_id, $id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Akun berhasil diperbarui']);
} else {
    echo json_encode(['status' => 'error', 'message' => $stmt->error]);
}
$stmt->close(); $conn->close();
