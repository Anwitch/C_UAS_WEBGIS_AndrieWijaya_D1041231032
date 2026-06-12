<?php
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('APP_DB_USER') ?: (getenv('DB_USER') ?: 'root'));
define('DB_PASS', getenv('APP_DB_PASSWORD') ?: (getenv('DB_PASS') ?: ''));
define('DB_NAME', getenv('DB_01_NAME') ?: (getenv('DB_NAME') ?: 'db_webgis_01'));
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3307));

require_once __DIR__ . '/auth/helper.php';
start_session();

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_error && DB_USER !== (getenv('DB_USER') ?: 'root')) {
    $fallback_user = getenv('DB_USER') ?: 'root';
    $fallback_pass = getenv('DB_PASS') ?: '';
    $conn = @new mysqli(DB_HOST, $fallback_user, $fallback_pass, DB_NAME, DB_PORT);
}

if ($conn->connect_error) {
    http_response_code(500);
    error_log('Project 01 database connection failed: ' . $conn->connect_error);
    json_error('Koneksi database gagal. Hubungi administrator.', 500);
}

$conn->set_charset('utf8mb4');
