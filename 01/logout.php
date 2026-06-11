<?php
require_once __DIR__ . '/auth/helper.php';
start_session();
session_destroy();
header('Location: index.php');
exit;
