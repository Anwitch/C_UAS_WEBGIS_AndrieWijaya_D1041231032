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

$username     = trim($_POST['username']     ?? '');
$nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
$password     = trim($_POST['password']     ?? '');
$role         = trim($_POST['role']         ?? '');
$ibadah_id    = ($_POST['ibadah_id'] ?? '') !== '' ? (int)$_POST['ibadah_id'] : null;

$valid_roles = ['administrator', 'operator', 'viewer'];
if (!$username || !$nama_lengkap || !$password || !in_array($role, $valid_roles)) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap atau role tidak valid']); exit;
}
if (strlen($password) < 8 || !preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
    echo json_encode(['status' => 'error', 'message' => 'Password min 8 karakter, kombinasi huruf dan angka']); exit;
}
if ($role === 'operator' && !$ibadah_id) {
    echo json_encode(['status' => 'error', 'message' => 'Operator harus dikaitkan ke rumah ibadah']); exit;
}
if ($role !== 'operator') {
    $ibadah_id = null;
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

$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $conn->prepare(
    "INSERT INTO users (username, nama_lengkap, password, role, ibadah_id, must_change_password)
     VALUES (?, ?, ?, ?, ?, 1)"
);
$stmt->bind_param('ssssi', $username, $nama_lengkap, $hash, $role, $ibadah_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Akun berhasil dibuat', 'id' => $conn->insert_id]);
} else {
    $msg = str_contains($conn->error, 'Duplicate') ? "Username '$username' sudah digunakan" : $conn->error;
    echo json_encode(['status' => 'error', 'message' => $msg]);
}
$stmt->close(); $conn->close();
