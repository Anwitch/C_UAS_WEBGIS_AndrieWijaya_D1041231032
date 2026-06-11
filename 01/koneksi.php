<?php
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_01_NAME') ?: (getenv('DB_NAME') ?: 'db_webgis_01'));
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3307));

require_once __DIR__ . '/auth/helper.php';
start_session();

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['status' => 'error', 'message' => 'Koneksi database gagal: ' . $conn->connect_error]));
}

$conn->set_charset('utf8mb4');
