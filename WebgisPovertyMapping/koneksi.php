<?php
// koneksi.php — Koneksi database + Haversine helper
if (!defined('DB_HOST')) require_once __DIR__ . '/config.php';

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_error && DB_USER !== (getenv('DB_USER') ?: 'root')) {
    $fallback_user = getenv('DB_USER') ?: 'root';
    $fallback_pass = getenv('DB_PASS') ?: '';
    $conn = @new mysqli(DB_HOST, $fallback_user, $fallback_pass, DB_NAME, DB_PORT);
}

if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    http_response_code(500);
    die(json_encode([
        'status'  => 'error',
        'message' => 'Koneksi database gagal. Hubungi administrator.'
    ]));
}
$conn->set_charset('utf8mb4');

function haversine_distance($lat1, $lon1, $lat2, $lon2) {
    $R    = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a    = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2)**2;
    return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

// PRD-correct proximity: nearest ibadah whose radius covers the point wins;
// among ties (equal distance), ORDER BY id ASC via strict < ensures smallest id.
// jarak_m = distance to nearest-overall ibadah when blank spot.
function calc_proximity(mysqli $conn, float $lat, float $lng): array {
    $res = $conn->query(
        "SELECT id, lat, lng, radius_m FROM rumah_ibadah WHERE deleted_at IS NULL ORDER BY id ASC"
    );
    $nearest_overall_dist = PHP_FLOAT_MAX;
    $best_in_radius       = null;
    $best_in_radius_dist  = PHP_FLOAT_MAX;
    while ($ri = $res->fetch_assoc()) {
        $d = haversine_distance($lat, $lng, (float)$ri['lat'], (float)$ri['lng']);
        if ($d < $nearest_overall_dist) $nearest_overall_dist = $d;
        if ($d <= (float)$ri['radius_m'] && $d < $best_in_radius_dist) {
            $best_in_radius_dist = $d;
            $best_in_radius      = $ri;
        }
    }
    if ($best_in_radius) {
        return [
            'ibadah_id'    => (int)$best_in_radius['id'],
            'jarak_m'      => round($best_in_radius_dist, 2),
            'is_blank_spot' => 0,
        ];
    }
    return [
        'ibadah_id'    => null,
        'jarak_m'      => $nearest_overall_dist < PHP_FLOAT_MAX ? round($nearest_overall_dist, 2) : null,
        'is_blank_spot' => 1,
    ];
}
