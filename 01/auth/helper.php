<?php
function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['lifetime' => 0, 'httponly' => true, 'samesite' => 'Strict']);
        session_start();
    }
}

function is_admin(): bool {
    start_session();
    return !empty($_SESSION['admin_id']);
}

function require_admin_json(): void {
    if (!is_admin()) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Silahkan login terlebih dahulu.']);
        exit;
    }
}
