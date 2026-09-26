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
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — CampusEquip</title>
    <link rel="stylesheet" href="/campusequip/public/assets/css/style.css">
</head>

<body>

    <div class="wrapper">
        <div id="auth-container" class="auth-container max-w-signin">
            <div id="signin-view" class="view-section active-view">
                <div class="brand-header">
                    <span class="campus-equip-logo">
                        <span class="chip-icon"></span>
                    </span>
                    <span class="brand-name">CampusEquip</span>
                </div>
                <h2 class="view-title">Sign In</h2>
                <p class="view-subtitle">Access the campus hardware & equipment portal</p>

                <form id="login-form" novalidate>
                    <div class="form-group">
                        <label class="form-label">Email / Student ID</label>
                        <input type="text" name="identifier" class="input-field"
                            placeholder="e.g. 2026-10201 or student@univ.edu" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="input-field" placeholder="Enter password" required>
                    </div>

                    <button type="submit" class="btn-primary" style="margin-top: 16px;">LOGIN</button>
                </form>
                <div class="toggle-view-text">
                    New to CampusEquip?
                    <a href="/campusequip/public/signup.php" class="link-text"
                        style="text-transform: uppercase; margin-left: 4px;">SIGN UP</a>
                </div>
            </div>
        </div>
    </div>

    <script src="/campusequip/public/assets/js/snackbar.js"></script>
    <script src="/campusequip/public/assets/js/auth.js"></script>
</body>

</html>
