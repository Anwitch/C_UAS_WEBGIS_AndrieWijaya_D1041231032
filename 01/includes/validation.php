<?php

const MAP_MIN_LAT = -0.25;
const MAP_MAX_LAT = 0.15;
const MAP_MIN_LNG = 109.15;
const MAP_MAX_LNG = 109.55;
const MAX_GEOJSON_BYTES = 1048576;
const MAX_COORDINATE_POINTS = 5000;

function clean_string(string $value, int $maxLength): string
{
    $value = trim($value);
    if (mb_strlen($value, 'UTF-8') > $maxLength) {
        return mb_substr($value, 0, $maxLength, 'UTF-8');
    }
    return $value;
}

function validate_enum(string $value, array $allowed): ?string
{
    return in_array($value, $allowed, true) ? $value : null;
}

function validate_lat_lng($lat, $lng): ?array
{
    if (!is_numeric($lat) || !is_numeric($lng)) {
        return null;
    }

    $lat = (float)$lat;
    $lng = (float)$lng;
    if ($lat < MAP_MIN_LAT || $lat > MAP_MAX_LAT || $lng < MAP_MIN_LNG || $lng > MAP_MAX_LNG) {
        return null;
    }

    return [$lat, $lng];
}

function decode_geojson(string $geojson): ?array
{
    if ($geojson === '' || strlen($geojson) > MAX_GEOJSON_BYTES) {
        return null;
    }

    $decoded = json_decode($geojson, true);
    return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : null;
}

function count_coordinates($coordinates): int
{
    if (!is_array($coordinates)) {
        return 0;
    }

    if (count($coordinates) >= 2 && is_numeric($coordinates[0]) && is_numeric($coordinates[1])) {
        return 1;
    }

    $total = 0;
    foreach ($coordinates as $child) {
        $total += count_coordinates($child);
        if ($total > MAX_COORDINATE_POINTS) {
            return $total;
        }
    }

    return $total;
}

function coordinates_in_bounds($coordinates): bool
{
    if (!is_array($coordinates)) {
        return false;
    }

    if (count($coordinates) >= 2 && is_numeric($coordinates[0]) && is_numeric($coordinates[1])) {
        return validate_lat_lng($coordinates[1], $coordinates[0]) !== null;
    }

    foreach ($coordinates as $child) {
        if (!coordinates_in_bounds($child)) {
            return false;
        }
    }

    return true;
}

function validate_geojson_geometry(array $geometry, array $allowedTypes): bool
{
    $type = $geometry['type'] ?? '';
    if (!in_array($type, $allowedTypes, true)) {
        return false;
    }
    if (!array_key_exists('coordinates', $geometry)) {
        return false;
    }
    if (count_coordinates($geometry['coordinates']) > MAX_COORDINATE_POINTS) {
        return false;
    }
    return coordinates_in_bounds($geometry['coordinates']);
}

function validate_feature_collection(array $geojson, string $attributeKey): bool
{
    if (($geojson['type'] ?? '') !== 'FeatureCollection') {
        return false;
    }

    $features = $geojson['features'] ?? null;
    if (!is_array($features) || count($features) === 0 || count($features) > 1000) {
        return false;
    }

    foreach ($features as $feature) {
        if (($feature['type'] ?? '') !== 'Feature') {
            return false;
        }
        if (!array_key_exists($attributeKey, $feature['properties'] ?? [])) {
            return false;
        }
        if (!validate_geojson_geometry($feature['geometry'] ?? [], ['Polygon', 'MultiPolygon', 'LineString', 'MultiLineString', 'Point', 'MultiPoint'])) {
            return false;
        }
    }

    return true;
}
