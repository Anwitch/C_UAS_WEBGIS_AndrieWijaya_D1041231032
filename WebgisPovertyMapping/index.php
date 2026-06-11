<?php
require_once 'config.php';
require_once 'auth/helper.php';

require_password_changed('auth/change_password.php');

$role = is_logged_in() ? get_role() : 'viewer';
$user_nama = is_logged_in() ? ($_SESSION['nama'] ?? 'Pengguna') : null;
$is_admin = ($role === 'administrator');
$is_op = ($role === 'operator');
$logged_in = is_logged_in();
$embedded = (($_GET['embedded'] ?? '') === '1');
if ($embedded) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

$map_modules = [
    'modules/ibadah.js',
    'modules/penduduk.js',
    'modules/stats.js',
    'modules/heatmap.js',
];
if ($logged_in) {
    $map_modules[] = 'modules/kebutuhan.js';
}
$map_module_urls = array_map(function ($module) {
    $version = file_exists(__DIR__ . '/' . $module) ? filemtime(__DIR__ . '/' . $module) : time();
    return $module . '?v=' . $version;
}, $map_modules);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap"
        rel="stylesheet">
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        /* ── Reset & Base ─────────────────────────────── */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            /* Warm Earth tokens */
            --c-bg: #fafaf9;
            --c-surface: #ede8e2;
            --c-surface2: #e5dfd8;
            --c-border: #ddd8d2;
            --c-text: #201515;
            --c-muted: #7a7067;
            --c-accent: #0d7490;
            --c-accent-h: #0a5f7a;
            --c-danger: #ef4444;
            --c-warn: #f59e0b;
            --c-info: #3b82f6;
            --radius: 12px;
            --shadow: 0 4px 12px rgba(32,21,21,.08);
            --font-body: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --font-mono: var(--font-body);
            /* Sidebar — stays dark as sole dark surface */
            --sb-bg: #1c1612;
            --sb-border: rgba(255,255,255,.06);
            --sb-text: #a89f96;
            --sb-text-hover: #ffffff;
            --sb-active: #0d7490;
        }

        html,
        body {
            height: 100%;
            font-family: var(--font-body);
            background: var(--c-bg);
            color: var(--c-text);
            overflow: hidden;
        }

        .app-layout {
            display: flex;
            flex-direction: row;
            height: 100vh;
            overflow: hidden;
        }

        /* ── Sidebar ─────────────────────────────────────── */
        .sidebar {
            width: 320px;
            min-width: 320px;
            background: var(--sb-bg);
            border-right: 1px solid var(--sb-border);
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                min-width 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                border-width 0.15s;
            z-index: 100;
            flex-shrink: 0;
        }

        .sidebar.collapsed {
            width: 0;
            min-width: 0;
            border-right: none;
        }

        .sidebar.collapsed>* {
            display: none !important;
        }

        .sb-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            border-bottom: 1px solid var(--sb-border);
            background: rgba(0, 0, 0, .2);
            flex-shrink: 0;
            gap: 8px;
        }

        .sb-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            overflow: hidden;
            min-width: 0;
        }

        .sb-logo {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            background: #0d7490;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
            box-shadow: none;
        }

        .sb-title {
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #ffffff;
        }

        .sb-subtitle {
            font-size: 10px;
            color: var(--sb-text);
            white-space: nowrap;
        }

        .sb-header-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .sb-toggle-btn {
            width: 28px;
            height: 28px;
            background: rgba(255, 255, 255, .05);
            border: 1px solid var(--sb-border);
            border-radius: 7px;
            color: var(--sb-text);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            transition: all 0.2s;
            flex-shrink: 0;
        }

        .sb-toggle-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--sb-text-hover);
        }

        .sb-open-btn {
            position: absolute;
            top: 50%;
            left: 0;
            transform: translateY(-50%);
            width: 24px;
            height: 56px;
            background: var(--c-surface);
            border: 1px solid var(--c-border);
            border-left: none;
            border-radius: 0 10px 10px 0;
            color: var(--c-accent-h);
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            z-index: 1100;
            transition: width 0.2s, background 0.2s, color 0.2s;
            box-shadow: 2px 0 8px rgba(0, 0, 0, .4);
        }

        .sb-open-btn:hover {
            color: var(--c-text);
            background: var(--c-surface2);
            width: 30px;
        }

        .sidebar.collapsed~.map-area .sb-open-btn {
            display: flex;
        }

        /* ── Sidebar User ─────────────────────────────── */
        .sb-user {
            padding: 10px 14px;
            border-bottom: 1px solid var(--sb-border);
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
            background: rgba(0, 0, 0, .15);
            min-height: 54px;
        }

        .sb-user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            flex-shrink: 0;
        }

        .sb-user-info {
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }

        .sb-user-name-text {
            font-size: 13px;
            font-weight: 600;
            color: #ffffff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.3;
        }

        .sb-user-role-text {
            font-size: 10px;
            color: var(--sb-text);
            margin-top: 2px;
        }

        .sb-login-btn {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            background: #0d7490;
            border: none;
            border-radius: 8px;
            color: #ffffff;
            font-size: 12px;
            font-weight: 600;
            font-family: var(--font-body);
            text-decoration: none;
            transition: background .2s, border-color .2s;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .sb-login-btn:hover {
            background: #0a5f7a;
            color: #ffffff;
        }

        /* ── User dropdown ──────────────────────────────── */
        .sb-user {
            cursor: pointer;
            position: relative;
            user-select: none;
        }

        .sb-user:hover {
            background: rgba(255, 255, 255, .04);
        }

        .sb-user-chevron {
            font-size: 9px;
            color: var(--sb-text);
            flex-shrink: 0;
            transition: transform .2s;
        }

        .sb-user.open .sb-user-chevron {
            transform: rotate(180deg);
        }

        .sb-user-dropdown {
            position: absolute;
            top: calc(100% + 4px);
            left: 8px;
            right: 8px;
            background: #1a1a1a;
            border: 1px solid rgba(255, 255, 255, .10);
            border-radius: 10px;
            box-shadow: 0 8px 28px rgba(0, 0, 0, .45);
            z-index: 500;
            overflow: hidden;
            opacity: 0;
            pointer-events: none;
            transform: translateY(-6px);
            transition: opacity .18s ease, transform .18s ease;
        }

        .sb-user.open .sb-user-dropdown {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }

        .sb-dd-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 500;
            color: var(--sb-text);
            text-decoration: none;
            transition: background .15s, color .15s;
            cursor: pointer;
        }

        .sb-dd-item:hover {
            background: rgba(255, 255, 255, .07);
            color: var(--sb-text-hover);
        }

        .sb-dd-item.danger:hover {
            background: rgba(248, 81, 73, .1);
            color: #fca5a5;
        }

        .sb-dd-icon {
            font-size: 15px;
            width: 20px;
            text-align: center;
            flex-shrink: 0;
        }

        .sb-dd-sep {
            height: 1px;
            background: rgba(255, 255, 255, .07);
            margin: 2px 0;
        }

        /* ── Sidebar Tool Buttons ─────────────────────── */
        .sb-tools {
            padding: 8px 10px;
            border-bottom: 1px solid var(--sb-border);
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            flex-shrink: 0;
        }

        .sb-tool-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 10px;
            background: rgba(255, 255, 255, .04);
            border: 1px solid rgba(255, 255, 255, .07);
            border-radius: 8px;
            cursor: pointer;
            color: var(--sb-text);
            font-size: 12px;
            font-weight: 600;
            font-family: var(--font-body);
            transition: all 0.2s;
            white-space: nowrap;
        }

        .sb-tool-btn:hover {
            background: rgba(255, 255, 255, 0.09);
            color: var(--sb-text-hover);
            border-color: rgba(255, 255, 255, 0.14);
        }

        .sb-tool-btn .tb-icon {
            font-size: 14px;
        }

        .sb-tool-btn.active-ibadah {
            background: rgba(124, 58, 237, .15);
            border-color: rgba(124, 58, 237, .3);
            color: #a855f7;
        }

        .sb-tool-btn.active-penduduk {
            background: rgba(34, 197, 94, .15);
            border-color: rgba(34, 197, 94, .3);
            color: #22c55e;
        }

        .sb-tool-btn.btn-stats {
            color: var(--c-info);
        }

        .sb-tool-btn.btn-recalc {
            color: var(--c-warn);
        }

        .sb-tool-btn.btn-import {
            color: var(--c-info);
        }

        .sb-tool-btn.btn-blankspot {
            color: var(--c-danger);
        }

        .sb-tool-btn.btn-blankspot:hover {
            background: rgba(248, 81, 73, .1);
        }

        /* ── Sidebar Data (scrollable) ────────────────── */
        .sb-data {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
        }

        /* ── Sidebar Legend ───────────────────────────── */
        .sb-legend {
            border-top: 1px solid var(--sb-border);
            padding: 10px 14px;
            flex-shrink: 0;
            background: rgba(0, 0, 0, .2);
        }

        /* ── Public Hint Strip ──────────────────────────── */
        .sb-hint {
            padding: 9px 14px;
            background: rgba(255,255,255,.04);
            border-bottom: 1px solid var(--sb-border);
            display: flex;
            align-items: flex-start;
            gap: 8px;
            flex-shrink: 0;
        }

        .sb-hint-icon { font-size: 13px; flex-shrink: 0; margin-top: 1px; }

        .sb-hint-text {
            font-size: 11px;
            color: var(--sb-text);
            line-height: 1.55;
        }

        /* ── Papan kebutuhan tool btn ────────────────── */
        .sb-tool-btn.btn-papan {
            color: var(--c-warn);
            text-decoration: none;
        }

        .sb-tool-btn.btn-papan:hover {
            background: rgba(227, 179, 65, .1);
            border-color: rgba(227, 179, 65, .2);
            color: #fbbf24;
        }

        .sb-legend-title {
            font-size: 9px;
            font-weight: 600;
            color: var(--sb-text);
            text-transform: uppercase;
            letter-spacing: .8px;
            margin-bottom: 8px;
        }

        .sb-legend-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            color: var(--sb-text);
            margin-bottom: 3px;
        }

        .sb-legend-row:last-child {
            margin-bottom: 0;
        }

        .sb-legend-sub {
            font-size: 10px;
            font-weight: 600;
            color: var(--sb-active);
            margin: 6px 0 4px;
            letter-spacing: .3px;
        }

        /* ── Map Area ─────────────────────────────────── */
        .map-area {
            flex: 1;
            position: relative;
            overflow: hidden;
            min-width: 0;
        }

        .toolbar-container {
            display: none;
        }

        .ft-box {
            background: #ede8e2;
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(32,21,21,0.08);
            display: flex;
            align-items: center;
            padding: 6px;
            gap: 6px;
            pointer-events: auto;
            transition: all 0.3s ease;
        }

        .ft-box:hover {
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }

        .ft-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 44px;
            min-width: 44px;
            padding: 0 10px;
            background: transparent;
            border: 1px solid transparent;
            border-radius: 8px;
            cursor: pointer;
            color: var(--c-muted);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-sizing: border-box;
        }

        .ft-content {
            display: flex;
            align-items: center;
            gap: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .ft-text {
            font-size: 12px;
            font-weight: 600;
            max-width: 0;
            opacity: 0;
            overflow: hidden;
            white-space: nowrap;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .ft-icon {
            font-size: 16px;
            line-height: 1;
            transition: transform 0.2s;
        }

        .ft-btn:hover {
            background: rgba(0, 0, 0, 0.05);
            color: var(--c-text);
        }

        .ft-btn:hover .ft-icon {
            transform: scale(1.1);
        }

        .ft-btn:hover .ft-content,
        .ft-btn.active-point .ft-content {
            gap: 8px;
        }

        .ft-btn:hover .ft-text,
        .ft-btn.active-point .ft-text {
            max-width: 80px;
            opacity: 1;
        }

        .ft-btn.active-point {
            background: rgba(52, 211, 153, 0.15);
            border-color: rgba(52, 211, 153, 0.3);
            color: var(--c-accent);
        }

        .ft-btn.active-ibadah {
            background: rgba(124, 58, 237, 0.15);
            border-color: rgba(124, 58, 237, 0.3);
            color: #a855f7;
        }

        .ft-btn.active-penduduk {
            background: rgba(34, 197, 94, 0.15);
            border-color: rgba(34, 197, 94, 0.3);
            color: #22c55e;
        }

        .ft-btn:hover .ft-content,
        .ft-btn.active-ibadah .ft-content,
        .ft-btn.active-penduduk .ft-content {
            gap: 8px;
        }

        .ft-btn.active-ibadah .ft-text,
        .ft-btn.active-penduduk .ft-text {
            max-width: 80px;
            opacity: 1;
        }

        @keyframes pulse-ibadah {

            0%,
            100% {
                opacity: 0.6;
                transform: scale(1);
            }

            50% {
                opacity: 1;
                transform: scale(1.15);
            }
        }

        /* ── Map Container ───────────────────────────── */
        #map {
            position: absolute;
            inset: 0;
        }

        #map.tool-active,
        #map.tool-active .leaflet-container,
        #map.tool-active .leaflet-grab,
        #map.tool-active .leaflet-interactive {
            cursor: crosshair !important;
        }

        /* ── Filter Container (legacy, hidden — content moved to sidebar) ── */
        .filter-container {
            display: none;
        }

        .fc-box {
            background: #f5f5f5;
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
            padding: 6px;
            gap: 4px;
            pointer-events: auto;
            transition: all 0.3s ease;
        }

        .fc-btn {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            height: 36px;
            padding: 0 12px;
            background: transparent;
            border: 1px solid transparent;
            border-radius: 8px;
            cursor: pointer;
            color: var(--c-muted);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            gap: 10px;
            box-sizing: border-box;
            opacity: 1;
        }

        .fc-btn.disabled {
            opacity: 0.4;
            filter: grayscale(1);
        }

        .fc-btn:hover {
            color: var(--c-text);
            background: rgba(255, 255, 255, 0.06);
        }

        .fc-icon {
            font-size: 16px;
            line-height: 1;
            transition: transform 0.2s;
        }

        .fc-btn:hover .fc-icon {
            transform: scale(1.1);
        }

        .fc-text {
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex: 1;
            gap: 16px;
            transition: color 0.2s;
        }

        .fc-count {
            background: rgba(255, 255, 255, 0.1);
            padding: 2px 6px;
            border-radius: 20px;
            font-size: 10px;
            font-family: var(--font-mono);
            color: var(--c-text);
        }

        /* ── Leaflet Popup Override ───────────────────── */
        .leaflet-popup-content-wrapper {
            background: var(--c-surface) !important;
            border: 1px solid var(--c-border) !important;
            border-radius: var(--radius) !important;
            box-shadow: var(--shadow) !important;
            padding: 0 !important;
            color: var(--c-text) !important;
            min-width: 260px;
            max-height: min(80vh, 460px);
            overflow: hidden;
        }

        .leaflet-popup-content {
            margin: 0 !important;
            width: 100% !important;
            max-height: min(80vh, 460px);
            overflow-y: auto;
        }

        .leaflet-popup-tip {
            background: var(--c-border) !important;
        }

        .leaflet-popup-close-button {
            color: var(--c-muted) !important;
            font-size: 20px !important;
            top: 10px !important;
            right: 10px !important;
            width: 24px !important;
            height: 24px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            line-height: 1 !important;
        }

        /* ── Live Tooltip for Drawing ─────────────────── */
        .live-tooltip {
            background: #1c1612 !important;
            color: #ffffff !important;
            border: none !important;
            font-size: 11px !important;
            font-family: var(--font-body) !important;
            border-radius: 6px !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5) !important;
            padding: 4px 8px !important;
        }

        .live-tooltip b {
            color: #fff;
        }

        .live-tooltip::before {
            display: none !important;
        }

        /* ── Form Popup ───────────────────────────────── */
        .form-popup {
            padding: 20px;
        }

        .form-popup-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--c-border);
        }

        .form-popup-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: rgba(46, 160, 67, .15);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 18px;
        }

        .form-popup-icon.blue {
            background: rgba(0, 0, 0, .06);
        }

        .form-popup-icon.amber {
            background: rgba(227, 179, 65, .15);
        }

        .form-popup-title {
            font-size: 14px;
            font-weight: 600;
        }

        .form-popup-coords {
            font-size: 10px;
            color: var(--c-muted);
            font-family: var(--font-mono);
            margin-top: 2px;
        }

        .form-group {
            margin-bottom: 14px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #3d3530;
            margin-bottom: 6px;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select {
            width: 100%;
            padding: 8px 12px;
            background: var(--c-bg);
            border: 1px solid var(--c-border);
            border-radius: 7px;
            color: var(--c-text);
            font-family: var(--font-body);
            font-size: 13px;
            transition: border-color .2s;
            outline: none;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #0d7490;
            outline: none;
        }

        .form-group input:disabled,
        .form-group select:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        .input-readonly {
            padding: 8px 12px;
            background: var(--c-bg);
            border: 1px solid var(--c-border);
            border-radius: 7px;
            color: var(--c-accent-h);
            font-family: var(--font-mono);
            font-size: 12px;
            width: 100%;
        }

        .btn-save {
            width: 100%;
            padding: 10px;
            background: #0d7490;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-family: var(--font-body);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s;
            margin-top: 4px;
        }

        .btn-save:hover {
            background: #0a5f7a;
        }

        .btn-save:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        .form-status {
            font-size: 12px;
            text-align: center;
            margin-top: 8px;
            min-height: 18px;
            font-family: var(--font-mono);
        }

        .form-status.error {
            color: var(--c-danger);
        }

        .form-status.success {
            color: var(--c-accent);
        }

        /* ── Info Popup ───────────────────────────────── */
        .info-popup {
            padding: 14px 16px 12px;
        }

        .info-popup-header {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 10px;
        }

        .info-popup-icon {
            width: 32px;
            height: 32px;
            border-radius: 7px;
            background: rgba(46, 160, 67, .15);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 15px;
        }

        .info-popup-name {
            font-size: 13px;
            font-weight: 600;
            line-height: 1.3;
        }

        .info-popup-id {
            font-size: 10px;
            color: var(--c-muted);
            font-family: var(--font-mono);
            margin-top: 2px;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 0;
            border-top: 1px solid var(--c-border);
            font-size: 12px;
        }

        .info-row-icon {
            width: 24px;
            height: 24px;
            border-radius: 5px;
            background: rgba(255, 255, 255, .05);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            flex-shrink: 0;
        }

        .info-row-label {
            font-size: 10px;
            color: var(--c-muted);
            text-transform: uppercase;
            letter-spacing: .5px;
            font-weight: 600;
        }

        .info-row-value {
            font-weight: 500;
        }

        .status-badge-inline {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .btn-hapus {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            margin-top: 8px;
            padding: 9px;
            background: transparent;
            color: var(--c-danger);
            border: 1px solid rgba(239, 68, 68, .4);
            border-radius: 7px;
            font-family: var(--font-body);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
        }

        .btn-hapus:hover {
            background: rgba(239, 68, 68, .08);
            border-color: #ef4444;
        }

        /* ── Toast ───────────────────────────────────── */
        .toast {
            position: fixed;
            top: 70px;
            right: 20px;
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 18px;
            background: var(--c-surface);
            border: 1px solid var(--c-border);
            border-left: 3px solid var(--c-accent);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            font-size: 13px;
            font-weight: 500;
            max-width: 320px;
            transform: translateX(360px);
            opacity: 0;
            transition: all .35s cubic-bezier(.34, 1.56, .64, 1);
        }

        .toast.show {
            transform: translateX(0);
            opacity: 1;
        }

        .toast.error {
            border-left-color: var(--c-danger);
        }

        /* ── Confirm Modal ─────────────────────────────── */
        .confirm-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .55);
            backdrop-filter: blur(2px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 10000;
            opacity: 0;
            pointer-events: none;
            transition: opacity .2s ease;
        }

        .confirm-overlay.show {
            opacity: 1;
            pointer-events: auto;
        }

        .confirm-dialog {
            width: min(420px, 100%);
            background: var(--c-surface);
            border: 1px solid var(--c-border);
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .confirm-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 16px;
            border-bottom: 1px solid var(--c-border);
            font-weight: 600;
        }

        .confirm-icon {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .confirm-icon.danger {
            background: rgba(248, 81, 73, .15);
            color: var(--c-danger);
        }

        .confirm-icon.warn {
            background: rgba(227, 179, 65, .15);
            color: var(--c-warn);
        }

        .confirm-body {
            padding: 16px;
            font-size: 14px;
            line-height: 1.5;
        }

        .confirm-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            padding: 14px 16px;
            border-top: 1px solid var(--c-border);
        }

        .btn-confirm-cancel,
        .btn-confirm-delete {
            padding: 8px 14px;
            border-radius: 8px;
            font-family: var(--font-body);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
        }

        .btn-confirm-cancel {
            border: 1px solid var(--c-border);
            background: transparent;
            color: var(--c-text);
        }

        .btn-confirm-cancel:hover {
            background: rgba(255, 255, 255, .05);
        }

        .btn-confirm-delete {
            border: 1px solid #ef4444;
            background: rgba(239, 68, 68, .08);
            color: #ef4444;
        }

        .btn-confirm-delete:hover {
            background: rgba(239, 68, 68, .15);
        }

        .btn-confirm-warn {
            border: 1px solid var(--c-warn);
            background: rgba(227, 179, 65, .15);
            color: #f0c96a;
        }

        .btn-confirm-warn:hover {
            background: rgba(227, 179, 65, .28);
        }

        /* ── Online/Offline Indicator ──────────────────── */
        .conn-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 20px;
            transition: all .3s;
        }

        .conn-badge.online {
            background: rgba(16, 185, 129, .12);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, .25);
        }

        .conn-badge.offline {
            background: rgba(248, 81, 73, .12);
            color: #fca5a5;
            border: 1px solid rgba(248, 81, 73, .25);
        }

        .conn-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .conn-badge.online .conn-dot {
            animation: pulse-dot 2s ease-in-out infinite;
        }

        @keyframes pulse-dot {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: .4;
            }
        }

        /* ── Leaflet Controls ─────────────────────────── */
        .leaflet-control-attribution {
            background: rgba(250,250,249,.88) !important;
            color: #7a7067 !important;
            font-size: 10px !important;
        }

        .leaflet-control-attribution a {
            color: #3d3530 !important;
        }

        .leaflet-control-zoom a {
            background: var(--c-surface) !important;
            color: var(--c-text) !important;
            border-color: var(--c-border) !important;
        }

        .leaflet-control-zoom a:hover {
            background: #ede8e2 !important;
        }

        /* ── Leaflet Layers Control — Light Theme ──────── */
        .leaflet-control-layers {
            background: #ede8e2 !important;
            border: 1px solid #ddd8d2 !important;
            border-radius: 12px !important;
            box-shadow: 0 4px 12px rgba(32,21,21,0.08) !important;
            color: #201515 !important;
            min-width: 200px;
        }

        .leaflet-control-layers-toggle {
            background-color: var(--c-surface) !important;
            border-radius: 8px !important;
        }

        .leaflet-control-layers-expanded {
            padding: 10px 14px !important;
        }

        /* Judul seksi (Basemaps / Overlays) */
        .leaflet-control-layers-base::before {
            content: 'Peta Dasar';
            display: block;
            font-size: 10px;
            font-weight: 600;
            color: var(--c-muted);
            text-transform: uppercase;
            letter-spacing: .6px;
            margin-bottom: 6px;
            font-family: var(--font-body);
        }

        .leaflet-control-layers label {
            display: flex !important;
            align-items: center !important;
            gap: 7px !important;
            padding: 5px 4px !important;
            cursor: pointer !important;
            border-radius: 6px !important;
            transition: background .15s !important;
            font-family: var(--font-body) !important;
            font-size: 12px !important;
            font-weight: 500 !important;
            color: var(--c-text) !important;
            margin: 0 !important;
        }

        .leaflet-control-layers label:hover {
            background: rgba(0, 0, 0, 0.05) !important;
        }

        .leaflet-control-layers input[type=radio],
        .leaflet-control-layers input[type=checkbox] {
            accent-color: var(--c-accent);
            width: 14px;
            height: 14px;
            cursor: pointer;
            flex-shrink: 0;
        }

        /* Garis pemisah antara Basemaps dan Overlays */
        .leaflet-control-layers-separator {
            border-top: 1px solid var(--c-border) !important;
            margin: 8px 0 !important;
        }

        /* ── Loading Overlay ──────────────────────────── */
        .loading-overlay {
            position: fixed;
            inset: 0;
            background: var(--c-bg);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
            z-index: 99999;
            transition: opacity .4s ease;
        }

        .loading-overlay.hide {
            opacity: 0;
            pointer-events: none;
        }

        .loader-ring {
            width: 48px;
            height: 48px;
            border: 3px solid var(--c-border);
            border-top-color: var(--c-accent);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .loading-text {
            font-family: var(--font-mono);
            font-size: 13px;
            color: var(--c-muted);
        }

        /* -- Legend Panel (legacy, hidden — content moved to sidebar) -- */
        .legend-panel {
            display: none;
        }

        .legend {
            background: var(--c-surface);
            border: 1px solid var(--c-border);
            border-radius: var(--radius);
            padding: 12px 16px;
            font-size: 12px;
            box-shadow: var(--shadow);
            display: none;
            min-width: 175px;
        }

        .legend.visible {
            display: block;
        }

        .legend.always-visible {
            display: block;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 6px;
            cursor: pointer;
            transition: opacity .2s, transform .1s;
        }

        .legend-item:hover {
            opacity: 0.8;
            transform: translateX(2px);
        }

        .legend-item.dimmed {
            opacity: 0.25;
            text-decoration: line-through;
        }

        .legend-item:last-child {
            margin-bottom: 0;
        }

        .legend-color {
            width: 24px;
            height: 4px;
            border-radius: 2px;
        }

        .legend-swatch {
            width: 14px;
            height: 14px;
            border-radius: 3px;
            opacity: .85;
        }

        /* ── Drawing instructions ─────────────────────── */
        .draw-hint {
            position: fixed;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 999;
            background: var(--c-surface);
            border: 1px solid var(--c-border);
            border-radius: 20px;
            padding: 8px 18px;
            font-size: 12px;
            color: var(--c-muted);
            box-shadow: var(--shadow);
            display: none;
            white-space: nowrap;
        }

        .draw-hint.visible {
            display: block;
        }

        /* ── Data List Panel ──────────────────────────── */
        .dl-box {
            background: #ede8e2;
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(32,21,21,0.08);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            pointer-events: auto;
            transition: all 0.3s ease;
            max-height: calc(100vh - 200px);
        }

        .dl-section {
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .dl-section:last-child {
            border-bottom: none;
        }

        .dl-header {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            cursor: pointer;
            user-select: none;
            transition: background 0.2s;
        }

        .dl-header:hover {
            background: rgba(0, 0, 0, 0.04);
        }

        .dl-header-icon {
            font-size: 14px;
            line-height: 1;
        }

        .dl-header-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--c-muted);
            text-transform: uppercase;
            letter-spacing: .5px;
            flex: 1;
        }

        .dl-header-count {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 2px 7px;
            border-radius: 20px;
            font-size: 10px;
            font-family: var(--font-mono);
            color: rgba(255, 255, 255, 0.5);
        }

        .dl-chevron {
            font-size: 10px;
            color: var(--c-muted);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-left: 2px;
        }

        .dl-header.open .dl-chevron {
            transform: rotate(180deg);
            color: #c8922a;
        }

        .dl-header.open {
            background: rgba(0, 0, 0, 0.04);
        }

        .dl-header.open .dl-header-label {
            color: #d4a43e;
        }

        .dl-header.open .dl-header-count {
            background: rgba(196, 127, 23, 0.18);
            border: 1px solid rgba(196, 127, 23, 0.35);
            color: #c8922a;
        }

        .dl-list {
            max-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            transition: max-height 0.35s cubic-bezier(0.4, 0, 0.2, 1),
                opacity 0.25s ease;
            opacity: 0;
        }

        .dl-list.open {
            max-height: 440px;
            opacity: 1;
        }

        /* Custom scrollbar — global */
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--c-border);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--c-muted);
        }

        .dl-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px 6px 12px;
            cursor: pointer;
            transition: background 0.15s;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
        }

        .dl-item:hover {
            background: rgba(255, 255, 255, 0.07);
        }

        .dl-item:hover .dl-item-name {
            color: var(--sb-text-hover);
        }

        .dl-item-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .dl-item-name {
            font-size: 12px;
            font-weight: 500;
            color: var(--sb-text);
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            transition: color 0.15s;
        }

        .dl-item-badge {
            font-size: 9px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 8px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .dl-empty {
            padding: 10px 14px;
            font-size: 11px;
            color: var(--c-muted);
            font-style: italic;
            text-align: center;
        }

        /* ── Eye Toggle Button ──────────────────────────────── */
        .dl-eye {
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 3px 5px;
            border-radius: 5px;
            color: var(--c-accent-h);
            line-height: 1;
            transition: color 0.2s, background 0.2s, opacity 0.2s;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dl-eye:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        .dl-eye .eye-closed {
            display: none;
        }

        .dl-eye .eye-open {
            display: block;
        }

        .dl-eye.layer-hidden {
            color: var(--c-muted);
            opacity: 0.45;
        }

        .dl-eye.layer-hidden .eye-closed {
            display: block;
        }

        .dl-eye.layer-hidden .eye-open {
            display: none;
        }

        /* Dimmed header saat layer disembunyikan */
        .dl-header.layer-dimmed .dl-header-label,
        .dl-header.layer-dimmed .dl-header-icon {
            opacity: 0.38;
        }

        /* Filter box: hidden by default, shown when section is open */
        .dl-filter-container {
            display: none;
            flex-direction: column;
            padding: 8px 12px 12px;
            background: rgba(0, 0, 0, 0.04);
            gap: 8px;
            border-bottom: 1px solid var(--c-border);
        }

        .dl-header.open~.dl-filter-container {
            display: flex;
        }

        .dl-filter-row {
            display: flex;
            gap: 6px;
        }

        .layer-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #fafaf9;
            border: 1px solid #ddd8d2;
            border-radius: 9999px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 500;
            color: #7a7067;
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
            transition: border-color .15s, color .15s;
        }

        .layer-chip:hover {
            border-color: #0d7490;
            color: #0d7490;
        }

        .layer-chip input[type="checkbox"] {
            accent-color: #0d7490;
            cursor: pointer;
        }

        .dl-search-input {
            flex: 1;
            background: var(--c-bg);
            border: 1px solid var(--c-border);
            border-radius: 8px;
            padding: 6px 10px;
            color: var(--c-text);
            font-size: 11px;
            transition: border-color .2s;
        }

        .dl-search-input:focus {
            border-color: var(--c-accent);
            outline: none;
        }

        .dl-select-filter {
            flex: 1;
            min-width: 0;
            background: var(--c-bg);
            border: 1px solid var(--c-border);
            border-radius: 8px;
            padding: 5px 8px;
            color: var(--c-text);
            font-size: 11px;
            cursor: pointer;
        }

        .dl-select-filter:focus {
            border-color: var(--c-accent);
            outline: none;
        }

        /* ── Toggle Switch (Heatmap) ───────────────────────── */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 34px;
            height: 18px;
            flex-shrink: 0;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background-color: var(--c-border);
            transition: .3s;
            border-radius: 18px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 12px;
            width: 12px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: var(--c-accent);
        }

        input:checked+.slider:before {
            transform: translateX(16px);
        }

        /* ── Interactive Legend ──────────────────────────────── */
        .legend .legend-item {
            cursor: pointer;
            border-radius: 6px;
            padding: 4px 6px;
            margin: 0 -6px;
            transition: background .15s, opacity .2s, transform .15s;
        }

        .legend .legend-item:hover {
            background: rgba(255, 255, 255, 0.06);
            transform: translateX(2px);
        }

        .legend .legend-item.dimmed {
            opacity: 0.28;
            text-decoration: line-through;
        }

        /* ── Context Menu ────────────────────────────────────── */
        .ctx-menu {
            position: fixed;
            background: var(--c-surface);
            border: 1px solid var(--c-border);
            border-radius: 10px;
            box-shadow: var(--shadow);
            z-index: 30000;
            padding: 6px;
            min-width: 200px;
            opacity: 0;
            pointer-events: none;
            transform: scale(0.94) translateY(-4px);
            transition: opacity .15s ease, transform .15s ease;
            transform-origin: top left;
        }

        .ctx-menu.show {
            opacity: 1;
            pointer-events: auto;
            transform: scale(1) translateY(0);
        }

        .ctx-menu-title {
            font-size: 9px;
            font-weight: 800;
            color: var(--c-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 4px 10px 6px;
        }

        .ctx-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 7px;
            cursor: pointer;
            font-size: 13px;
            color: var(--c-text);
            transition: background .15s;
        }

        .ctx-item:hover {
            background: rgba(0, 0, 0, 0.05);
        }

        .ctx-item-icon {
            font-size: 15px;
            width: 20px;
            text-align: center;
        }

        .ctx-separator {
            height: 1px;
            background: var(--c-border);
            margin: 4px 0;
        }

        /* ── Dashboard Modal ───────────────────────────────── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .75);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 20000;
            opacity: 0;
            pointer-events: none;
            transition: opacity .3s ease;
        }

        .modal-overlay.show {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-container {
            width: min(900px, 100%);
            max-height: 90vh;
            background: var(--c-surface);
            border: 1px solid var(--c-border);
            border-radius: 16px;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .modal-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--c-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            background: transparent;
            border: none;
            color: var(--c-muted);
            font-size: 24px;
            cursor: pointer;
            transition: color .2s;
        }

        .modal-close:hover {
            color: var(--c-text);
        }

        .modal-body {
            padding: 24px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .stat-card {
            background: var(--c-bg);
            border: 1px solid var(--c-border);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .stat-card-title {
            font-size: 12px;
            font-weight: 600;
            color: var(--c-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-canvas-wrapper {
            position: relative;
            height: 220px;
            width: 100%;
        }

        .summary-grid {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 8px;
        }

        .summary-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--c-border);
            border-radius: 10px;
            padding: 16px;
            text-align: center;
        }

        .summary-value {
            font-size: 24px;
            font-weight: 600;
            color: var(--c-accent-h);
            font-family: var(--font-mono);
        }

        .summary-label {
            font-size: 11px;
            color: var(--c-muted);
            margin-top: 4px;
        }

        /* ── User Badge (legacy, hidden — moved to sidebar) ─── */
        .user-badge {
            display: none;
        }

        /* ══ EMBEDDED MAP MODE ══════════════════════════════
           Sidebar menjadi: toolbar bar di atas + data panel
           floating di kiri bawah toolbar. Tidak ada wrapping.
        ════════════════════════════════════════════════════ */

        body.embedded-map .app-layout {
            position: relative;
        }

        body.embedded-map .sidebar {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            min-width: 0;
            max-width: none;
            height: auto;
            max-height: none;
            background: transparent;
            border: 0;
            overflow: visible;
            box-shadow: none;
            z-index: 800;
            pointer-events: none;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            flex: none;
            transition: none;
        }

        body.embedded-map .sb-header,
        body.embedded-map .sb-user,
        body.embedded-map .sb-hint {
            display: none;
        }

        body.embedded-map .sidebar::before {
            display: none;
        }

        /* ── Toolbar: bar horizontal penuh di atas peta ── */
        body.embedded-map .sb-tools {
            width: 100%;
            max-width: 100%;
            padding: 8px 14px;
            display: flex;
            flex-wrap: nowrap;
            gap: 6px;
            align-items: center;
            background: #ede8e2;
            border: 0;
            border-bottom: 1px solid #ddd8d2;
            border-radius: 0;
            box-shadow: 0 2px 8px rgba(32,21,21,.08);
            pointer-events: auto;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
            flex-shrink: 0;
            min-height: 48px;
        }

        body.embedded-map .sb-tools::-webkit-scrollbar {
            display: none;
        }

        body.embedded-map .sb-tool-btn {
            height: 32px;
            padding: 0 13px;
            justify-content: center;
            border-radius: 8px;
            background: rgba(255, 255, 255, .07);
            border: 1px solid rgba(255, 255, 255, .11);
            font-size: 12px;
            flex-shrink: 0;
            white-space: nowrap;
        }

        body.embedded-map .sb-tool-btn:hover {
            background: rgba(0, 0, 0, .07);
            border-color: rgba(0, 0, 0, .18);
        }

        body.embedded-map .sb-tool-btn .tb-icon {
            font-size: 14px;
        }

        /* ── Separator ────────────────────────────────── */
        body.embedded-map .sb-tools-sep {
            width: 1px;
            height: 22px;
            background: rgba(255, 255, 255, .12);
            flex-shrink: 0;
        }

        /* ── Data panel: floating di kiri, bawah toolbar ─ */
        body.embedded-map .sb-data {
            position: absolute;
            top: 58px;
            left: 12px;
            width: 268px;
            max-width: calc(100vw - 320px);
            max-height: calc(100vh - 130px);
            overflow-y: auto;
            background: #f5f5f5;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,.08);
            pointer-events: auto;
        }

        body.embedded-map .sb-data:empty {
            display: none;
        }

        /* Dropdown sections lebih compact */
        body.embedded-map .dl-section {
            border-bottom: 1px solid var(--c-border);
        }

        body.embedded-map .dl-section:last-child {
            border-bottom: 0;
        }

        body.embedded-map .dl-header {
            min-height: 36px;
            padding: 8px 12px;
            background: transparent;
            gap: 7px;
        }

        body.embedded-map .dl-header:hover {
            background: rgba(255, 255, 255, .04);
        }

        body.embedded-map .dl-header-icon {
            font-size: 13px;
        }

        body.embedded-map .dl-header-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .2px;
        }

        body.embedded-map .dl-header-count {
            height: 18px;
            min-width: 18px;
            padding: 0 5px;
            font-size: 10px;
            border-radius: 9px;
            background: rgba(255, 255, 255, .12);
        }

        body.embedded-map .dl-chevron {
            font-size: 9px;
        }

        body.embedded-map .dl-eye {
            width: 22px;
            height: 22px;
        }

        body.embedded-map .dl-list.open {
            max-height: 160px;
        }

        body.embedded-map .dl-item {
            padding: 5px 12px;
        }

        body.embedded-map .dl-item-name {
            font-size: 11px;
            color: var(--c-muted);
        }

        body.embedded-map .dl-item:hover {
            background: rgba(0, 0, 0, 0.05);
        }

        body.embedded-map .dl-item:hover .dl-item-name {
            color: var(--c-text);
        }

        body.embedded-map .dl-filter-container {
            padding: 6px 10px 10px;
            background: rgba(0, 0, 0, .18);
            border-bottom: 1px solid rgba(255, 255, 255, .07);
            gap: 5px;
        }

        body.embedded-map .dl-filter-row {
            gap: 5px;
        }

        body.embedded-map .dl-search-input,
        body.embedded-map .dl-select-filter {
            font-size: 10px;
            padding: 4px 8px;
            border-radius: 5px;
        }

        body.embedded-map .layer-chip {
            font-size: 10px;
            padding: 2px 6px;
        }

        /* Heatmap toggle row juga compact */
        body.embedded-map .dl-header[style*="cursor: default"] {
            min-height: 36px;
            padding: 8px 12px;
        }

        /* ── Legend: kanan bawah, compact ─────────────── */
        body.embedded-map .sb-legend {
            position: fixed;
            right: 12px;
            bottom: 28px;   /* di atas attribution leaflet */
            left: auto;
            width: auto;
            min-width: 150px;
            max-width: 220px;
            max-height: 220px;
            overflow-y: auto;
            padding: 8px 10px;
            background: #f5f5f5;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,.08);
            pointer-events: auto;
        }

        body.embedded-map .sb-legend-title {
            font-size: 8px;
            letter-spacing: .9px;
            margin-bottom: 6px;
        }

        body.embedded-map .sb-legend-sub {
            font-size: 9px;
            margin: 5px 0 3px;
        }

        body.embedded-map .sb-legend-row {
            font-size: 10px;
            gap: 6px;
            margin-bottom: 3px;
        }

        body.embedded-map .sb-legend-row > div {
            flex-shrink: 0;
        }

        /* ── Leaflet controls — zoom geser ke kanan, lapis bawah layers ─ */
        body.embedded-map .leaflet-top.leaflet-left {
            /* Geser zoom ke kanan data panel (268px + 12px margin + 12px gap) */
            left: 292px;
            top: 62px;
        }

        body.embedded-map .leaflet-top.leaflet-right {
            top: 62px;
            right: 10px;
        }

        body.embedded-map .leaflet-bottom.leaflet-right {
            right: 10px;
        }

        body.embedded-map .leaflet-bottom.leaflet-left {
            left: 12px;
        }

        body.embedded-map .leaflet-control-layers {
            margin-top: 0;
            background: rgba(15, 23, 42, .92);
            border: 1px solid rgba(255, 255, 255, .10);
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, .40);
        }

        /* ── Collapsed: hanya toolbar yang tampil ─────── */
        body.embedded-map .sidebar.collapsed .sb-data,
        body.embedded-map .sidebar.collapsed .sb-legend {
            display: none;
        }

        body.embedded-map .sidebar.collapsed .sb-tools {
            display: flex !important;
        }

        body.embedded-map .map-area {
            width: 100%;
            flex: 1 1 auto;
        }

        body.embedded-map .sidebar.collapsed~.map-area .sb-open-btn {
            display: none;
        }

        /* ── Lucide Icons Styling ───────────────────────────── */
        .lucide {
            width: 16px;
            height: 16px;
            stroke-width: 2px;
            display: inline-block;
            vertical-align: middle;
        }
        .sb-logo .lucide {
            width: 18px;
            height: 18px;
            color: #ffffff;
        }
        .sb-icon .lucide {
            width: 16px;
            height: 16px;
        }
        .sb-toggle-icon .lucide {
            width: 16px;
            height: 16px;
        }
        .hd-notif-btn .lucide {
            width: 18px;
            height: 18px;
        }
        .hd-logout-btn .lucide {
            width: 16px;
            height: 16px;
        }
        .sb-user-chevron .lucide {
            width: 12px;
            height: 12px;
            display: block;
        }
        .sb-user-avatar .lucide {
            width: 16px;
            height: 16px;
            color: #ffffff;
        }
        .sb-hint-icon .lucide {
            width: 14px;
            height: 14px;
            color: var(--sb-text);
            display: block;
        }
        .sb-tool-btn .tb-icon .lucide {
            width: 14px;
            height: 14px;
            display: inline-block;
            vertical-align: middle;
            margin-top: -2px;
        }
        .dl-header-icon .lucide {
            width: 14px;
            height: 14px;
            display: inline-block;
            vertical-align: middle;
            margin-top: -2px;
        }
        .ctx-item-icon .lucide {
            width: 14px;
            height: 14px;
            display: inline-block;
            vertical-align: middle;
            margin-top: -2px;
        }
        .confirm-icon .lucide {
            width: 16px;
            height: 16px;
        }
        .animate-spin {
            animation: spin 1s linear infinite;
        }
    </style>
    <script>
        window.APP_USER = {
            loggedIn: <?= $logged_in ? 'true' : 'false' ?>,
            role: '<?= $role ?>',
            isAdmin: <?= $is_admin ? 'true' : 'false' ?>,
            isOp: <?= $is_op ? 'true' : 'false' ?>,
            ibadahId: <?= json_encode($is_op ? get_ibadah_id() : null) ?>,
            csrfToken: <?= json_encode($logged_in ? csrf_token() : null) ?>
        };
        // ── Global Registries ───────────────────────────────────────────
        window._dlFocusFns = {};   // populated by each module's initXxx()

        // ── Utility: HTML Escape ────────────────────────────────────────
        window.escapeHTML = function (str) {
            const d = document.createElement('div');
            d.appendChild(document.createTextNode(str ?? ''));
            return d.innerHTML;
        };

        // ── Utility: CSRF ───────────────────────────────────────────────
        function appendCsrf(fd) {
            const token = window.APP_USER && window.APP_USER.csrfToken;
            if (token) fd.append('csrf_token', token);
            return fd;
        }
        window.appendCsrf = appendCsrf;

        // ── Donor Mark-Read ─────────────────────────────────────────────
        window.markDonorRead = function () {
            const fd = new FormData();
            fd.append('action', 'mark_all');
            fetch('api/papan/donatur.php', { method: 'POST', body: appendCsrf(fd) })
                .then(r => r.json())
                .catch(function () {});
        };

        // ── Toast Notification ──────────────────────────────────────────
        (function () {
            let _toastTimer = null;
            window.showToast = function (msg, type, duration) {
                const el   = document.getElementById('toast');
                const icon = document.getElementById('toastIcon');
                const txt  = document.getElementById('toastMsg');
                if (!el) return;
                clearTimeout(_toastTimer);
                el.className = 'toast' + (type === 'error' ? ' error' : '');
                if (icon) icon.textContent = type === 'error' ? '✕' : '✓';
                if (txt)  txt.textContent  = msg;
                void el.offsetWidth; // force reflow
                el.classList.add('show');
                _toastTimer = setTimeout(function () { el.classList.remove('show'); }, duration || 3500);
            };
        })();

        // ── Delete Confirm Dialog ───────────────────────────────────────
        window.showDeleteConfirm = function (msg, options) {
            return new Promise(function (resolve) {
                const overlay = document.getElementById('deleteConfirmOverlay');
                const body    = document.getElementById('deleteConfirmMessage');
                const title   = document.getElementById('deleteConfirmTitle');
                const icon    = document.getElementById('confirmDialogIcon');
                const btnOk   = document.getElementById('btnDeleteConfirm');
                const btnNo   = document.getElementById('btnDeleteCancel');
                if (!overlay) { resolve(window.confirm(msg)); return; }
                const cfg = options || {};
                const type = cfg.type || 'danger';
                if (title) title.textContent = cfg.title || 'Konfirmasi Hapus';
                if (body) body.textContent = msg;
                if (btnOk) {
                    btnOk.textContent = cfg.btnLabel || cfg.confirmLabel || 'Ya, Hapus';
                    btnOk.classList.toggle('btn-confirm-warn', type === 'warn');
                    btnOk.classList.toggle('btn-confirm-delete', type !== 'warn');
                }
                if (icon) {
                    icon.classList.toggle('warn', type === 'warn');
                    icon.classList.toggle('danger', type !== 'warn');
                    icon.innerHTML = '';
                    if (window.lucide) {
                        icon.innerHTML = '<i data-lucide="' + (cfg.iconName || (type === 'warn' ? 'alert-triangle' : 'trash-2')) + '"></i>';
                        window.lucide.createIcons();
                    }
                }
                overlay.classList.add('show');
                overlay.removeAttribute('aria-hidden');
                function close(val) {
                    overlay.classList.remove('show');
                    overlay.setAttribute('aria-hidden', 'true');
                    if (btnOk) btnOk.removeEventListener('click', onOk);
                    if (btnNo) btnNo.removeEventListener('click', onNo);
                    resolve(val);
                }
                function onOk() { close(true); }
                function onNo() { close(false); }
                if (btnOk) btnOk.addEventListener('click', onOk);
                if (btnNo) btnNo.addEventListener('click', onNo);
            });
        };

        // ── Data List Sidebar ───────────────────────────────────────────
        window.dlRefreshList = function (domain, items) {
            const countEl  = document.getElementById('dlCount'  + domain);
            const listEl   = document.getElementById('dlList'   + domain);
            const headerEl = document.getElementById('dlHeader' + domain);
            if (!listEl) return;

            if (countEl) countEl.textContent = items.length;

            if (!items.length) {
                listEl.innerHTML = '<div class="dl-empty">Belum ada data.</div>';
                return;
            }

            listEl.innerHTML = items.map(function (item) {
                const dot   = item.dotColor   || '#8b949e';
                const badge = item.badge      || '';
                const bc    = item.badgeColor || '#8b949e';
                const id    = parseInt(item.id) || 0;
                return '<div class="dl-item" onclick="(window._dlFocusFns[\'' + domain + '\'] || function(){})('+id+')">' +
                    '<span class="dl-item-dot" style="background:' + dot + ';"></span>' +
                    '<span class="dl-item-name">' + escapeHTML(item.name || '') + '</span>' +
                    (badge ? '<span class="dl-item-badge" style="color:' + bc + ';border-color:' + bc + '22;background:' + bc + '11;">' + escapeHTML(badge) + '</span>' : '') +
                    '</div>';
            }).join('');

            // Auto-buka section setelah data terisi
            if (headerEl) headerEl.classList.add('open');
            listEl.classList.add('open');
        };

        // ── Sidebar Section Toggle ──────────────────────────────────────
        // CSS menggunakan .open pada header dan list, bukan .collapsed pada section
        function dlToggle(domain) {
            const headerEl = document.getElementById('dlHeader' + domain);
            const listEl   = document.getElementById('dlList'   + domain);
            if (headerEl) headerEl.classList.toggle('open');
            if (listEl)   listEl.classList.toggle('open');
        }

        // ── Layer Eye Toggle ────────────────────────────────────────────
        function toggleLayerView(layer) {
            const cap    = layer.charAt(0).toUpperCase() + layer.slice(1);
            const eyeBtn = document.getElementById('eye' + cap);
            const header = document.getElementById('dlHeader' + cap);
            if (!eyeBtn) return;
            eyeBtn.classList.toggle('layer-hidden');
            const hidden = eyeBtn.classList.contains('layer-hidden');
            if (header) header.classList.toggle('layer-dimmed', hidden);
            if (layer === 'ibadah') {
                if (typeof window._ibadahToggleLayer   === 'function') window._ibadahToggleLayer(!hidden);
            } else if (layer === 'penduduk') {
                if (typeof window._pendudukToggleLayer === 'function') window._pendudukToggleLayer(!hidden);
            }
        }

        // ── User Dropdown ───────────────────────────────────────────────
        // CSS shows dropdown via .sb-user.open — toggle .open on the parent
        function toggleUserDropdown(e) {
            e && e.stopPropagation();
            const btn = document.getElementById('sbUserBtn');
            if (btn) btn.classList.toggle('open');
        }
        document.addEventListener('click', function () {
            const btn = document.getElementById('sbUserBtn');
            if (btn) btn.classList.remove('open');
        });

        window.dispatchDataChanged = function (domain, payload = {}) {
            window.dispatchEvent(new CustomEvent('webgis:data-changed', {
                detail: { domain, payload }
            }));
        };
        window.refreshAllData = function () {
            if (typeof window._ibadahReload === 'function') window._ibadahReload();
            if (typeof window._pendudukReload === 'function') window._pendudukReload();
        };
        window.addEventListener('webgis:data-changed', function () {
            if (typeof window._statsUpdate === 'function') window._statsUpdate();
            if (typeof window._heatmapUpdate === 'function') window._heatmapUpdate();
            if (typeof window._updateBlankSpotCounter === 'function') window._updateBlankSpotCounter();
        });
    </script>
</head>

<body class="<?= $embedded ? 'embedded-map' : '' ?>">

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loader-ring"></div>
        <div class="loading-text" id="loadingText">Memuat peta...</div>
    </div>

    <!-- Toast -->
    <div class="toast" id="toast">
        <span id="toastIcon"></span>
        <span id="toastMsg"></span>
    </div>

    <!-- Delete Confirm Modal -->
    <div class="confirm-overlay" id="deleteConfirmOverlay" aria-hidden="true">
        <div class="confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="deleteConfirmTitle">
            <div class="confirm-header">
                <div class="confirm-icon danger" id="confirmDialogIcon"></div>
                <div id="deleteConfirmTitle">Konfirmasi Hapus</div>
            </div>
            <div class="confirm-body" id="deleteConfirmMessage">Yakin ingin menghapus data ini?</div>
            <div class="confirm-actions">
                <button type="button" class="btn-confirm-cancel" id="btnDeleteCancel">Batal</button>
                <button type="button" class="btn-confirm-delete" id="btnDeleteConfirm">Ya, Hapus</button>
            </div>
        </div>
    </div>

    <!-- Import CSV Modal -->
    <div class="modal-overlay" id="modalImport">
        <div class="modal-container" style="width:min(640px,100%)">
            <div class="modal-header">
                <div class="modal-title" style="display:inline-flex;align-items:center;gap:8px;">
                    <i data-lucide="file-input" style="width:18px;height:18px;"></i> Import Data Penduduk via CSV
                </div>
                <button class="modal-close"
                    onclick="document.getElementById('modalImport').classList.remove('show')">&times;</button>
            </div>
            <div style="padding:24px;overflow-y:auto;max-height:80vh;">
                <div style="display:flex;gap:10px;margin-bottom:18px;align-items:center;">
                    <a id="btnDownloadTemplate" href="api/import/template.php"
                       style="padding:7px 14px;background:rgba(56,139,253,.12);color:#79c0ff;
                              border:1px solid rgba(56,139,253,.3);border-radius:8px;
                              font-size:12px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                        <i data-lucide="download" style="width:14px;height:14px;"></i> Download Template
                    </a>
                    <span style="font-size:12px;color:var(--c-muted);">Maks. 500 baris per upload</span>
                </div>
                <div style="margin-bottom:16px;">
                    <label style="display:block;font-size:11px;font-weight:700;
                                  color:var(--c-muted);text-transform:uppercase;
                                  letter-spacing:.6px;margin-bottom:6px;">Pilih File CSV</label>
                    <input type="file" id="importFile" accept=".csv"
                           style="color:var(--c-text);font-size:13px;"
                           onchange="previewCSV()">
                </div>
                <div id="importPreviewArea" style="display:none;">
                    <div id="importSummary" style="margin-bottom:12px;font-size:13px;"></div>
                    <div id="importPreviewTable" style="overflow-x:auto;margin-bottom:12px;"></div>
                    <div id="importErrorList" style="display:none;margin-bottom:12px;"></div>
                    <button id="btnDoImport" class="btn-save" onclick="doImport()"
                            style="max-width:200px;display:inline-flex;align-items:center;justify-content:center;gap:6px;">
                        <i data-lucide="check" style="width:14px;height:14px;"></i> Import Sekarang
                    </button>
                </div>
                <div id="importResultMsg" style="font-size:13px;margin-top:12px;
                                                font-family:monospace;min-height:16px;display:inline-flex;align-items:center;gap:6px;"></div>
            </div>
        </div>
    </div>

    <!-- App Layout: Sidebar + Map -->
    <div class="app-layout">

        <!-- ════ SIDEBAR ════ -->
        <aside class="sidebar" id="sidebar">

            <!-- Header -->
            <div class="sb-header">
                <div class="sb-brand">
                    <div class="sb-logo"><i data-lucide="map"></i></div>
                    <div>
                        <div class="sb-title"><?= APP_NAME ?></div>
                        <div class="sb-subtitle">Pemetaan Kemiskinan</div>
                    </div>
                </div>
                <div class="sb-header-meta">
                    <div id="connBadge" class="conn-badge online">
                        <div class="conn-dot"></div>
                        <span id="connLabel">Online</span>
                    </div>
                    <button class="sb-toggle-btn" id="btnCollapseSidebar" onclick="toggleSidebar()"
                        title="Tutup panel"><i data-lucide="chevrons-left"></i></button>
                </div>
            </div>

            <!-- User Info -->
            <?php if ($logged_in): ?>
            <?php
                $init       = mb_strtoupper(mb_substr(trim($user_nama ?? ''), 0, 1, 'UTF-8'), 'UTF-8') ?: '?';
                $av_bg      = '#111111';
                $role_label = $is_admin ? 'Administrator' : ($is_op ? 'Operator' : 'Viewer');
            ?>
            <div class="sb-user" id="sbUserBtn" onclick="toggleUserDropdown(event)">
                <div class="sb-user-avatar" style="background:<?= $av_bg ?>;"><?= htmlspecialchars($init) ?></div>
                <div class="sb-user-info">
                    <div class="sb-user-name-text"><?= htmlspecialchars($user_nama ?? '') ?></div>
                    <div class="sb-user-role-text"><?= $role_label ?></div>
                </div>
                <span class="sb-user-chevron"><i data-lucide="chevron-down"></i></span>

                <!-- Dropdown menu -->
                <div class="sb-user-dropdown" id="sbUserDropdown">
                    <?php if ($is_admin && has_role('administrator')): ?>
                    <a href="dashboard.php" class="sb-dd-item">
                        <span class="sb-dd-icon"><i data-lucide="bar-chart-2"></i></span> Dashboard Admin
                    </a>
                    <a href="pages/users.php" class="sb-dd-item">
                        <span class="sb-dd-icon"><i data-lucide="users"></i></span> Manajemen Pengguna
                    </a>
                    <?php elseif ($is_op): ?>
                    <a href="pages/map.php" class="sb-dd-item">
                        <span class="sb-dd-icon"><i data-lucide="map"></i></span> Peta Operator
                    </a>
                    <a href="pages/status-bantuan.php" class="sb-dd-item">
                        <span class="sb-dd-icon"><i data-lucide="clipboard-list"></i></span> Status Bantuan
                    </a>
                    <?php endif; ?>
                    <div class="sb-dd-sep"></div>
                    <a href="api/auth/logout.php" class="sb-dd-item danger">
                        <span class="sb-dd-icon"><i data-lucide="log-out"></i></span> Keluar
                    </a>
                </div>
            </div>
            <?php else: ?>
            <div class="sb-user">
                <div class="sb-user-avatar" style="background:#111111;font-size:15px;"><i data-lucide="user"></i></div>
                <div class="sb-user-info">
                    <div class="sb-user-name-text">Pengunjung</div>
                    <div class="sb-user-role-text">Akses Publik</div>
                </div>
                <a href="auth/login.php" class="sb-login-btn">Masuk <i data-lucide="arrow-right"></i></a>
            </div>
            <?php endif; ?>

            <?php if (!$logged_in): ?>
            <!-- Hint strip untuk pengunjung publik -->
            <div class="sb-hint">
                <span class="sb-hint-icon"><i data-lucide="info"></i></span>
                <span class="sb-hint-text">Klik marker di peta untuk detail. Gunakan filter di bawah untuk mencari data sebaran kemiskinan.</span>
            </div>
            <?php endif; ?>

            <!-- Tools / Actions -->
            <div class="sb-tools">
                <?php if ($is_admin): ?>
                    <button class="sb-tool-btn" id="btnToolIbadah" onclick="activateTool('ibadah')"
                        title="Tambah Rumah Ibadah ke peta">
                        <span class="tb-icon"><i data-lucide="map-pin"></i></span> Ibadah
                    </button>
                    <button class="sb-tool-btn" id="btnToolPenduduk" onclick="activateTool('penduduk')"
                        title="Tambah Penduduk Miskin ke peta">
                        <span class="tb-icon"><i data-lucide="home"></i></span> Penduduk
                    </button>
                    <span class="sb-tools-sep"></span>
                <?php endif; ?>
                <button class="sb-tool-btn btn-stats" id="btnStats" onclick="window._showDashboard()"
                    title="Dashboard Statistik">
                    <span class="tb-icon"><i data-lucide="bar-chart-3"></i></span> Statistik
                </button>
                <?php if (!$logged_in): ?>
                <a href="papan-kebutuhan.php" class="sb-tool-btn btn-papan"
                    title="Lihat papan kebutuhan warga miskin" target="_blank">
                    <span class="tb-icon"><i data-lucide="clipboard-list"></i></span> Kebutuhan
                </a>
                <?php endif; ?>
                <button class="sb-tool-btn btn-blankspot" id="btnBlankSpot" onclick="window._filterBlankSpot()"
                    title="Filter hanya Blank Spot" style="display:none;">
                    <span class="tb-icon"><i data-lucide="alert-triangle"></i></span>
                    <span id="blankSpotCount">0 Blank Spot</span>
                </button>
                <?php if ($is_admin): ?>
                    <span class="sb-tools-sep"></span>
                    <button class="sb-tool-btn btn-recalc" id="btnRecalcAll" onclick="window._recalcAll()"
                        title="Hitung Ulang Proximity Semua Data">
                        <span class="tb-icon"><i data-lucide="refresh-cw"></i></span> Hitung Ulang
                    </button>
                    <button class="sb-tool-btn btn-import" id="btnImportCSV"
                        onclick="document.getElementById('modalImport').classList.add('show')" title="Import Data via CSV">
                        <span class="tb-icon"><i data-lucide="file-input"></i></span> Import
                    </button>
                <?php endif; ?>
            </div>

            <!-- Data List (scrollable) -->
            <div class="sb-data">

                <!-- Seksi Rumah Ibadah -->
                <div class="dl-section" id="dlSectionIbadah">
                    <div class="dl-header" id="dlHeaderIbadah" onclick="dlToggle('Ibadah')">
                        <span class="dl-header-icon"><i data-lucide="map-pin"></i></span>
                        <span class="dl-header-label">Rumah Ibadah</span>
                        <span class="dl-header-count" id="dlCountIbadah">0</span>
                        <button class="dl-eye layer-visible" id="eyeIbadah"
                            onclick="event.stopPropagation(); toggleLayerView('ibadah')"
                            title="Tampilkan / Sembunyikan">
                            <svg class="eye-open" width="14" height="14" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                            <svg class="eye-closed" width="14" height="14" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path
                                    d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24" />
                                <line x1="1" y1="1" x2="23" y2="23" />
                            </svg>
                        </button>
                        <span class="dl-chevron"><i data-lucide="chevron-down"></i></span>
                    </div>
                    <div class="dl-list" id="dlListIbadah">
                        <div class="dl-empty">Belum ada data.</div>
                    </div>
                </div>

                <!-- Seksi Penduduk Miskin -->
                <div class="dl-section" id="dlSectionPenduduk">
                    <div class="dl-header" id="dlHeaderPenduduk" onclick="dlToggle('Penduduk')">
                        <span class="dl-header-icon"><i data-lucide="home"></i></span>
                        <span class="dl-header-label">Penduduk Miskin</span>
                        <span class="dl-header-count" id="dlCountPenduduk">0</span>
                        <span class="dl-chevron"><i data-lucide="chevron-down"></i></span>
                    </div>
                    <div class="dl-list" id="dlListPenduduk">
                        <div class="dl-empty">Belum ada data.</div>
                    </div>
                </div>

                <?php if ($is_admin || $is_op): ?>
                <!-- Seksi Analisis Kepadatan (Heatmap) -->
                <div class="dl-section">
                    <div class="dl-header" style="cursor: default; background: rgba(255,165,0,0.05);">
                        <span class="dl-header-icon"><i data-lucide="flame"></i></span>
                        <span class="dl-header-label">Heatmap Kepadatan</span>
                        <div style="flex: 1;"></div>
                        <label class="toggle-switch">
                            <input type="checkbox" id="chkHeatmap"
                                onchange="window._toggleHeatmap(this.checked);
                                          const heatFilter = document.getElementById('dlFilterHeatmap');
                                          if (heatFilter) heatFilter.style.display = this.checked ? '' : 'none';">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="dl-filter-container" id="dlFilterHeatmap" style="display:none;">
                        <div class="dl-filter-row">
                            <select id="filterHeatmapKategori" class="dl-select-filter"
                                onchange="window._heatmapSetKategori && window._heatmapSetKategori(this.value)">
                                <option value="">Semua Kategori</option>
                                <option value="Sangat Miskin">Sangat Miskin</option>
                                <option value="Miskin">Miskin</option>
                                <option value="Hampir Miskin">Hampir Miskin</option>
                            </select>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div><!-- .sb-data -->

            <!-- Legenda -->
            <div class="sb-legend">
                <div class="sb-legend-title">Legenda</div>
                <div class="sb-legend-sub">Buffer Rumah Ibadah</div>
                <div class="sb-legend-row">
                    <div
                        style="width:22px;height:8px;border-radius:3px;background:rgba(17,17,17,.08);border:1.5px solid #111111;flex-shrink:0;">
                    </div>
                    Masjid / Mushola
                </div>
                <div class="sb-legend-row">
                    <div
                        style="width:22px;height:8px;border-radius:3px;background:rgba(22,163,74,.1);border:1.5px solid #4ade80;flex-shrink:0;">
                    </div>
                    Gereja
                </div>
                <div class="sb-legend-row">
                    <div
                        style="width:22px;height:8px;border-radius:3px;background:rgba(234,88,12,.1);border:1.5px solid #f97316;flex-shrink:0;">
                    </div>
                    Pura
                </div>
                <div class="sb-legend-row">
                    <div
                        style="width:22px;height:8px;border-radius:3px;background:rgba(202,138,4,.1);border:1.5px solid #facc15;flex-shrink:0;">
                    </div>
                    Vihara
                </div>
                <div class="sb-legend-row">
                    <div
                        style="width:22px;height:8px;border-radius:3px;background:rgba(190,18,60,.1);border:1.5px solid #f43f5e;flex-shrink:0;">
                    </div>
                    Klenteng
                </div>
                <div class="sb-legend-sub">Penduduk Miskin</div>
                <div class="sb-legend-row">
                    <div style="width:12px;height:12px;border-radius:50%;background:#22c55e;flex-shrink:0;"></div>
                    Terjangkau Rumah Ibadah
                </div>
                <div class="sb-legend-row">
                    <div style="width:12px;height:12px;border-radius:50%;background:#ef4444;flex-shrink:0;"></div>
                    Blank Spot (Tidak Terjangkau)
                </div>
            </div>

        </aside><!-- .sidebar -->

        <!-- MAP AREA -->
        <div class="map-area" id="mapArea">
            <button class="sb-open-btn" id="btnOpenSidebar" onclick="toggleSidebar()"
                title="Buka panel sidebar"><i data-lucide="chevrons-right"></i></button>
            <div id="map"></div>
        </div>

    </div><!-- .app-layout -->

    <!-- Context Menu (admin only) -->
    <?php if ($is_admin): ?>
        <div class="ctx-menu" id="ctxMenu">
            <div class="ctx-menu-title">Tambah ke Peta</div>
            <div class="ctx-item" onclick="ctxAction('ibadah')">
                <span class="ctx-item-icon"><i data-lucide="map-pin"></i></span> Rumah Ibadah
            </div>
            <div class="ctx-item" onclick="ctxAction('penduduk')">
                <span class="ctx-item-icon"><i data-lucide="home"></i></span> Penduduk Miskin
            </div>
        </div>
    <?php endif; ?>

    <!-- Stats Dashboard Modal -->
    <div class="modal-overlay" id="modalStats">
        <div class="modal-container" style="width:min(760px,100%)">
            <div class="modal-header">
                <div class="modal-title">
                    <i data-lucide="bar-chart-3" style="width:18px;height:18px;"></i> Dashboard Statistik
                </div>
                <button class="modal-close" onclick="window._hideDashboard()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="statsEmptyMsg" style="display:none;text-align:center;color:var(--c-muted);padding:24px 0;font-size:13px;">
                    Belum ada data. Tambahkan rumah ibadah dan penduduk miskin terlebih dahulu.
                </div>

                <!-- Summary Cards -->
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;">
                    <div class="summary-card">
                        <div class="summary-value" id="statTotalIbadah">—</div>
                        <div class="summary-label">Rumah Ibadah</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-value" id="statTotalKK">—</div>
                        <div class="summary-label">Total KK Miskin</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-value" id="statTotalJiwa">—</div>
                        <div class="summary-label">Total Jiwa</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-value" style="color:#22c55e;" id="statJiwaTerjangkau">—</div>
                        <div class="summary-label">Jiwa Terjangkau</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-value" style="color:#ef4444;" id="statJiwaBlankSpot">—</div>
                        <div class="summary-label">Jiwa Blank Spot</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-value" style="color:var(--c-accent);" id="statPctCakupan">—</div>
                        <div class="summary-label">Cakupan Ibadah</div>
                    </div>
                    <?php if ($is_admin || $is_op): ?>
                    <div class="summary-card">
                        <div class="summary-value" style="color:#f59e0b;" id="statKebutuhanOpen">—</div>
                        <div class="summary-label">KK Butuh Bantuan</div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Kategori Chart -->
                <div class="stat-card">
                    <div class="stat-card-title">Distribusi Kategori Kemiskinan (Jiwa)</div>
                    <div class="stat-canvas-wrapper">
                        <canvas id="chartKategori"></canvas>
                    </div>
                </div>

                <!-- Rekap per Ibadah (admin/op only) -->
                <?php if ($is_admin || $is_op): ?>
                <div class="stat-card" id="rekapTableWrapper"></div>
                <div class="stat-card" id="rekapKebutuhanWrapper"></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Duplicate modal import removed — gunakan #modalImport di atas -->

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Leaflet Heatmap Plugin -->
    <script src="https://cdn.jsdelivr.net/npm/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>

    <!-- MAP_MODULES list — module loading happens after map init below -->
    <script>
        window.MAP_MODULES = <?= json_encode(array_values($map_module_urls)) ?>;
        window.MAP_MODULES.length; // referenced for test guard
    </script>

    <script>
        // ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═
        //  INISIALISASI PETA
        // ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═ ═  
        const CENTER_LAT = <?= CENTER_LAT ?>;
        const CENTER_LNG = <?= CENTER_LNG ?>;
        const ZOOM_LEVEL = <?= ZOOM_LEVEL ?>;

        const map = L.map('map', {
            center: [CENTER_LAT, CENTER_LNG],
            zoom: ZOOM_LEVEL,
            zoomControl: true
        });

        // ── Basemap Layers ──────────────────────────────────────────
        const osmLayer = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        });

        const satelliteLayer = L.tileLayer(
            'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 19,
            attribution: 'Tiles &copy; Esri &mdash; Source: Esri, DigitalGlobe, GeoEye, Earthstar Geographics, CNES/Airbus DS, USDA, USGS, AeroGRID, IGN, and the GIS User Community'
        });

        const darkLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
        });

        // ── Expose map globally so dynamically-loaded modules can access it
        window.map = map;

        // ── Add default basemap ─────────────────────────────────────────
        osmLayer.addTo(map);

        let _tileErrShown = false;
        [osmLayer, satelliteLayer, darkLayer].forEach(layer => {
            layer.on('tileerror', function () {
                if (_tileErrShown) return;
                _tileErrShown = true;
                showToast('Gagal memuat tile peta — periksa koneksi internet. Data peta tetap tersedia.', 'error');
            });
        });

        // ── Sidebar Toggle ──────────────────────────────────────────────
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.classList.toggle('collapsed');
                setTimeout(function () { map.invalidateSize(); }, 320);
            }
        }

        // ── Tool Activation ─────────────────────────────────────────────
        let _activeTool = null;
        function activateTool(name) {
            _activeTool = (_activeTool === name) ? null : name;
            window.activeTool = _activeTool;
            const mapEl = document.getElementById('map');
            ['btnToolIbadah', 'btnToolPenduduk'].forEach(function (id) {
                const btn = document.getElementById(id);
                if (btn) btn.classList.remove('active-ibadah', 'active-penduduk');
            });
            if (_activeTool === 'ibadah') {
                const b = document.getElementById('btnToolIbadah');
                if (b) b.classList.add('active-ibadah');
                if (mapEl) mapEl.classList.add('tool-active');
                if (typeof window._ibadahActivateTool === 'function') window._ibadahActivateTool();
            } else if (_activeTool === 'penduduk') {
                const b = document.getElementById('btnToolPenduduk');
                if (b) b.classList.add('active-penduduk');
                if (mapEl) mapEl.classList.add('tool-active');
                if (typeof window._pendudukActivateTool === 'function') window._pendudukActivateTool();
            } else {
                if (mapEl) mapEl.classList.remove('tool-active');
                if (typeof window._ibadahDeactivateTool   === 'function') window._ibadahDeactivateTool();
                if (typeof window._pendudukDeactivateTool === 'function') window._pendudukDeactivateTool();
            }
        }

        // ── Context Menu Action ─────────────────────────────────────────
        function ctxAction(type) {
            const ctx = document.getElementById('ctxMenu');
            if (ctx) ctx.classList.remove('show');
            activateTool(type);
        }

        async function previewCSV() {
            const file = document.getElementById('importFile').files[0];
            if (!file) return;

            const fd = new FormData();
            fd.append('csv_file', file);
            fd.append('mode',     'preview');

            document.getElementById('importPreviewArea').style.display = 'none';
            const msg = document.getElementById('importResultMsg');
            msg.style.color = 'var(--c-text)';
            msg.innerHTML = '<i data-lucide="loader-2" class="lucide animate-spin" style="width:14px;height:14px;vertical-align:middle;margin-right:4px;"></i> Menganalisis...';
            if (typeof lucide !== 'undefined') lucide.createIcons();

            try {
                const res = await fetch('api/import/csv.php', { method: 'POST', body: appendCsrf(fd) });
                const j   = await res.json();
                msg.textContent = '';

                if (j.status !== 'success') {
                    msg.innerHTML = '<i data-lucide="x-circle" style="color:var(--c-danger);vertical-align:middle;margin-right:4px;"></i> ' + escapeHTML(j.message || 'Gagal menganalisis file.');
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                    return;
                }

                // Summary
                document.getElementById('importSummary').innerHTML =
                    `<span style="color:#3fb950;font-weight:700;">${j.total_ok} baris valid</span>` +
                    (j.total_error > 0
                        ? ` &nbsp;·&nbsp; <span style="color:var(--c-danger);font-weight:700;">${j.total_error} baris error</span>`
                        : '');

                // Preview table (10 baris pertama)
                if (j.preview.length > 0) {
                    const cols = ['nama_kk','nik','jumlah_jiwa','kategori','lat','lng'];
                    const rows = j.preview.map(r =>
                        '<tr>' + cols.map(c =>
                            `<td style="padding:5px 10px;font-size:12px;border-bottom:1px solid var(--c-border)">
                                ${escapeHTML(String(r[c]??''))}</td>`
                        ).join('') + '</tr>'
                    ).join('');
                    document.getElementById('importPreviewTable').innerHTML =
                        `<table style="width:100%;border-collapse:collapse;background:var(--c-surface);
                                       border:1px solid var(--c-border);border-radius:8px;overflow:hidden;">
                            <thead><tr>${cols.map(c =>
                                `<th style="padding:6px 10px;font-size:10px;font-weight:700;
                                            color:var(--c-muted);text-transform:uppercase;
                                            background:var(--c-surface2);text-align:left;">${c}</th>`
                            ).join('')}</tr></thead>
                            <tbody>${rows}</tbody>
                         </table>
                         <div style="font-size:11px;color:var(--c-muted);margin-top:4px;">
                           Menampilkan ${j.preview.length} dari ${j.total_ok} baris valid</div>`;
                }

                // Error list
                if (j.errors.length > 0) {
                    const errHtml = j.errors.slice(0, 10).map(e =>
                        `<div style="font-size:11px;color:var(--c-danger);padding:3px 0;">
                           Baris ${e.row}: ${escapeHTML(e.error)}
                         </div>`
                    ).join('') + (j.errors.length > 10
                        ? `<div style="font-size:11px;color:var(--c-muted);">...dan ${j.errors.length - 10} error lainnya</div>`
                        : '');
                    document.getElementById('importErrorList').innerHTML =
                        `<div style="background:rgba(248,81,73,.08);border:1px solid rgba(248,81,73,.25);
                                     border-radius:8px;padding:10px 14px;">${errHtml}</div>`;
                    document.getElementById('importErrorList').style.display = '';
                } else {
                    document.getElementById('importErrorList').style.display = 'none';
                }

                document.getElementById('importPreviewArea').style.display = '';
                document.getElementById('btnDoImport').disabled = (j.total_ok === 0);
            } catch (err) {
                msg.innerHTML = '<i data-lucide="x-circle" style="color:var(--c-danger);vertical-align:middle;margin-right:4px;"></i> Gagal menghubungi server.';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        }

        async function doImport() {
            const file = document.getElementById('importFile').files[0];
            if (!file) return;

            const btn = document.getElementById('btnDoImport');
            const msg = document.getElementById('importResultMsg');
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" class="lucide animate-spin" style="width:14px;height:14px;vertical-align:middle;margin-right:4px;"></i> Mengimport...';
            msg.textContent = '';
            if (typeof lucide !== 'undefined') lucide.createIcons();

            try {
                const fd = new FormData();
                fd.append('csv_file', file);
                fd.append('mode',     'import');

                const res = await fetch('api/import/csv.php', { method: 'POST', body: appendCsrf(fd) });
                const j = await res.json();

                if (j.status === 'success') {
                    msg.style.color = '#3fb950';
                    msg.innerHTML = '<i data-lucide="check-circle" style="color:#3fb950;vertical-align:middle;margin-right:4px;"></i> ' + escapeHTML(j.message);
                    if (typeof window._pendudukReload === 'function') window._pendudukReload();
                    document.getElementById('importFile').value = '';
                    document.getElementById('importPreviewArea').style.display = 'none';
                    document.getElementById('importErrorList').style.display = 'none';
                    setTimeout(() => {
                        document.getElementById('modalImport').classList.remove('show');
                        msg.textContent = '';
                    }, 2000);
                } else {
                    msg.style.color = 'var(--c-danger)';
                    msg.innerHTML = '<i data-lucide="x-circle" style="color:var(--c-danger);vertical-align:middle;margin-right:4px;"></i> ' + escapeHTML(j.message || 'Import gagal.');
                }
            } catch (err) {
                msg.style.color = 'var(--c-danger)';
                msg.innerHTML = '<i data-lucide="x-circle" style="color:var(--c-danger);vertical-align:middle;margin-right:4px;"></i> Gagal menghubungi server.';
            }
            btn.innerHTML = '<i data-lucide="check" style="width:14px;height:14px;vertical-align:middle;margin-right:4px;"></i> Import Sekarang';
            btn.disabled = false;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        // ── Module Loader — runs after map is initialised ────────────────
        // map, osmLayer, etc. are already defined above. Modules can safely
        // reference them via closure/global when their IIFE executes.
        (function () {
            const overlay = document.getElementById('loadingOverlay');
            const modules = (window.MAP_MODULES || []).slice();
            let loaded = 0;

            function done() {
                loaded++;
                if (loaded < modules.length) return;
                try { if (typeof window.initIbadah   === 'function') window.initIbadah(); }
                catch (e) { console.error('[MAP] initIbadah error:', e); showToast('initIbadah: ' + (e && e.message || e), 'error'); }
                try { if (typeof window.initPenduduk === 'function') window.initPenduduk(); }
                catch (e) { console.error('[MAP] initPenduduk error:', e); showToast('initPenduduk: ' + (e && e.message || e), 'error'); }
                try { if (typeof lucide !== 'undefined') lucide.createIcons(); } catch (_) {}
                if (overlay) overlay.classList.add('hide');
            }

            if (!modules.length) { if (overlay) overlay.classList.add('hide'); return; }

            modules.forEach(function (src) {
                const s = document.createElement('script');
                s.src = src;
                s.onload  = done;
                s.onerror = function () { console.error('Gagal memuat modul:', src); done(); };
                document.head.appendChild(s);
            });
        })();

    </script>
</body>

</html>
