<?php
define('APP_INIT', true);
require_once __DIR__ . '/../config/config.php';

app_session_start();

if (isset($_SESSION['user_id'])) {
    $dest = in_array($_SESSION['role'], ['staff', 'admin'], true)
        ? APP_BASE . '/public/manage/dashboard.php'
        : APP_BASE . '/public/borrower/dashboard.php';
    header('Location: ' . $dest);
} else {
    header('Location: ' . APP_URL . '/login/login.php');
}
exit;
