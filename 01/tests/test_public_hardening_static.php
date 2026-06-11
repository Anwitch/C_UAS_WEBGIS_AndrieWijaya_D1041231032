<?php

$root = dirname(__DIR__);

function read_required(string $relative): string
{
    global $root;
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (!is_file($path)) {
        fwrite(STDERR, "Missing file: {$relative}\n");
        exit(1);
    }
    return file_get_contents($path);
}

function assert_contains_text(string $haystack, string $needle, string $label): void
{
    if (strpos($haystack, $needle) === false) {
        fwrite(STDERR, "FAIL: {$label} must contain {$needle}\n");
        exit(1);
    }
}

function assert_not_contains_text(string $haystack, string $needle, string $label): void
{
    if (strpos($haystack, $needle) !== false) {
        fwrite(STDERR, "FAIL: {$label} must not contain {$needle}\n");
        exit(1);
    }
}

$helper = read_required('auth/helper.php');
assert_contains_text($helper, 'function require_admin_post', 'auth helper admin POST guard');
assert_contains_text($helper, 'function csrf_token', 'auth helper CSRF token');
assert_contains_text($helper, 'function verify_csrf_token', 'auth helper CSRF verification');
assert_contains_text($helper, "'secure' => is_https()", 'session cookie secure when HTTPS');
assert_contains_text($helper, 'session_regenerate_id(true)', 'login/session fixation protection helper');
assert_contains_text($helper, 'SESSION_IDLE_TIMEOUT', 'idle timeout constant');
assert_contains_text($helper, 'LOGIN_MAX_ATTEMPTS', 'login throttling constant');
assert_contains_text($helper, 'function login_throttle_key', 'login throttling key helper');

$validation = read_required('includes/validation.php');
assert_contains_text($validation, 'function validate_lat_lng', 'coordinate validation helper');
assert_contains_text($validation, 'function validate_geojson_geometry', 'GeoJSON validation helper');

$connection = read_required('koneksi.php');
assert_not_contains_text($connection, '$conn->connect_error]', 'connection error should not leak raw details');
assert_contains_text($connection, 'error_log', 'connection errors are logged server-side');

foreach ([
    'api/point/simpan.php',
    'api/point/hapus.php',
    'api/point/update_posisi.php',
    'api/jalan/simpan.php',
    'api/jalan/hapus.php',
    'api/parsil/simpan.php',
    'api/parsil/hapus.php',
    'api/choropleth/upload.php',
    'api/choropleth/toggle.php',
    'api/choropleth/hapus.php',
] as $endpoint) {
    $source = read_required($endpoint);
    assert_contains_text($source, 'require_admin_post();', "{$endpoint} mutation guard");
}

foreach (['simpan.php', 'hapus.php', 'update_posisi.php'] as $legacy) {
    $source = read_required($legacy);
    assert_contains_text($source, 'http_response_code(410);', "{$legacy} disabled status");
}

$index = read_required('index.php');
assert_contains_text($index, 'window._CSRF_TOKEN', 'index exposes CSRF token');

foreach ([
    'modules/point.js',
    'modules/jalan.js',
    'modules/parsil.js',
    'modules/choropleth.js',
] as $module) {
    $source = read_required($module);
    assert_contains_text($source, "fd.append('csrf_token'", "{$module} sends CSRF token");
}

$pointModule = read_required('modules/point.js');
assert_contains_text($pointModule, 'draggable: window._IS_ADMIN', 'POI marker draggable only for admin');
assert_contains_text($pointModule, 'window._IS_ADMIN ? `<button class="btn-hapus"', 'POI delete button admin-only');

$jalanModule = read_required('modules/jalan.js');
assert_contains_text($jalanModule, 'window._IS_ADMIN ? `<button class="btn-hapus"', 'road delete button admin-only');

$parsilModule = read_required('modules/parsil.js');
assert_contains_text($parsilModule, 'window._IS_ADMIN ? `<button class="btn-hapus"', 'parcel delete button admin-only');

$choroplethModule = read_required('modules/choropleth.js');
assert_not_contains_text($choroplethModule, 'deleteLayer(${m.id},\'${safeNama}\')', 'choropleth avoids unsafe inline layer name');

$htaccess = read_required('.htaccess');
assert_contains_text($htaccess, 'setup_database\.sql', 'SQL setup file blocked');
assert_contains_text($htaccess, 'tests', 'tests directory blocked');
assert_contains_text($htaccess, 'Header always set X-Frame-Options', 'security headers configured');

$apache = read_required('../docker/apache-webgis.conf');
assert_contains_text($apache, '<FilesMatch', 'Apache file blocking configured');
assert_contains_text($apache, 'Header always set X-Content-Type-Options', 'Apache security headers configured');

$schema = read_required('setup_database.sql');
assert_not_contains_text($schema, 'Default: admin / password', 'schema must not advertise public default password');

$connection = read_required('koneksi.php');
assert_contains_text($connection, "getenv('APP_DB_USER')", 'connection supports app DB user env');

echo "PASS: public hardening static guards are configured\n";
