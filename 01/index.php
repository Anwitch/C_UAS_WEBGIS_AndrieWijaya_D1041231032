<?php
require_once __DIR__ . '/koneksi.php';
$is_admin       = is_admin();
$admin_username = $is_admin ? ($_SESSION['admin_username'] ?? 'Admin') : '';
$admin_init     = $is_admin ? mb_strtoupper(mb_substr($admin_username, 0, 1, 'UTF-8'), 'UTF-8') : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WebGIS Pontianak — Visualisasi Choropleth</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.9.2/proj4.min.js"></script>
<style>
/* ── Reset & Tokens ─────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --sb-w:        260px;
    --sb-bg:       #1c1612;
    --sb-text:     #a89f96;
    --sb-text-h:   #ffffff;
    --sb-sect:     #5c5148;
    --hd-bg:       #fafaf9;
    --hd-border:   #ddd8d2;
    --body-bg:     #f5f0eb;
    --card-bg:     #fafaf9;
    --card-border: #ddd8d2;
    --text-1:      #201515;
    --text-2:      #3d3530;
    --text-m:      #7a7067;
    --accent:      #0d7490;
    --accent-h:    #0a5f7a;
    --accent-dim:  rgba(13,116,144,.08);
    --danger:      #ef4444;
    --danger-dim:  rgba(239,68,68,.08);
    --danger-bdr:  rgba(239,68,68,.25);
    --radius:      12px;
    --radius-sm:   8px;
    --font:        'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --shadow:      0 4px 16px rgba(32,21,21,.1);
}
html, body { height: 100%; font-family: var(--font); background: var(--body-bg); color: var(--text-1); overflow: hidden; }

/* ── App Shell ──────────────────────────────────────────────── */
.app-wrap { display: flex; height: 100vh; overflow: hidden; }

/* ── Sidebar ────────────────────────────────────────────────── */
.app-sidebar {
    width: var(--sb-w); min-width: var(--sb-w);
    background: var(--sb-bg);
    display: flex; flex-direction: column;
    height: 100vh; overflow: hidden;
    transition: width .3s cubic-bezier(.4,0,.2,1), min-width .3s;
    flex-shrink: 0; z-index: 50;
}
.app-sidebar.collapsed { width: 60px; min-width: 60px; }
.app-sidebar.collapsed .sb-hide { display: none; }
.app-sidebar.collapsed .sb-toggle-icon { transform: rotate(180deg); }
.app-sidebar.collapsed .sb-logo-text { display: none; }

.sb-logo {
    display: flex; align-items: center; gap: 10px;
    padding: 18px 16px 14px;
    border-bottom: 1px solid rgba(255,255,255,.06);
    flex-shrink: 0;
}
.sb-logo-icon {
    width: 36px; height: 36px; border-radius: 9px; background: var(--accent);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.sb-logo-icon .lucide { width: 18px; height: 18px; color: #fff; }
.sb-logo-name { font-size: 13px; font-weight: 600; color: #fff; white-space: nowrap; letter-spacing: -.3px; }
.sb-logo-sub  { font-size: 10px; color: var(--sb-text); white-space: nowrap; }

.sb-body { flex: 1; overflow-y: auto; overflow-x: hidden; padding: 12px 0; }
.sb-body::-webkit-scrollbar { width: 4px; }
.sb-body::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 4px; }

.sb-sect-label {
    font-size: 10px; font-weight: 600; color: var(--sb-sect);
    text-transform: uppercase; letter-spacing: .8px;
    padding: 10px 16px 4px; white-space: nowrap;
}

/* ── Layer list in sidebar ──────────────────────────────────── */
.layer-list { padding: 4px 8px; }
.layer-empty { font-size: 12px; color: var(--sb-text); padding: 12px 8px; line-height: 1.6; }

.layer-item {
    background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.07);
    border-radius: 9px; padding: 10px 10px 8px; margin-bottom: 6px;
    transition: background .15s;
}
.layer-item:hover { background: rgba(255,255,255,.07); }
.layer-item.is-hidden { opacity: .5; }

.layer-item-row { display: flex; align-items: center; gap: 8px; }
.layer-eye {
    width: 28px; height: 28px; border-radius: 6px;
    background: rgba(255,255,255,.08); border: none; cursor: pointer;
    color: var(--sb-text); display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; transition: background .15s, color .15s;
}
.layer-eye:hover { background: rgba(255,255,255,.15); color: #fff; }
.layer-eye .lucide { width: 14px; height: 14px; }
.layer-info { flex: 1; min-width: 0; }
.layer-name { font-size: 12px; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.layer-attr { font-size: 10px; color: var(--sb-text); margin-top: 1px; }
.layer-del {
    width: 26px; height: 26px; border-radius: 6px;
    background: transparent; border: none; cursor: pointer;
    color: var(--sb-text); display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; transition: background .15s, color .15s;
}
.layer-del:hover { background: rgba(239,68,68,.15); color: #ef4444; }
.layer-del .lucide { width: 13px; height: 13px; }

.layer-pal { display: flex; gap: 3px; margin-top: 8px; padding-left: 36px; }
.pal-dot { width: 14px; height: 8px; border-radius: 2px; }

/* ── Sidebar footer / upload btn ────────────────────────────── */
.sb-footer { padding: 10px 8px; border-top: 1px solid rgba(255,255,255,.06); flex-shrink: 0; }

.btn-upload-sb {
    display: flex; align-items: center; gap: 8px;
    width: 100%; padding: 9px 12px; border-radius: 8px;
    background: var(--accent); border: none; cursor: pointer;
    color: #fff; font-family: var(--font); font-size: 12px; font-weight: 600;
    transition: background .15s;
}
.btn-upload-sb:hover { background: var(--accent-h); }
.btn-upload-sb .lucide { width: 14px; height: 14px; flex-shrink: 0; }
.btn-upload-sb .sb-hide { white-space: nowrap; }

.sb-toggle-btn {
    display: flex; align-items: center; gap: 0;
    padding: 0 10px; height: 36px; width: 100%;
    background: transparent; border: none; border-radius: 8px; cursor: pointer;
    color: var(--sb-text); font-size: 12px; font-weight: 500; font-family: var(--font);
    transition: background .15s, color .15s; white-space: nowrap;
}
.sb-toggle-btn:hover { background: rgba(255,255,255,.06); color: var(--sb-text-h); }
.sb-toggle-icon { width: 32px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; transition: transform .3s; }
.sb-toggle-icon .lucide { width: 15px; height: 15px; }

/* ── Main area ──────────────────────────────────────────────── */
.app-main { flex: 1; display: flex; flex-direction: column; overflow: hidden; min-width: 0; }

/* ── Header ─────────────────────────────────────────────────── */
.app-header {
    height: 56px; background: var(--hd-bg); border-bottom: 1px solid var(--hd-border);
    display: flex; align-items: center; padding: 0 20px; gap: 14px; flex-shrink: 0; z-index: 40;
}
.hd-hamburger {
    width: 34px; height: 34px; background: transparent; border: none; cursor: pointer;
    border-radius: 7px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;
    transition: background .15s; flex-shrink: 0;
}
.hd-hamburger:hover { background: #ede8e2; }
.hd-hamburger span { display: block; width: 17px; height: 2px; background: var(--text-2); border-radius: 2px; }
.hd-title-wrap { flex: 1; min-width: 0; }
.hd-title    { font-size: 16px; font-weight: 600; color: var(--text-1); letter-spacing: -.3px; line-height: 1.2; }
.hd-subtitle { font-size: 11px; color: var(--text-m); margin-top: 1px; }
.hd-right { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }

.hd-status { display: flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 500; color: #16a34a; }
.hd-status-dot { width: 6px; height: 6px; border-radius: 50%; background: #16a34a; animation: pulse-dot 2s ease-in-out infinite; }
@keyframes pulse-dot { 0%,100%{opacity:1;} 50%{opacity:.4;} }

.hd-user {
    display: flex; align-items: center; gap: 9px; padding: 5px 10px;
    border-radius: 9px; border: 1px solid var(--card-border);
}
.hd-avatar {
    width: 30px; height: 30px; border-radius: 50%;
    background: #ede8e2; border: 1px solid #ddd8d2;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 600; color: var(--text-1); flex-shrink: 0;
}
.hd-user-name { font-size: 12px; font-weight: 600; color: var(--text-1); }
.hd-user-role { font-size: 10px; color: var(--text-m); }
.hd-logout {
    background: transparent; border: none; color: var(--text-m); cursor: pointer;
    padding: 3px; border-radius: 5px; transition: color .15s, background .15s;
    display: flex; align-items: center;
}
.hd-logout:hover { color: var(--danger); background: #fef2f2; }
.hd-logout .lucide { width: 14px; height: 14px; }

.btn-login-link {
    display: flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: 8px;
    background: var(--accent); color: #fff; text-decoration: none;
    font-size: 12px; font-weight: 600; transition: background .15s;
}
.btn-login-link:hover { background: var(--accent-h); }
.btn-login-link .lucide { width: 14px; height: 14px; }

/* ── Map ────────────────────────────────────────────────────── */
.map-container { flex: 1; position: relative; overflow: hidden; }
#map { width: 100%; height: 100%; }
.leaflet-pane.is-hit-disabled,
.leaflet-pane.is-hit-disabled * {
    pointer-events: none !important;
}

/* ── Legend overlay ─────────────────────────────────────────── */
.legend-panel {
    position: absolute; bottom: 24px; left: 16px;
    z-index: 1000; display: none;
    display: flex; flex-direction: column; gap: 10px;
    max-height: calc(100% - 48px); overflow-y: auto;
    pointer-events: none;
}
.leg-block {
    background: rgba(250,250,249,.94); border: 1px solid var(--card-border);
    border-radius: 10px; padding: 12px 14px;
    box-shadow: 0 2px 8px rgba(32,21,21,.1);
    pointer-events: auto; min-width: 160px;
}
.leg-title { font-size: 11px; font-weight: 600; color: var(--text-1); margin-bottom: 2px; }
.leg-attr  { font-size: 10px; color: var(--text-m); margin-bottom: 8px; }
.leg-row   { display: flex; align-items: center; gap: 7px; margin-bottom: 4px; }
.leg-swatch { width: 20px; height: 12px; border-radius: 3px; flex-shrink: 0; }
.leg-range  { font-size: 10px; color: var(--text-2); }

/* ── Choropleth popup ────────────────────────────────────────── */
.choro-popup-wrap .leaflet-popup-content-wrapper {
    border-radius: 10px; padding: 0; overflow: hidden;
    box-shadow: 0 4px 16px rgba(32,21,21,.15);
    border: 1px solid var(--card-border);
}
.choro-popup-wrap .leaflet-popup-content { margin: 0; }
.choro-popup { max-height: 260px; overflow-y: auto; font-family: var(--font); }
.choro-popup table { width: 100%; border-collapse: collapse; }
.choro-popup tr:nth-child(odd)  { background: #f5f0eb; }
.choro-popup tr:nth-child(even) { background: #fafaf9; }
.choro-popup .pp-k { padding: 5px 10px 5px 12px; font-size: 11px; font-weight: 600; color: var(--text-m); white-space: nowrap; }
.choro-popup .pp-v { padding: 5px 12px 5px 8px;  font-size: 11px; color: var(--text-1); }

/* ── Toast ───────────────────────────────────────────────────── */
.toast {
    position: fixed; bottom: 24px; right: 24px; z-index: 9999;
    padding: 11px 18px; border-radius: 9px;
    background: var(--text-1); color: #fff;
    font-size: 13px; font-weight: 500;
    box-shadow: 0 4px 14px rgba(32,21,21,.2);
    transform: translateY(12px); opacity: 0;
    transition: opacity .25s, transform .25s;
    pointer-events: none; max-width: 320px; line-height: 1.4;
}
.toast.show { opacity: 1; transform: translateY(0); }
.toast.success { background: #166534; }
.toast.error   { background: #991b1b; }

/* ── Modals ──────────────────────────────────────────────────── */
.modal-overlay {
    position: fixed; inset: 0; z-index: 8000;
    background: rgba(28,22,18,.55); backdrop-filter: blur(3px);
    display: flex; align-items: center; justify-content: center; padding: 20px;
}
.modal-box {
    background: var(--card-bg); border: 1px solid var(--card-border);
    border-radius: var(--radius); width: min(480px, 100%);
    box-shadow: var(--shadow); overflow: hidden;
}
.modal-box.modal-sm { width: min(360px, 100%); }
.modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 20px 14px; border-bottom: 1px solid var(--card-border);
}
.modal-title { font-size: 15px; font-weight: 600; color: var(--text-1); }
.modal-close {
    width: 30px; height: 30px; border-radius: 7px;
    background: transparent; border: none; cursor: pointer;
    color: var(--text-m); display: flex; align-items: center; justify-content: center;
    transition: background .15s, color .15s;
}
.modal-close:hover { background: #ede8e2; color: var(--text-1); }
.modal-close .lucide { width: 16px; height: 16px; }
.modal-body { padding: 20px; max-height: 65vh; overflow-y: auto; }

.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 12px; font-weight: 600; color: var(--text-2); margin-bottom: 6px; }
.form-group input[type="text"],
.form-group input[type="file"],
.form-group select,
.form-group textarea {
    width: 100%; padding: 10px 13px;
    background: #ede8e2; border: 1.5px solid var(--card-border); border-radius: 8px;
    color: var(--text-1); font-family: var(--font); font-size: 13px; outline: none;
    transition: border-color .2s, background .2s, box-shadow .2s;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    border-color: var(--accent); background: #fafaf9; box-shadow: 0 0 0 3px rgba(13,116,144,.08);
}
.form-group textarea { resize: vertical; min-height: 64px; }
.form-group input[type="file"] { padding: 8px 13px; cursor: pointer; }
.attr-hint { font-size: 11px; color: var(--text-m); margin-top: 5px; font-style: italic; }

.form-status { min-height: 18px; font-size: 12px; margin-top: 8px; }
.form-status.error { color: var(--danger); }
.form-status.info  { color: var(--accent); }

.modal-footer {
    display: flex; justify-content: flex-end; gap: 8px;
    padding: 14px 20px; border-top: 1px solid var(--card-border);
    background: #f5f0eb;
}
.btn-cancel {
    padding: 9px 18px; border-radius: 8px;
    background: transparent; border: 1.5px solid var(--card-border);
    color: var(--text-2); font-family: var(--font); font-size: 13px; font-weight: 600;
    cursor: pointer; transition: background .15s, border-color .15s;
}
.btn-cancel:hover { background: #ede8e2; border-color: #c8c3bd; }
.btn-primary {
    padding: 9px 20px; border-radius: 8px;
    background: var(--accent); border: none;
    color: #fff; font-family: var(--font); font-size: 13px; font-weight: 600;
    cursor: pointer; transition: background .15s; display: flex; align-items: center; gap: 7px;
}
.btn-primary:hover:not(:disabled) { background: var(--accent-h); }
.btn-primary:disabled { opacity: .6; cursor: not-allowed; }
.btn-danger {
    padding: 9px 20px; border-radius: 8px;
    background: var(--danger); border: none;
    color: #fff; font-family: var(--font); font-size: 13px; font-weight: 600;
    cursor: pointer; transition: background .15s;
}
.btn-danger:hover { background: #dc2626; }
.btn-spinner { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,.35); border-top-color: #fff; border-radius: 50%; animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.confirm-msg { font-size: 14px; color: var(--text-1); line-height: 1.6; margin-bottom: 4px; }
.confirm-sub { font-size: 12px; color: var(--text-m); margin-top: 6px; }

.pal-preview-row { display: flex; align-items: center; gap: 3px; }
.pal-dot-lg { width: 22px; height: 14px; border-radius: 3px; }

/* ── Palette preview in select ────────────────────────────── */
.pal-select-wrap { display: flex; flex-direction: column; gap: 6px; }
.pal-opt {
    display: flex; align-items: center; gap: 8px; padding: 8px 12px;
    border-radius: 8px; border: 1.5px solid var(--card-border); cursor: pointer;
    transition: border-color .15s, background .15s;
}
.pal-opt.selected { border-color: var(--accent); background: var(--accent-dim); }
.pal-opt-dots { display: flex; gap: 2px; }
.pal-opt-dot { width: 16px; height: 10px; border-radius: 2px; }
.pal-opt-name { font-size: 12px; color: var(--text-2); font-weight: 500; }

/* Lucide default sizing */
.lucide { width: 16px; height: 16px; stroke-width: 2; display: inline-block; vertical-align: middle; }

/* ── Drawing toolbar ────────────────────────────────────────── */
.ft-toolbar {
    position: absolute; top: 12px; left: 12px; z-index: 1000;
    display: flex; gap: 4px;
    background: rgba(250,250,249,.96); border: 1px solid var(--card-border);
    border-radius: 10px; padding: 5px;
    box-shadow: 0 2px 10px rgba(32,21,21,.1);
}
.ft-btn {
    display: flex; align-items: center; gap: 6px; padding: 7px 13px;
    border-radius: 7px; background: transparent; border: none; cursor: pointer;
    font-family: var(--font); font-size: 12px; font-weight: 600; color: var(--text-2);
    transition: background .15s, color .15s; white-space: nowrap;
}
.ft-btn:hover               { background: #ede8e2; }
.ft-btn.active-point        { background: rgba(46,160,67,.12);  color: #2ea043; }
.ft-btn.active-jalan        { background: rgba(56,139,253,.12); color: #388bfd; }
.ft-btn.active-parsil       { background: rgba(234,179,8,.15);  color: #a87c00; }
.ft-btn .lucide             { width: 14px; height: 14px; }

/* ── Draw hint ───────────────────────────────────────────────── */
.draw-hint {
    position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%);
    z-index: 1000; background: rgba(28,22,18,.88); color: #fff;
    border-radius: 9px; padding: 9px 18px; font-size: 12px; font-weight: 500;
    display: none; align-items: center; gap: 10px; white-space: nowrap; backdrop-filter: blur(4px);
    box-shadow: 0 2px 8px rgba(0,0,0,.25);
}
.draw-hint.visible { display: flex; }
.draw-hint kbd {
    background: rgba(255,255,255,.2); border-radius: 4px;
    padding: 1px 6px; font-size: 11px; font-family: var(--font);
}
.draw-hint-actions { display: flex; align-items: center; gap: 6px; }
.draw-hint-btn {
    border: 1px solid rgba(255,255,255,.25);
    background: rgba(255,255,255,.12);
    color: #fff;
    border-radius: 6px;
    padding: 5px 9px;
    font-family: var(--font);
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
}
.draw-hint-btn:hover { background: rgba(255,255,255,.2); }
.draw-hint-btn.primary { background: var(--accent); border-color: var(--accent); }
.draw-hint-btn.primary:hover { background: var(--accent-h); border-color: var(--accent-h); }

/* ── Jalan / Parsil inline legends ──────────────────────────── */
.side-legend {
    position: absolute; right: 12px; bottom: 44px; z-index: 1000;
    background: rgba(250,250,249,.96); border: 1px solid var(--card-border);
    border-radius: 10px; padding: 12px 14px; min-width: 150px;
    box-shadow: 0 2px 8px rgba(32,21,21,.1); display: none;
}
.side-legend.visible { display: block; }

/* ── Classic data rows ───────────────────────────────────────── */
.classic-section { padding: 0 8px 8px; }
.classic-row {
    display: flex; align-items: center; gap: 8px;
    padding: 7px 8px; border-radius: 7px; margin-bottom: 3px;
    transition: background .15s;
}
.classic-row:hover { background: rgba(255,255,255,.05); }
.classic-count { font-size: 10px; color: var(--sb-text); margin-top: 1px; }
</style>
</head>
<body>
<div class="app-wrap">

    <!-- ═══ SIDEBAR ═══ -->
    <aside class="app-sidebar" id="appSidebar">

        <div class="sb-logo">
            <div class="sb-logo-icon"><i data-lucide="map"></i></div>
            <div class="sb-logo-text">
                <div class="sb-logo-name">WebGIS Pontianak</div>
                <div class="sb-logo-sub">Visualisasi Wilayah</div>
            </div>
        </div>

        <div class="sb-body">
            <div class="sb-sect-label sb-hide">Layer Choropleth</div>
            <div class="layer-list" id="layerList">
                <div class="layer-empty">Memuat layer...</div>
            </div>

            <div class="sb-sect-label sb-hide" style="margin-top:6px;">Data Klasik</div>
            <div class="classic-section">
                <div class="classic-row">
                    <button class="layer-eye" id="eyeWrapPoint" onclick="toggleClassic('point')" title="Toggle Lokasi Usaha">
                        <i data-lucide="eye" id="eyeClassicPoint"></i>
                    </button>
                    <div class="layer-info">
                        <div class="layer-name">📍 Lokasi Usaha</div>
                        <div class="classic-count" id="countPoint">Memuat...</div>
                    </div>
                </div>
                <div class="classic-row">
                    <button class="layer-eye" id="eyeWrapJalan" onclick="toggleClassic('jalan')" title="Toggle Data Jalan">
                        <i data-lucide="eye" id="eyeClassicJalan"></i>
                    </button>
                    <div class="layer-info">
                        <div class="layer-name">🛣️ Data Jalan</div>
                        <div class="classic-count" id="countJalan">Memuat...</div>
                    </div>
                </div>
                <div class="classic-row">
                    <button class="layer-eye" id="eyeWrapParsil" onclick="toggleClassic('parsil')" title="Toggle Parsil Tanah">
                        <i data-lucide="eye" id="eyeClassicParsil"></i>
                    </button>
                    <div class="layer-info">
                        <div class="layer-name">🏘️ Parsil Tanah</div>
                        <div class="classic-count" id="countParsil">Memuat...</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sb-footer">
            <?php if ($is_admin): ?>
            <button class="btn-upload-sb" onclick="openUploadModal()" style="margin-bottom:6px;">
                <i data-lucide="upload-cloud"></i>
                <span class="sb-hide">Upload Layer</span>
            </button>
            <?php endif; ?>
            <button class="sb-toggle-btn" onclick="toggleSidebar()">
                <span class="sb-toggle-icon"><i data-lucide="chevron-left"></i></span>
                <span class="sb-hide">Sembunyikan</span>
            </button>
        </div>

    </aside><!-- .app-sidebar -->

    <!-- ═══ MAIN ═══ -->
    <div class="app-main">

        <header class="app-header">
            <button class="hd-hamburger" onclick="toggleSidebar()" aria-label="Toggle sidebar">
                <span></span><span></span><span></span>
            </button>
            <div class="hd-title-wrap">
                <div class="hd-title">WebGIS Pontianak</div>
                <div class="hd-subtitle">Choropleth — Data Spasial Wilayah</div>
            </div>
            <div class="hd-right">
                <div class="hd-status"><div class="hd-status-dot"></div>Online</div>
                <?php if ($is_admin): ?>
                <div class="hd-user">
                    <div class="hd-avatar"><?= htmlspecialchars($admin_init) ?></div>
                    <div>
                        <div class="hd-user-name"><?= htmlspecialchars($admin_username) ?></div>
                        <div class="hd-user-role">Administrator</div>
                    </div>
                    <a href="logout.php" class="hd-logout" title="Keluar"><i data-lucide="log-out"></i></a>
                </div>
                <?php else: ?>
                <a href="login.php" class="btn-login-link">
                    <i data-lucide="log-in"></i> Masuk sebagai Admin
                </a>
                <?php endif; ?>
            </div>
        </header>

        <div class="map-container">
            <div id="map"></div>
            <div class="legend-panel" id="legendContainer" style="display:none;"></div>

            <?php if ($is_admin): ?>
            <div class="ft-toolbar" id="ftToolbar">
                <button class="ft-btn" id="btnToolPoint" onclick="activateTool('point')" title="Tambah Lokasi Usaha">
                    <i data-lucide="map-pin"></i> Lokasi
                </button>
                <button class="ft-btn" id="btnToolJalan" onclick="activateTool('jalan')" title="Gambar Segmen Jalan">
                    <i data-lucide="route"></i> Jalan
                </button>
                <button class="ft-btn" id="btnToolParsil" onclick="activateTool('parsil')" title="Gambar Persil Tanah">
                    <i data-lucide="pentagon"></i> Parsil
                </button>
            </div>
            <?php endif; ?>

            <div class="draw-hint" id="drawHint">
                <span>
                    ✏️ Klik titik-titik di peta &nbsp;·&nbsp;
                    <kbd>Enter</kbd> / 2&times;klik selesai &nbsp;·&nbsp;
                    <kbd>Esc</kbd> batal
                </span>
                <span class="draw-hint-actions">
                    <button type="button" class="draw-hint-btn primary" onclick="finishActiveDrawing()">Selesai</button>
                    <button type="button" class="draw-hint-btn" onclick="cancelActiveDrawing()">Batal</button>
                </span>
            </div>

            <!-- Legends for classic vector layers -->
            <div class="side-legend" id="legendJalan">
                <div class="leg-title">Status Jalan</div>
                <div class="leg-row"><span class="leg-swatch" style="background:#ef4444"></span><span class="leg-range">Nasional</span></div>
                <div class="leg-row"><span class="leg-swatch" style="background:#f97316"></span><span class="leg-range">Provinsi</span></div>
                <div class="leg-row"><span class="leg-swatch" style="background:#eab308"></span><span class="leg-range">Kabupaten</span></div>
            </div>
            <div class="side-legend" id="legendParsil">
                <div class="leg-title">Status Kepemilikan</div>
                <div class="leg-row"><span class="leg-swatch" style="background:#3b82f6"></span><span class="leg-range">SHM</span></div>
                <div class="leg-row"><span class="leg-swatch" style="background:#8b5cf6"></span><span class="leg-range">HGB</span></div>
                <div class="leg-row"><span class="leg-swatch" style="background:#ec4899"></span><span class="leg-range">HGU</span></div>
                <div class="leg-row"><span class="leg-swatch" style="background:#14b8a6"></span><span class="leg-range">HP</span></div>
            </div>
        </div>

    </div><!-- .app-main -->
</div><!-- .app-wrap -->

<!-- ═══ UPLOAD MODAL (admin only) ═══ -->
<?php if ($is_admin): ?>
<div id="uploadModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <span class="modal-title">Upload Layer GeoJSON</span>
            <button class="modal-close" onclick="closeUploadModal()"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>Nama Layer *</label>
                <input type="text" id="uNama" placeholder="cth. Kepadatan Penduduk Kecamatan" maxlength="150">
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <textarea id="uDesc" placeholder="Keterangan singkat tentang layer ini (opsional)"></textarea>
            </div>
            <div class="form-group">
                <label>File GeoJSON * <span style="font-weight:400;color:var(--text-m);">(.json / .geojson dari ArcGIS)</span></label>
                <input type="file" id="uFile" accept=".json,.geojson,application/json">
                <div class="attr-hint" id="fileHint">Pilih file untuk melihat atribut yang tersedia.</div>
            </div>
            <div class="form-group" id="attrGroup" style="display:none;">
                <label>Atribut untuk Choropleth * <span style="font-weight:400;color:var(--text-m);">(kolom yang akan menentukan warna)</span></label>
                <select id="uAttr"></select>
            </div>
            <div class="form-group">
                <label>Palette Warna</label>
                <div class="pal-select-wrap" id="palSelect">
                    <div class="pal-opt selected" data-pal="blue" onclick="selectPal(this,'blue')">
                        <div class="pal-opt-dots">
                            <div class="pal-opt-dot" style="background:#eff3ff;"></div>
                            <div class="pal-opt-dot" style="background:#bdd7e7;"></div>
                            <div class="pal-opt-dot" style="background:#6baed6;"></div>
                            <div class="pal-opt-dot" style="background:#2171b5;"></div>
                            <div class="pal-opt-dot" style="background:#084594;"></div>
                        </div>
                        <span class="pal-opt-name">Biru</span>
                    </div>
                    <div class="pal-opt" data-pal="orange" onclick="selectPal(this,'orange')">
                        <div class="pal-opt-dots">
                            <div class="pal-opt-dot" style="background:#feedde;"></div>
                            <div class="pal-opt-dot" style="background:#fdbe85;"></div>
                            <div class="pal-opt-dot" style="background:#fd8d3c;"></div>
                            <div class="pal-opt-dot" style="background:#e6550d;"></div>
                            <div class="pal-opt-dot" style="background:#a63603;"></div>
                        </div>
                        <span class="pal-opt-name">Oranye</span>
                    </div>
                    <div class="pal-opt" data-pal="green" onclick="selectPal(this,'green')">
                        <div class="pal-opt-dots">
                            <div class="pal-opt-dot" style="background:#edf8e9;"></div>
                            <div class="pal-opt-dot" style="background:#bae4b3;"></div>
                            <div class="pal-opt-dot" style="background:#74c476;"></div>
                            <div class="pal-opt-dot" style="background:#31a354;"></div>
                            <div class="pal-opt-dot" style="background:#006d2c;"></div>
                        </div>
                        <span class="pal-opt-name">Hijau</span>
                    </div>
                    <div class="pal-opt" data-pal="purple" onclick="selectPal(this,'purple')">
                        <div class="pal-opt-dots">
                            <div class="pal-opt-dot" style="background:#f2f0f7;"></div>
                            <div class="pal-opt-dot" style="background:#cbc9e2;"></div>
                            <div class="pal-opt-dot" style="background:#9e9ac8;"></div>
                            <div class="pal-opt-dot" style="background:#756bb1;"></div>
                            <div class="pal-opt-dot" style="background:#54278f;"></div>
                        </div>
                        <span class="pal-opt-name">Ungu</span>
                    </div>
                </div>
            </div>
            <div class="form-status" id="uploadStatus"></div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeUploadModal()">Batal</button>
            <button class="btn-primary" id="btnUpload" onclick="submitUpload()">
                <i data-lucide="upload-cloud"></i> Upload Layer
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ═══ CONFIRM MODAL ═══ -->
<div id="confirmModal" class="modal-overlay" style="display:none;">
    <div class="modal-box modal-sm">
        <div class="modal-header">
            <span class="modal-title">Konfirmasi</span>
        </div>
        <div class="modal-body">
            <p class="confirm-msg" id="confirmMsg"></p>
            <p class="confirm-sub">Tindakan ini tidak dapat dibatalkan.</p>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="resolveConfirm(false)">Batal</button>
            <button class="btn-danger" onclick="resolveConfirm(true)">Ya, Hapus</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="toast" role="alert" aria-live="polite"></div>

<script>
// ── Globals ──────────────────────────────────────────────────
window._IS_ADMIN = <?= $is_admin ? 'true' : 'false' ?>;

function escapeHTML(s) {
    return String(s ?? '')
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

// ── Toast ────────────────────────────────────────────────────
let _toastTimer;
function showToast(msg, type) {
    const el = document.getElementById('toast');
    el.textContent = msg;
    el.className = 'toast show' + (type ? ' ' + type : '');
    clearTimeout(_toastTimer);
    _toastTimer = setTimeout(() => { el.className = 'toast'; }, 3200);
}

// ── Confirm dialog ───────────────────────────────────────────
let _confirmResolve = null;
function showDeleteConfirm(msg) {
    return new Promise(resolve => {
        _confirmResolve = resolve;
        document.getElementById('confirmMsg').textContent = msg;
        document.getElementById('confirmModal').style.display = 'flex';
    });
}
function resolveConfirm(val) {
    document.getElementById('confirmModal').style.display = 'none';
    if (_confirmResolve) { _confirmResolve(val); _confirmResolve = null; }
}
document.getElementById('confirmModal').addEventListener('click', function(e) {
    if (e.target === this) resolveConfirm(false);
});

// ── Sidebar toggle ───────────────────────────────────────────
function toggleSidebar() {
    document.getElementById('appSidebar').classList.toggle('collapsed');
    if (typeof map !== 'undefined') setTimeout(() => map.invalidateSize(), 310);
}

// ── Upload modal ─────────────────────────────────────────────
<?php if ($is_admin): ?>
let _selectedPal = 'blue';

function selectPal(el, pal) {
    _selectedPal = pal;
    document.querySelectorAll('.pal-opt').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
}

function openUploadModal() {
    document.getElementById('uploadModal').style.display = 'flex';
    document.getElementById('uNama').focus();
}
function closeUploadModal() {
    document.getElementById('uploadModal').style.display = 'none';
    document.getElementById('uNama').value = '';
    document.getElementById('uDesc').value = '';
    document.getElementById('uFile').value = '';
    document.getElementById('attrGroup').style.display = 'none';
    document.getElementById('fileHint').textContent = 'Pilih file untuk melihat atribut yang tersedia.';
    document.getElementById('uploadStatus').textContent = '';
    document.getElementById('uploadStatus').className = 'form-status';
    document.getElementById('btnUpload').disabled = false;
    _selectedPal = 'blue';
    document.querySelectorAll('.pal-opt').forEach(o => o.classList.remove('selected'));
    document.querySelector('[data-pal="blue"]').classList.add('selected');
}
document.getElementById('uploadModal').addEventListener('click', function(e) {
    if (e.target === this) closeUploadModal();
});

// UTM zone defs for common Indonesia zones
if (typeof proj4 !== 'undefined') {
    proj4.defs('EPSG:32647', '+proj=utm +zone=47 +datum=WGS84 +units=m +no_defs'); // Sumatera barat
    proj4.defs('EPSG:32648', '+proj=utm +zone=48 +datum=WGS84 +units=m +no_defs'); // Sumatera timur / Kalbar barat
    proj4.defs('EPSG:32649', '+proj=utm +zone=49 +datum=WGS84 +units=m +no_defs'); // Kalbar / Kalteng
    proj4.defs('EPSG:32650', '+proj=utm +zone=50 +datum=WGS84 +units=m +no_defs'); // Kaltim / Sulawesi barat
    proj4.defs('EPSG:32651', '+proj=utm +zone=51 +datum=WGS84 +units=m +no_defs'); // Sulawesi tengah
    proj4.defs('EPSG:32752', '+proj=utm +zone=52 +south +datum=WGS84 +units=m +no_defs'); // NTB
    proj4.defs('EPSG:32754', '+proj=utm +zone=54 +south +datum=WGS84 +units=m +no_defs'); // Maluku
}

// Guess UTM CRS from a sample coordinate [x, y]
function guessUtmCrs(x, y) {
    if (typeof proj4 === 'undefined') return null;
    // x is easting (~100k-900k for UTM), y is northing
    if (x < 100000 || x > 900000) return null;
    // Estimate longitude from easting: CM of each zone = zone*6 - 183
    // easting = 500000 + (lng - CM)*scale; approximate: lng ≈ (x-500000)/111320 + CM
    const lngApprox = (x - 500000) / 111320 * (180 / Math.PI); // rough
    const candidates = [
        { epsg: 'EPSG:32647', cm: 99,  south: false },
        { epsg: 'EPSG:32648', cm: 105, south: false },
        { epsg: 'EPSG:32649', cm: 111, south: false },
        { epsg: 'EPSG:32650', cm: 117, south: false },
        { epsg: 'EPSG:32651', cm: 123, south: false },
    ];
    let best = null, bestDist = Infinity;
    for (const c of candidates) {
        const approxLng = c.cm + lngApprox;
        // Try converting and check if result is plausible Indonesia coords
        try {
            const [lng, lat] = proj4(c.epsg, 'WGS84', [x, y]);
            if (lng >= 94 && lng <= 142 && lat >= -12 && lat <= 8) {
                const dist = Math.abs(lng - 108); // rough centre of Indonesia
                if (dist < bestDist) { bestDist = dist; best = c.epsg; }
            }
        } catch (_) {}
    }
    return best;
}

// Reproject all coordinates in a GeoJSON FeatureCollection
function reprojectGeoJSON(gj, fromCRS) {
    function rc(c) { return proj4(fromCRS, 'WGS84', c); }
    function rr(ring) { return ring.map(rc); }
    function rg(geom) {
        if (!geom) return geom;
        switch (geom.type) {
            case 'Point':           return { ...geom, coordinates: rc(geom.coordinates) };
            case 'MultiPoint':
            case 'LineString':      return { ...geom, coordinates: geom.coordinates.map(rc) };
            case 'MultiLineString':
            case 'Polygon':         return { ...geom, coordinates: geom.coordinates.map(rr) };
            case 'MultiPolygon':    return { ...geom, coordinates: geom.coordinates.map(p => p.map(rr)) };
            default: return geom;
        }
    }
    return { ...gj, features: gj.features.map(f => ({ ...f, geometry: rg(f.geometry) })) };
}

// Convert ArcGIS / non-standard JSON → standard GeoJSON FeatureCollection
function esriToGeoJSON(data) {
    if (data.type === 'FeatureCollection') return data; // already standard

    // GeometryCollection: wrap each geometry as a Feature (no attribute data)
    if (data.type === 'GeometryCollection') {
        return {
            type: 'FeatureCollection',
            _srcType: 'GeometryCollection',
            features: (data.geometries || []).map((geom, i) => ({
                type: 'Feature',
                properties: { id: i + 1 },
                geometry: geom,
            })),
        };
    }

    // ESRI JSON (from ArcGIS REST / JSON export)
    const firstFeat = Array.isArray(data.features) ? data.features[0] : null;
    if (!data.geometryType && !(firstFeat && firstFeat.attributes)) return data; // unknown format

    function convertGeom(g) {
        if (!g) return null;
        if (g.rings)           return { type: 'Polygon',         coordinates: g.rings };
        if (g.paths)           return { type: 'MultiLineString', coordinates: g.paths };
        if (g.points)          return { type: 'MultiPoint',      coordinates: g.points };
        if (g.x !== undefined) return { type: 'Point',           coordinates: [g.x, g.y] };
        return null;
    }
    return {
        type: 'FeatureCollection',
        _srcType: 'ESRI',
        features: (data.features || []).map(f => ({
            type: 'Feature',
            properties: f.attributes || {},
            geometry: convertGeom(f.geometry),
        })),
    };
}

let _parsedGeoJSON = null;

document.getElementById('uFile').addEventListener('change', function() {
    _parsedGeoJSON = null;
    const file = this.files[0];
    if (!file) return;
    const hint = document.getElementById('fileHint');
    hint.textContent = 'Membaca file...';
    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const raw = JSON.parse(e.target.result);
            const gj  = esriToGeoJSON(raw);
            if (!gj || gj.type !== 'FeatureCollection' || !gj.features?.length) {
                hint.textContent = 'File bukan GeoJSON FeatureCollection yang valid.';
                document.getElementById('attrGroup').style.display = 'none';
                return;
            }
            const props = gj.features[0].properties || {};
            const keys  = Object.keys(props);
            if (keys.length === 0) {
                hint.textContent = 'Fitur tidak memiliki atribut/properties.';
                document.getElementById('attrGroup').style.display = 'none';
                return;
            }
            // Check if coordinates look projected (not lat/lng) and try to reproject
            let reprojectNote = '';
            try {
                const c0 = gj.features[0]?.geometry?.coordinates;
                const sample = Array.isArray(c0?.[0]?.[0]) ? c0[0][0] : (Array.isArray(c0?.[0]) ? c0[0] : c0);
                if (sample && (Math.abs(sample[0]) > 180 || Math.abs(sample[1]) > 90)) {
                    const crs = guessUtmCrs(sample[0], sample[1]);
                    if (crs) {
                        Object.assign(gj, reprojectGeoJSON(gj, crs));
                        reprojectNote = ` · Koordinat direprojeksikan dari ${crs} ke WGS84 otomatis.`;
                    } else {
                        reprojectNote = ' ⚠️ Koordinat bukan lat/lng dan CRS tidak terdeteksi — posisi di peta mungkin salah.';
                    }
                }
            } catch (reprojErr) {
                reprojectNote = ' ⚠️ Gagal reproject koordinat: ' + reprojErr.message;
            }

            if (gj._srcType === 'GeometryCollection' && keys.length <= 1) {
                // Only has auto-generated 'id', warn but still allow upload
                hint.style.color = '#b45309';
                hint.textContent = `⚠️ File tidak memiliki atribut data (hanya geometri). Layer akan tampil sebagai peta wilayah dengan warna nomor urut.${reprojectNote}`;
            } else {
                hint.style.color = reprojectNote.startsWith(' ⚠️') ? '#b45309' : '';
                const srcNote = gj._srcType && gj._srcType !== 'GeometryCollection' ? ` (dikonversi dari ${gj._srcType})` : '';
                hint.textContent = `Terdeteksi ${gj.features.length.toLocaleString('id-ID')} fitur · ${keys.length} atribut${srcNote}.${reprojectNote}`;
            }

            _parsedGeoJSON = gj;
            const sel = document.getElementById('uAttr');
            sel.innerHTML = keys.map(k => `<option value="${escapeHTML(k)}">${escapeHTML(k)}</option>`).join('');
            document.getElementById('attrGroup').style.display = 'block';
        } catch {
            hint.textContent = 'Gagal membaca file. Pastikan format JSON/GeoJSON valid.';
            document.getElementById('attrGroup').style.display = 'none';
        }
    };
    reader.readAsText(file);
});

async function submitUpload() {
    const nama     = document.getElementById('uNama').value.trim();
    const deskripsi= document.getElementById('uDesc').value.trim();
    const file     = document.getElementById('uFile').files[0];
    const attrSel  = document.getElementById('uAttr');
    const attr_key = attrSel.value.trim();
    const statusEl = document.getElementById('uploadStatus');
    const btn      = document.getElementById('btnUpload');

    if (!nama) { statusEl.textContent = 'Nama layer wajib diisi.'; statusEl.className = 'form-status error'; return; }
    if (!_parsedGeoJSON) { statusEl.textContent = 'Pilih file GeoJSON terlebih dahulu (atau tunggu file selesai dibaca).'; statusEl.className = 'form-status error'; return; }
    if (!attr_key) { statusEl.textContent = 'Pilih atribut untuk choropleth.'; statusEl.className = 'form-status error'; return; }

    btn.disabled = true;
    statusEl.textContent = 'Mengupload...';
    statusEl.className = 'form-status info';

    const gjBlob = new Blob([JSON.stringify(_parsedGeoJSON)], { type: 'application/json' });
    const fd = new FormData();
    fd.append('nama',          nama);
    fd.append('deskripsi',     deskripsi);
    fd.append('geojson',       gjBlob, file ? file.name : 'layer.geojson');
    fd.append('attribute_key', attr_key);
    fd.append('palette',       _selectedPal);

    try {
        const res = await fetch('api/choropleth/upload.php', { method: 'POST', body: fd });
        const j   = await res.json();
        if (j.status !== 'success') throw new Error(j.message);

        const newMeta = {
            id:            j.id,
            nama:          nama,
            deskripsi:     deskripsi,
            attribute_key: attr_key,
            palette:       _selectedPal,
            is_visible:    true,
        };
        closeUploadModal();
        showToast(`Layer "${escapeHTML(nama)}" berhasil diupload!`, 'success');
        if (typeof window._choro !== 'undefined') window._choro.addLayer(newMeta);
    } catch (err) {
        statusEl.textContent = err.message || 'Gagal mengupload layer.';
        statusEl.className = 'form-status error';
        btn.disabled = false;
    }
}
<?php endif; ?>

// ── Globals required by old modules (point/jalan/parsil) ────
var currentMode    = 0;  // 0=view, 1=point, 2=jalan/parsil
var currentSubMode = '';
var activeTool     = null;
window._dlFocusFns = {};

window._resetActiveTool = function() {
    activeTool = null; currentMode = 0; currentSubMode = '';
    setDrawBlockingLayerHitTesting(true);
    setChoroplethHitTesting(true);
    ['Point','Jalan','Parsil'].forEach(function(t) {
        var btn = document.getElementById('btnTool' + t);
        if (btn) btn.className = 'ft-btn';
    });
    var lJ = document.getElementById('legendJalan');
    var lP = document.getElementById('legendParsil');
    var dH = document.getElementById('drawHint');
    if (lJ) lJ.classList.remove('visible');
    if (lP) lP.classList.remove('visible');
    if (dH) dH.classList.remove('visible');
    var mc = document.getElementById('map');
    if (mc) mc.style.cursor = '';
};

function activateTool(tool) {
    if (activeTool === tool) tool = null; // toggle off
    if (typeof window._jalanStopDraw  === 'function') window._jalanStopDraw();
    if (typeof window._parsilStopDraw === 'function') window._parsilStopDraw();
    activeTool = tool;

    ['Point','Jalan','Parsil'].forEach(function(t) {
        var btn = document.getElementById('btnTool' + t);
        if (btn) btn.className = 'ft-btn' + (tool === t.toLowerCase() ? ' active-' + t.toLowerCase() : '');
    });

    var lJ = document.getElementById('legendJalan');
    var lP = document.getElementById('legendParsil');
    var dH = document.getElementById('drawHint');
    if (lJ) lJ.classList.remove('visible');
    if (lP) lP.classList.remove('visible');

    if (tool === 'point') {
        currentMode = 1; currentSubMode = 'point';
        setDrawBlockingLayerHitTesting(false);
        setChoroplethHitTesting(false);
        if (dH) dH.classList.add('visible');
    } else if (tool === 'jalan') {
        currentMode = 2; currentSubMode = 'jalan';
        setDrawBlockingLayerHitTesting(false);
        setChoroplethHitTesting(false);
        if (lJ) lJ.classList.add('visible');
        if (dH) dH.classList.add('visible');
        if (typeof window._jalanStartDraw === 'function') window._jalanStartDraw();
    } else if (tool === 'parsil') {
        currentMode = 2; currentSubMode = 'parsil';
        setDrawBlockingLayerHitTesting(false);
        setChoroplethHitTesting(false);
        if (lP) lP.classList.add('visible');
        if (dH) dH.classList.add('visible');
        if (typeof window._parsilStartDraw === 'function') window._parsilStartDraw();
    } else {
        currentMode = 0; currentSubMode = '';
        setDrawBlockingLayerHitTesting(true);
        setChoroplethHitTesting(true);
        if (dH) dH.classList.remove('visible');
    }

    var mc = document.getElementById('map');
    if (mc) mc.style.cursor = tool ? 'crosshair' : '';
}

function finishActiveDrawing() {
    if (activeTool === 'jalan' && typeof window._jalanFinishDraw === 'function') {
        window._jalanFinishDraw();
    } else if (activeTool === 'parsil' && typeof window._parsilFinishDraw === 'function') {
        window._parsilFinishDraw();
    }
}

function cancelActiveDrawing() {
    if (activeTool === 'jalan' && typeof window._jalanStopDraw === 'function') {
        window._jalanStopDraw();
    } else if (activeTool === 'parsil' && typeof window._parsilStopDraw === 'function') {
        window._parsilStopDraw();
    } else if (typeof window._resetActiveTool === 'function') {
        window._resetActiveTool();
    }
}

function updateCount(n, icon) {
    const m = { '📍': 'countPoint', '🛣️': 'countJalan', '🏘️': 'countParsil' };
    const el = document.getElementById(m[icon]);
    if (el) el.textContent = n + ' data';
}
window.dlRefreshList = function(type, items) {
    const m = { 'Point': 'countPoint', 'Jalan': 'countJalan', 'Parsil': 'countParsil' };
    const el = document.getElementById(m[type]);
    if (el) el.textContent = (items?.length ?? 0) + ' data';
};

var _classicVisible = { point: true, jalan: true, parsil: true };
function setPaneHitTesting(paneName, enabled) {
    if (typeof map === 'undefined' || !map.getPane) return;
    const pane = map.getPane(paneName);
    if (!pane) return;
    pane.style.pointerEvents = enabled ? '' : 'none';
    pane.classList.toggle('is-hit-disabled', !enabled);
}

function setDrawBlockingLayerHitTesting(enabled) {
    ['choroplethPane', 'parsilPane', 'jalanPane', 'pointPane', 'drawPane'].forEach(function(paneName) {
        setPaneHitTesting(paneName, enabled);
    });
}

function setChoroplethHitTesting(enabled) {
    setPaneHitTesting('choroplethPane', enabled);
    if (window._choro && typeof window._choro.setHitTesting === 'function') {
        window._choro.setHitTesting(enabled);
    }
}

function toggleClassic(type) {
    _classicVisible[type] = !_classicVisible[type];
    const v   = _classicVisible[type];
    const cap = type.charAt(0).toUpperCase() + type.slice(1);
    const ic  = document.getElementById('eyeClassic' + cap);
    if (ic) { ic.setAttribute('data-lucide', v ? 'eye' : 'eye-off'); lucide.createIcons(); }
    if (type === 'point'  && typeof window._pointToggleLayer  === 'function') _pointToggleLayer(v);
    if (type === 'jalan'  && typeof window._jalanToggleLayer  === 'function') _jalanToggleLayer(v);
    if (type === 'parsil' && typeof window._parsilToggleLayer === 'function') _parsilToggleLayer(v);
}

// ── Map init ────────────────────────────────────────────────
const map = L.map('map', {
    center: [-0.0557, 109.3487],
    zoom:   13,
    zoomControl: false,
});

[
    ['choroplethPane', 410],
    ['parsilPane',     430],
    ['jalanPane',      440],
    ['pointPane',      620],
    ['drawPane',       630],
].forEach(function ([name, zIndex]) {
    map.createPane(name);
    map.getPane(name).style.zIndex = zIndex;
});

L.control.zoom({ position: 'bottomright' }).addTo(map);

// Base layers
const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19,
});
const cartoLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> © <a href="https://carto.com/">CARTO</a>',
    maxZoom: 19,
});
cartoLayer.addTo(map);

L.control.layers(
    { 'Carto Light': cartoLayer, 'OpenStreetMap': osmLayer },
    {},
    { position: 'topright' }
).addTo(map);

// Boot Lucide
lucide.createIcons();
</script>

<!-- Load choropleth module then init -->
<script src="modules/choropleth.js?v=<?= filemtime(__DIR__ . '/modules/choropleth.js') ?>"></script>
<script>
    if (typeof window.initChoropleth === 'function') window.initChoropleth();
</script>

<!-- Load classic data modules (point/jalan/parsil) — view-only, currentMode=0 -->
<script src="modules/point.js?v=<?= filemtime(__DIR__ . '/modules/point.js') ?>"></script>
<script src="modules/jalan.js?v=<?= filemtime(__DIR__ . '/modules/jalan.js') ?>"></script>
<script src="modules/parsil.js?v=<?= filemtime(__DIR__ . '/modules/parsil.js') ?>"></script>
<script>
    if (typeof initPoint  === 'function') initPoint();
    if (typeof initJalan  === 'function') initJalan();
    if (typeof initParsil === 'function') initParsil();
</script>
</body>
</html>
