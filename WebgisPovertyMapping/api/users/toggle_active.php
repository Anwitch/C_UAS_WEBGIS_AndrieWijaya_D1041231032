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

$id = (int) ($_POST['id'] ?? 0);
if (!$id) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']); exit; }

if ($id === get_user_id()) {
    echo json_encode(['status' => 'error', 'message' => 'Tidak dapat menonaktifkan akun sendiri']); exit;
}

$target_stmt = $conn->prepare("SELECT role, is_active FROM users WHERE id = ? LIMIT 1");
$target_stmt->bind_param('i', $id);
$target_stmt->execute();
$target = $target_stmt->get_result()->fetch_assoc();
$target_stmt->close();
if (!$target) {
    echo json_encode(['status' => 'error', 'message' => 'Akun tidak ditemukan']); exit;
}

if ($target['role'] === 'administrator' && (int)$target['is_active'] === 1) {
    $admin_stmt = $conn->prepare(
        "SELECT COUNT(*) AS n FROM users WHERE role='administrator' AND is_active=1 AND id <> ?"
    );
    $admin_stmt->bind_param('i', $id);
    $admin_stmt->execute();
    $other_admins = (int)$admin_stmt->get_result()->fetch_assoc()['n'];
    $admin_stmt->close();
    if ($other_admins === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Tidak dapat menonaktifkan administrator aktif terakhir']); exit;
    }
}

$stmt = $conn->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    $s2 = $conn->prepare("SELECT is_active FROM users WHERE id = ?");
    $s2->bind_param('i', $id);
    $s2->execute();
    $r = $s2->get_result()->fetch_assoc();
    $s2->close();
    echo json_encode([
        'status'    => 'success',
        'is_active' => (int)$r['is_active'],
        'message'   => $r['is_active'] ? 'Akun diaktifkan' : 'Akun dinonaktifkan',
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => $stmt->error]);
}
$stmt->close(); $conn->close();
