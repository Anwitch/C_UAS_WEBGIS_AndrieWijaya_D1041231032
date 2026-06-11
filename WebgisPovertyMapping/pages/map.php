<?php
require_once '../config.php';
require_once '../auth/helper.php';
require_once '../koneksi.php';
if (!is_logged_in() || !has_role('operator')) {
    header('Location: ../auth/login.php');
    exit;
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
require_password_changed('../auth/change_password.php');
$page_title    = 'Peta Interaktif';
$page_subtitle = 'Peta sebaran rumah ibadah dan penduduk miskin';
$active_nav    = 'map';
include '../includes/page-start.php';
?>

<style>
.al-content {
    padding: 0;
    overflow: hidden;
}
.map-frame-wrap {
    height: 100%;
    min-height: 0;
    background: #f5f0eb;
}
.map-frame {
    display: block;
    width: 100%;
    height: 100%;
    border: 0;
    background: #f5f0eb;
}
</style>

<div class="map-frame-wrap">
    <iframe
        class="map-frame"
        src="../index.php?embedded=1&v=20260608-ui6"
        title="Peta Interaktif"
        loading="eager"
        referrerpolicy="same-origin">
    </iframe>
</div>

<?php include '../includes/page-end.php'; ?>
