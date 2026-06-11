<?php
// auth/helper.php — Session management dan RBAC helpers

if (session_status() === PHP_SESSION_NONE) {
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on');

    session_set_cookie_params([
        'lifetime' => 0,
        'httponly' => true,
        'secure' => $is_https,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function is_logged_in(): bool {
    if (empty($_SESSION['user_id'])) return false;
    if (!defined('SESSION_TIMEOUT')) return true;
    $last = $_SESSION['last_activity'] ?? 0;
    if (time() - $last > SESSION_TIMEOUT) {
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = time(); // extend only after passing timeout check
    return true;
}

function require_password_changed(string $redirect = '../auth/change_password.php'): void {
    if (is_logged_in() && !empty($_SESSION['must_change_password'])) {
        header('Location: ' . $redirect);
        exit;
    }
}

function get_role(): string {
    return $_SESSION['role'] ?? 'viewer';
}

function get_user_id(): int {
    return (int) ($_SESSION['user_id'] ?? 0);
}

function get_user_nama(): string {
    return $_SESSION['nama'] ?? '';
}

function get_ibadah_id(): ?int {
    $id = $_SESSION['ibadah_id'] ?? null;
    return $id ? (int)$id : null;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function require_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $session_token = $_SESSION['csrf_token'] ?? '';

    if (!$token || !$session_token || !hash_equals($session_token, $token)) {
        http_response_code(403);
        die(json_encode(['status' => 'error', 'message' => 'Token CSRF tidak valid']));
    }
}

// Hierarki: administrator > operator > viewer
function has_role(string $required): bool {
    $hierarchy = ['viewer' => 0, 'operator' => 1, 'administrator' => 2];
    $current   = get_role();
    return ($hierarchy[$current] ?? 0) >= ($hierarchy[$required] ?? 99);
}

/**
 * Gunakan di API endpoint. Jika tidak login / role kurang, die() dengan JSON error.
 * Contoh: require_auth('operator');
 */
function require_auth(string $min_role = 'viewer'): void {
    if (!is_logged_in()) {
        http_response_code(401);
        die(json_encode(['status' => 'error', 'message' => 'Tidak terautentikasi']));
    }
    if (!has_role($min_role)) {
        http_response_code(403);
        die(json_encode(['status' => 'error', 'message' => 'Akses ditolak']));
    }
}
