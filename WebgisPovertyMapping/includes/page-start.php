<?php
/**
 * includes/page-start.php
 * Shared admin layout — output HTML shell + sidebar + header + open main content.
 *
 * Required vars (set by including page before this include):
 *   string $page_title    — e.g. 'Dashboard Administrator'
 *   string $page_subtitle — e.g. 'Ringkasan situasi kemiskinan...'  (can be '')
 *   string $active_nav    — slug of active nav item:
 *                           'dashboard' | 'map' | 'ibadah' | 'penduduk' |
 *                           'kebutuhan' | 'analisis' | 'status' | 'laporan' |
 *                           'import' | 'users' | 'settings' | 'audit'
 *
 * Session must already be started; auth must be checked by calling page.
 */

$_nav_role  = get_role();
$_nav_nama  = get_user_nama();
$_nav_init  = mb_strtoupper(mb_substr(trim($_nav_nama), 0, 1, 'UTF-8'), 'UTF-8');

// Calculate relative path from the calling script's directory back to app root
$_app_root   = str_replace('\\', '/', dirname(__DIR__)); // parent of includes/ = app root
$_script_dir = str_replace('\\', '/', dirname(realpath($_SERVER['SCRIPT_FILENAME'])));
$_rel        = ltrim(str_replace($_app_root, '', $_script_dir), '/');
$_depth      = ($_rel === '') ? 0 : (substr_count($_rel, '/') + 1);
$_root       = str_repeat('../', $_depth);

function _sb_item(string $href, string $icon, string $label, string $slug, string $active): string {
    $cls = ($active === $slug) ? 'sb-item active' : 'sb-item';
    $h   = htmlspecialchars($href);
    $l   = htmlspecialchars($label);
    return "<a href=\"{$h}\" class=\"{$cls}\"><span class=\"sb-icon\"><i data-lucide=\"{$icon}\"></i></span><span class=\"sb-label\">{$l}</span></a>\n";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> — <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $_root ?>css/admin.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
    window.APP_CSRF_TOKEN = <?= json_encode(is_logged_in() ? csrf_token() : null) ?>;
    window.APP_ROOT = <?= json_encode($_root) ?>;
    function appendCsrf(fd) {
        const token = window.APP_USER?.csrfToken || window.APP_CSRF_TOKEN || null;
        if (token) fd.append('csrf_token', token);
        return fd;
    }
    </script>
</head>
<body>
<div class="al-wrap">

    <!-- ═══ SIDEBAR ═══ -->
    <aside class="al-sidebar" id="alSidebar">

        <div class="sb-logo">
            <div class="sb-logo-icon" style="color: #fff;"><i data-lucide="map"></i></div>
            <div class="sb-logo-text">
                <div class="sb-logo-name"><?= APP_NAME ?></div>
                <div class="sb-logo-sub">Pemetaan Kemiskinan</div>
            </div>
        </div>

        <nav class="sb-nav">
            <div class="sb-section-label">Navigasi Utama</div>

            <?php if ($_nav_role === 'administrator'): ?>
                <?= _sb_item("{$_root}dashboard.php",          'layout-dashboard', 'Dashboard',                'dashboard',  $active_nav) ?>
                <?= _sb_item("{$_root}pages/map.php",          'map',              'Peta Interaktif',          'map',        $active_nav) ?>
                <?= _sb_item("{$_root}pages/ibadah.php",       'map-pin',          'Rumah Ibadah',             'ibadah',     $active_nav) ?>
                <?= _sb_item("{$_root}pages/penduduk.php",     'home',             'Penduduk Miskin',          'penduduk',   $active_nav) ?>
                <?= _sb_item("{$_root}pages/kebutuhan.php",    'heart',            'Kebutuhan & Papan Publik', 'kebutuhan',  $active_nav) ?>
                <?= _sb_item("{$_root}pages/analisis.php",     'alert-triangle',   'Analisis & Blank Spot',    'analisis',   $active_nav) ?>
                <?= _sb_item("{$_root}pages/status-bantuan.php",'clipboard-list',   'Status Bantuan',           'status',     $active_nav) ?>
                <?= _sb_item("{$_root}pages/laporan.php",      'file-text',        'Laporan & Export',         'laporan',    $active_nav) ?>
                <?= _sb_item("{$_root}pages/import.php",       'file-input',       'Import CSV',               'import',     $active_nav) ?>

                <div class="sb-section-label">Manajemen</div>
                <?= _sb_item("{$_root}pages/users.php",        'users',            'Pengguna & Akun',          'users',      $active_nav) ?>
            <?php elseif ($_nav_role === 'operator'): ?>
                <?= _sb_item("{$_root}pages/map.php",          'map',              'Peta Interaktif',          'map',        $active_nav) ?>
                <?= _sb_item("{$_root}pages/penduduk.php",     'home',             'Penduduk Miskin',          'penduduk',   $active_nav) ?>
                <?= _sb_item("{$_root}pages/kebutuhan.php",    'heart',            'Kebutuhan & Papan Publik', 'kebutuhan',  $active_nav) ?>
                <?= _sb_item("{$_root}pages/status-bantuan.php",'clipboard-list',   'Status Bantuan',           'status',     $active_nav) ?>
            <?php else: ?>
                <?= _sb_item("{$_root}pages/map.php",          'map',              'Peta Interaktif',          'map',        $active_nav) ?>
            <?php endif; ?>
        </nav>

        <div class="sb-footer">
            <button class="sb-toggle-btn" onclick="toggleAdminSidebar()" title="Sembunyikan / Tampilkan Menu">
                <span class="sb-toggle-icon"><i data-lucide="chevron-left"></i></span>
                <span class="sb-toggle-label">Sembunyikan Menu</span>
            </button>
        </div>

    </aside><!-- .al-sidebar -->

    <!-- ═══ MAIN ═══ -->
    <div class="al-main">

        <header class="al-header">
            <button class="hd-hamburger" onclick="toggleAdminSidebar()" aria-label="Toggle sidebar">
                <span></span><span></span><span></span>
            </button>

            <div class="hd-title-block">
                <div class="hd-title"><?= htmlspecialchars($page_title) ?></div>
                <?php if (!empty($page_subtitle)): ?>
                <div class="hd-subtitle"><?= htmlspecialchars($page_subtitle) ?></div>
                <?php endif; ?>
            </div>

            <div class="hd-right">
                <div class="hd-status">
                    <div class="hd-status-dot"></div>
                    Online
                </div>

                <div class="notif-wrap" id="notif-wrap">
                    <button class="hd-notif-btn" title="Notifikasi" id="hd-notif-btn">
                        <i data-lucide="bell"></i>
                        <span class="hd-notif-badge" id="hd-notif-badge" style="display:none;">0</span>
                    </button>
                    <div class="notif-panel" id="notif-panel" style="display:none;" role="dialog" aria-label="Notifikasi">
                        <div class="notif-panel-header">
                            <span class="notif-panel-title">Notifikasi</span>
                            <button class="notif-panel-close" id="notif-close" aria-label="Tutup"><i data-lucide="x"></i></button>
                        </div>
                        <div class="notif-list" id="notif-list">
                            <div class="notif-loading">Memuat…</div>
                        </div>
                    </div>
                </div>

                <div class="hd-user">
                    <div class="hd-avatar"><?= htmlspecialchars($_nav_init ?: '?') ?></div>
                    <div>
                        <div class="hd-user-name"><?= htmlspecialchars($_nav_nama) ?></div>
                        <div class="hd-user-role"><?= ucfirst(htmlspecialchars($_nav_role)) ?></div>
                    </div>
                    <a href="<?= $_root ?>api/auth/logout.php" class="hd-logout-btn" title="Keluar"><i data-lucide="log-out"></i></a>
                </div>
            </div>
        </header>

        <main class="al-content">
<!-- page content starts here -->
