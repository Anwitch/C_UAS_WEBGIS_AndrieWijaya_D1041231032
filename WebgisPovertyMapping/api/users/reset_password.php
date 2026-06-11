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

$chars   = 'abcdefghjkmnpqrstuvwxyz';
$digits  = '23456789';
$temp_pw = '';
for ($i = 0; $i < 4; $i++) $temp_pw .= $chars[random_int(0, strlen($chars)-1)];
for ($i = 0; $i < 4; $i++) $temp_pw .= $digits[random_int(0, strlen($digits)-1)];
$temp_pw = str_shuffle($temp_pw);

$hash = password_hash($temp_pw, PASSWORD_BCRYPT);
$stmt = $conn->prepare(
    "UPDATE users SET password=?, must_change_password=1, login_attempts=0, locked_until=NULL WHERE id=?"
);
$stmt->bind_param('si', $hash, $id);

if ($stmt->execute()) {
    echo json_encode([
        'status'        => 'success',
        'temp_password' => $temp_pw,
        'message'       => 'Password direset. Sampaikan password sementara ini ke pengguna secara langsung.',
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => $stmt->error]);
}
$stmt->close(); $conn->close();
