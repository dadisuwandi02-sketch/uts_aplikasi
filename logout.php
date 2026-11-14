<?php
// logout.php
define('BASE_URL', '/uts_aplikasi');
if (session_status() === PHP_SESSION_NONE) session_start();
session_destroy();
header("Location: " . BASE_URL . "/index.php");
exit;
