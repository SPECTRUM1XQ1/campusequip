<?php
define('APP_INIT', true);
require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    $dest = in_array($_SESSION['role'], ['staff', 'admin'], true)
        ? '/campusequip/public/staff/dashboard.php'
        : '/campusequip/public/borrower/dashboard.php';
    header('Location: ' . $dest);
} else {
    header('Location: ' . APP_URL . '/login.php');
}
exit;
