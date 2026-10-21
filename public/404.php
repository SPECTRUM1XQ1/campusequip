<?php
define('APP_INIT', true);
require_once __DIR__ . '/../config/config.php';

app_session_start();

http_response_code(404);

$loggedIn = isset($_SESSION['user_id']);
$homeLink = $loggedIn
    ? (in_array($_SESSION['role'] ?? '', ['staff', 'admin'], true)
        ? APP_URL . '/manage/dashboard.php'
        : APP_URL . '/borrower/dashboard.php')
    : APP_URL . '/login/login.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found — CampusEquip</title>
    <meta name="app-base" content="<?= htmlspecialchars(APP_BASE) ?>">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
    <script src="<?= APP_BASE ?>/public/assets/js/app-core.js?v=<?= @filemtime(__DIR__ . '/assets/js/app-core.js') ?: 1 ?>"></script>
    <link rel="stylesheet" href="<?= APP_BASE ?>/public/assets/css/base.css?v=<?= @filemtime(__DIR__ . '/assets/css/base.css') ?: 1 ?>">
    <link rel="stylesheet" href="<?= APP_BASE ?>/public/assets/css/pages/error-404.css?v=<?= @filemtime(__DIR__ . '/assets/css/pages/error-404.css') ?: 1 ?>">
</head>

<body>
    <div
        class="e404-s1">
        <div class="e404-s2">404</div>
        <h2 class="e404-s3">Page not found</h2>
        <p class="text-muted e404-s4">This page doesn't exist yet, or the link is incorrect.</p>
        <a href="<?= htmlspecialchars($homeLink) ?>" class="btn btn-primary e404-s5"
           >
            <?= $loggedIn ? 'Back to Dashboard' : 'Back to Sign In' ?>
        </a>
    </div>
</body>

</html>