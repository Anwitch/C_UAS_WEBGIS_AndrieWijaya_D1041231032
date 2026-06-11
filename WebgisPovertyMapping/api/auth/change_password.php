<?php
// api/auth/change_password.php — POST: ganti password akun sendiri
header('Content-Type: application/json');
require_once '../../config.php';
require_once '../../auth/helper.php';
require_once '../../koneksi.php';

require_auth('viewer');
require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$new_password = $_POST['new_password']     ?? '';
$confirm      = $_POST['confirm_password'] ?? '';

// Validasi policy: min 8 karakter, kombinasi huruf + angka
if (strlen($new_password) < 8) {
    echo json_encode(['status' => 'error', 'message' => 'Password minimal 8 karakter']);
    exit;
}
if (!preg_match('/[a-zA-Z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
    echo json_encode(['status' => 'error', 'message' => 'Password harus mengandung huruf dan angka']);
    exit;
}
if ($new_password !== $confirm) {
    echo json_encode(['status' => 'error', 'message' => 'Konfirmasi password tidak cocok']);
    exit;
}

$hash    = password_hash($new_password, PASSWORD_BCRYPT);
$user_id = get_user_id();

$stmt = $conn->prepare("UPDATE users SET password = ?, must_change_password = 0 WHERE id = ?");
$stmt->bind_param('si', $hash, $user_id);

if ($stmt->execute()) {
    $_SESSION['must_change_password'] = 0;
    echo json_encode(['status' => 'success', 'message' => 'Password berhasil diubah']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan password']);
}

$stmt->close();
$conn->close();
