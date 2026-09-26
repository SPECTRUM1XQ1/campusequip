<?php
define('APP_INIT', true);
require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

http_response_code(404);

$loggedIn = isset($_SESSION['user_id']);
$homeLink = $loggedIn
    ? (in_array($_SESSION['role'] ?? '', ['staff', 'admin'], true)
        ? APP_URL . '/staff/dashboard.php'
        : APP_URL . '/borrower/dashboard.php')
    : APP_URL . '/login.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found — CampusEquip</title>
    <link rel="stylesheet" href="/campusequip/public/assets/css/style.css">
</head>
<body>
    <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:100vh; text-align:center; padding:2rem;">
        <div style="font-size:4rem; font-weight:800; color:var(--primary-brand); line-height:1;">404</div>
        <h2 style="margin:0.75rem 0 0.5rem;">Page not found</h2>
        <p class="text-muted" style="margin-bottom:1.75rem;">This page doesn't exist yet, or the link is incorrect.</p>
        <a href="<?= htmlspecialchars($homeLink) ?>" class="btn-primary" style="text-decoration:none; display:inline-block; padding:11px 28px;">
            <?= $loggedIn ? 'Back to Dashboard' : 'Back to Sign In' ?>
        </a>
    </div>
</body>
</html>
