<?php
// Shared validation helpers for request payloads.

function normalize_coordinate_value($value): array {
    if ($value === null) {
        return ['ok' => false, 'message' => 'Koordinat tidak boleh kosong'];
    }

    $raw = is_string($value)
        ? trim(str_replace([',', "\xe2\x88\x92"], ['.', '-'], $value))
        : $value;

    if ($raw === '') {
        return ['ok' => false, 'message' => 'Koordinat tidak boleh kosong'];
    }

    if (!is_numeric($raw)) {
        return ['ok' => false, 'message' => 'Koordinat harus berupa angka'];
    }

    $float = (float)$raw;
    if (!is_finite($float)) {
        return ['ok' => false, 'message' => 'Koordinat tidak valid'];
    }

    return ['ok' => true, 'value' => $float];
}

function validate_lat_lng($lat, $lng, $strict_bounds = true): array {
    $lat_result = normalize_coordinate_value($lat);
    if (!$lat_result['ok']) {
        return ['ok' => false, 'message' => 'Latitude: ' . $lat_result['message']];
    }

    $lng_result = normalize_coordinate_value($lng);
    if (!$lng_result['ok']) {
        return ['ok' => false, 'message' => 'Longitude: ' . $lng_result['message']];
    }

    $lat_value = $lat_result['value'];
    $lng_value = $lng_result['value'];

    if ($lat_value < -90 || $lat_value > 90) {
        return ['ok' => false, 'message' => 'Latitude di luar rentang -90 sampai 90'];
    }
    if ($lng_value < -180 || $lng_value > 180) {
        return ['ok' => false, 'message' => 'Longitude di luar rentang -180 sampai 180'];
    }

    if (
        $strict_bounds
        && defined('MAP_MIN_LAT') && defined('MAP_MAX_LAT')
        && defined('MAP_MIN_LNG') && defined('MAP_MAX_LNG')
        && (
            $lat_value < MAP_MIN_LAT || $lat_value > MAP_MAX_LAT
            || $lng_value < MAP_MIN_LNG || $lng_value > MAP_MAX_LNG
        )
    ) {
        return ['ok' => false, 'message' => 'Koordinat di luar wilayah studi'];
    }

    return ['ok' => true, 'lat' => $lat_value, 'lng' => $lng_value];
}
