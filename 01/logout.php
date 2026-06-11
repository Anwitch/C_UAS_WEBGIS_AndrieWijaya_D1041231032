<?php
require_once __DIR__ . '/koneksi.php';
start_session();
$_SESSION = [];
clear_session_cookie();
session_destroy();
header('Location: index.php');
exit;
