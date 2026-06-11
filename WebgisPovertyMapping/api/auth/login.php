<?php
// api/auth/login.php — POST: validasi kredensial + buat sesi
header('Content-Type: application/json');
require_once '../../config.php';
require_once '../../auth/helper.php';
require_once '../../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (!$username || !$password) {
    echo json_encode(['status' => 'error', 'message' => 'Username dan password wajib diisi']);
    exit;
}

// Ambil user dari DB (prepared statement — SQL injection safe)
$stmt = $conn->prepare(
    "SELECT id, nama_lengkap, password, role, ibadah_id, is_active,
            must_change_password, login_attempts, locked_until
     FROM users WHERE username = ? LIMIT 1"
);
$stmt->bind_param('s', $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'Username atau password salah']);
    exit;
}

if (!$user['is_active']) {
    echo json_encode(['status' => 'error', 'message' => 'Akun dinonaktifkan. Hubungi Administrator.']);
    exit;
}

// Cek lockout
if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
    $menit = ceil((strtotime($user['locked_until']) - time()) / 60);
    echo json_encode(['status' => 'error', 'message' => "Akun terkunci. Coba lagi dalam {$menit} menit."]);
    exit;
}

// Verifikasi password
if (!password_verify($password, $user['password'])) {
    $attempts = $user['login_attempts'] + 1;

    if ($attempts >= MAX_LOGIN_ATTEMPTS) {
        $locked_until = date('Y-m-d H:i:s', time() + LOCKOUT_MINUTES * 60);
        $stmt = $conn->prepare("UPDATE users SET login_attempts = ?, locked_until = ? WHERE id = ?");
        $stmt->bind_param('isi', $attempts, $locked_until, $user['id']);
    } else {
        $stmt = $conn->prepare("UPDATE users SET login_attempts = ? WHERE id = ?");
        $stmt->bind_param('ii', $attempts, $user['id']);
    }
    $stmt->execute();
    $stmt->close();

    $sisa = MAX_LOGIN_ATTEMPTS - $attempts;
    $msg  = $sisa > 0
        ? "Username atau password salah. Sisa percobaan: {$sisa}"
        : "Akun terkunci " . LOCKOUT_MINUTES . " menit karena terlalu banyak percobaan gagal.";
    echo json_encode(['status' => 'error', 'message' => $msg]);
    exit;
}

// Login berhasil — reset attempts
$stmt = $conn->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = ?");
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$stmt->close();

// Rotate session ID sebelum menulis data (cegah session fixation)
session_regenerate_id(true);

// Buat sesi
$_SESSION['user_id']             = $user['id'];
$_SESSION['username']            = $username;
$_SESSION['nama']                = $user['nama_lengkap'];
$_SESSION['role']                = $user['role'];
$_SESSION['ibadah_id']           = $user['ibadah_id'];
$_SESSION['must_change_password'] = (int)$user['must_change_password'];
$_SESSION['last_activity']       = time();

// Redirect target: admin → dashboard, operator/viewer → map
$redirect = ($user['role'] === 'administrator') ? '../dashboard.php' : '../index.php';

echo json_encode([
    'status'   => 'success',
    'redirect' => $redirect,
    'data'     => [
        'user_id'             => $user['id'],
        'nama'                => $user['nama_lengkap'],
        'role'                => $user['role'],
        'ibadah_id'           => $user['ibadah_id'],
        'must_change_password'=> (bool)$user['must_change_password'],
    ]
]);

$conn->close();
