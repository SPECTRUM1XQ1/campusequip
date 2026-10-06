<?php
define('APP_INIT', true);
require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    $dest = in_array($_SESSION['role'], ['staff', 'admin'], true)
        ? '/campusequip/public/admin/dashboard.php'
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
    <div class="login-wrapper">
        <!-- Left Side: Visual/Branding -->
        <div class="login-left">
            <div class="login-overlay">
                <div class="brand-showcase">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                        <span class="campus-equip-logo" style="width: 56px; height: 56px; border-radius: 14px;">
                            <span class="chip-icon" style="width: 32px; height: 32px;"></span>
                        </span>
                        <span style="font-size: 2.25rem; font-weight: 700; color: white;">CampusEquip</span>
                    </div>
                    <h1
                        style="color: white; font-size: 3rem; margin-bottom: 1.5rem; line-height: 1.2; font-weight: 700;">
                        Equipping your IT journey.
                    </h1>
                    <p style="color: #cbd5e1; font-size: 1.125rem; line-height: 1.7; max-width: 400px;">
                        The official hardware and equipment management portal for PHINMA UPang IT students. Reserve
                        laptops, cameras, and kits instantly.
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Side: The Form -->
        <div class="login-right">
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
                            <label class="form-label"
                                style="display: block; font-size: 0.625rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.375rem;">Email
                                / Student ID</label>
                            <input type="text" name="identifier" class="input-field"
                                placeholder="e.g. 2026-10201 or student@univ.edu" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label"
                                style="display: block; font-size: 0.625rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.375rem;">Password</label>
                            <input type="password" name="password" class="input-field" placeholder="Enter password"
                                required>
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
    </div>

    <script src="/campusequip/public/assets/js/snackbar.js"></script>
    <script src="/campusequip/public/assets/js/auth.js"></script>
</body>

</html>