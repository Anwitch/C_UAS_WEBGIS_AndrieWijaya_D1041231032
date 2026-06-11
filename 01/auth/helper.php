<?php

const SESSION_IDLE_TIMEOUT = 1800;
const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCK_SECONDS = 300;

function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    return ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

function clear_session_cookie(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        return;
    }

    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'] ?? '/',
        'domain' => $params['domain'] ?? '',
        'secure' => (bool)($params['secure'] ?? false),
        'httponly' => true,
        'samesite' => $params['samesite'] ?? 'Strict',
    ]);
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => is_https(),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }

    $now = time();
    $lastSeen = (int)($_SESSION['last_seen_at'] ?? $now);
    if (($now - $lastSeen) > SESSION_IDLE_TIMEOUT) {
        $_SESSION = [];
        clear_session_cookie();
        session_destroy();
        session_start();
    }
    $_SESSION['last_seen_at'] = $now;
}

function harden_successful_login(): void
{
    start_session();
    session_regenerate_id(true);
    $_SESSION['last_seen_at'] = time();
}

function is_admin(): bool
{
    start_session();
    return !empty($_SESSION['admin_id']);
}

function login_throttle_key(string $username): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return hash('sha256', strtolower(trim($username)) . '|' . $ip);
}

function login_is_throttled(string $username): bool
{
    start_session();
    $entry = $_SESSION['login_throttle'][login_throttle_key($username)] ?? null;
    if (!is_array($entry)) {
        return false;
    }
    return (int)($entry['locked_until'] ?? 0) > time();
}

function login_record_failure(string $username): void
{
    start_session();
    $key = login_throttle_key($username);
    $entry = $_SESSION['login_throttle'][$key] ?? ['count' => 0, 'locked_until' => 0];
    $entry['count'] = (int)$entry['count'] + 1;
    if ($entry['count'] >= LOGIN_MAX_ATTEMPTS) {
        $entry['locked_until'] = time() + LOGIN_LOCK_SECONDS;
        $entry['count'] = 0;
    }
    $_SESSION['login_throttle'][$key] = $entry;
}

function login_record_success(string $username): void
{
    start_session();
    unset($_SESSION['login_throttle'][login_throttle_key($username)]);
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool
{
    start_session();
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function json_error(string $message, int $statusCode = 400): void
{
    json_response(['status' => 'error', 'message' => $message], $statusCode);
}

function json_success(array $payload = []): void
{
    json_response(['status' => 'success'] + $payload);
}

function require_admin_json(): void
{
    if (!is_admin()) {
        json_error('Akses ditolak. Silahkan login terlebih dahulu.', 403);
    }
}

function require_admin_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_error('Method tidak valid.', 405);
    }
    require_admin_json();
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!verify_csrf_token($token)) {
        json_error('Token keamanan tidak valid.', 403);
    }
}
