<?php
// api/auth/logout.php — Hancurkan sesi dan redirect ke login
require_once '../../config.php';
require_once '../../auth/helper.php';

session_destroy();
header('Location: ../../auth/login.php');
exit;
