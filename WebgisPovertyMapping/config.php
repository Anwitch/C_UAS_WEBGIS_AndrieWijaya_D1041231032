<?php
// config.php — Konfigurasi aplikasi WebGIS Poverty Mapping
// Edit CENTER_LAT, CENTER_LNG, ZOOM_LEVEL untuk wilayah baru

define('APP_NAME',    'WebGIS Poverty Mapping');
define('CENTER_LAT',  -0.0557);
define('CENTER_LNG',  109.3487);
define('ZOOM_LEVEL',  15);

define('MAP_MIN_LAT', -0.35);
define('MAP_MAX_LAT',  0.20);
define('MAP_MIN_LNG', 109.15);
define('MAP_MAX_LNG', 109.60);

define('SESSION_TIMEOUT',    7200);   // 2 jam dalam detik
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_MINUTES',    15);

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('APP_DB_USER') ?: (getenv('DB_USER') ?: 'root'));
define('DB_PASS', getenv('APP_DB_PASSWORD') ?: (getenv('DB_PASS') ?: ''));
define('DB_NAME', getenv('DB_WEBGIS_NAME') ?: (getenv('DB_NAME') ?: 'db_webgis'));
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3307));
