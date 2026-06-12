<?php

mysqli_report(MYSQLI_REPORT_OFF);

function env_value(string $name, string $default = ''): string
{
    $value = getenv($name);
    return $value === false ? $default : $value;
}

function check_database(string $label, string $database, ?string $user = null, ?string $password = null): array
{
    $host = env_value('DB_HOST', 'localhost');
    $port = (int)env_value('DB_PORT', '3307');
    $username = $user ?? env_value('DB_USER', 'root');
    $dbPassword = $password ?? env_value('DB_PASS', '');

    $connection = @new mysqli($host, $username, $dbPassword, $database, $port);

    if ($connection->connect_error && $username !== env_value('DB_USER', 'root')) {
        $username = env_value('DB_USER', 'root');
        $dbPassword = env_value('DB_PASS', '');
        $connection = @new mysqli($host, $username, $dbPassword, $database, $port);
    }

    if ($connection->connect_error) {
        error_log("Health check database failed ({$label}): " . $connection->connect_error);
        return [
            'name' => $label,
            'status' => 'error',
        ];
    }

    $ok = $connection->query('SELECT 1') !== false;
    if (!$ok) {
        error_log("Health check query failed ({$label}): " . $connection->error);
    }

    $connection->close();

    return [
        'name' => $label,
        'status' => $ok ? 'ok' : 'error',
    ];
}

$project01User = env_value('APP_DB_USER') ?: env_value('DB_USER', 'root');
$project01Pass = env_value('APP_DB_PASSWORD') ?: env_value('DB_PASS', '');

$checks = [
    check_database('project_01', env_value('DB_01_NAME', 'db_webgis_01'), $project01User, $project01Pass),
    check_database('poverty_mapping', env_value('DB_WEBGIS_NAME', 'db_webgis')),
];

$healthy = true;
foreach ($checks as $check) {
    if ($check['status'] !== 'ok') {
        $healthy = false;
        break;
    }
}

http_response_code($healthy ? 200 : 500);
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'status' => $healthy ? 'ok' : 'error',
    'checks' => $checks,
], JSON_UNESCAPED_SLASHES);

if (PHP_SAPI === 'cli') {
    echo PHP_EOL;
    exit($healthy ? 0 : 1);
}
