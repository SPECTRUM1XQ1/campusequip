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
    <title>Sign Up — CampusEquip</title>
    <link rel="stylesheet" href="/campusequip/public/assets/css/style.css">
</head>

<body>

    <div class="wrapper">
        <div id="auth-container" class="auth-container max-w-signup">
            <div id="signup-view" class="view-section active-view">
                <div class="brand-header">
                    <span class="campus-equip-logo">
                        <span class="chip-icon"></span>
                    </span>
                    <span class="brand-name">CampusEquip</span>
                </div>
                <h2 class="view-title">Create Account</h2>
                <p class="view-subtitle">Register below to reserve and borrow campus devices</p>

                <form id="signup-form" novalidate>
                    <div class="grid-row grid-cols-3">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="input-label">Last Name</label>
                            <input type="text" name="last_name" class="input-field" placeholder="Rosario" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="input-label">First Name</label>
                            <input type="text" name="first_name" class="input-field" placeholder="Aian" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="input-label">Middle Name</label>
                            <input type="text" name="middle_name" class="input-field" placeholder="C.">
                        </div>
                    </div>

                    <div class="grid-row grid-cols-2">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="input-label">Student / Employee ID</label>
                            <input type="text" name="id_number" class="input-field" placeholder="2026-10201" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="input-label">Phone Number</label>
                            <input type="text" name="phone" class="input-field" placeholder="0951 582 5198">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="input-label">Email</label>
                        <input type="email" name="email" class="input-field" placeholder="aian@univ.edu" required>
                    </div>

                    <div class="grid-row grid-cols-2">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="input-label">Password</label>
                            <input type="password" name="password" class="input-field" placeholder="At least 8 characters" required minlength="8">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="input-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="input-field" placeholder="Re-enter password" required minlength="8">
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" style="margin-top: 16px;">Sign Up</button>
                </form>

                <div class="toggle-view-text">
                    Already have an account?
                    <a href="/campusequip/public/login.php" class="link-text" style="margin-left: 4px;">Sign In</a>
                </div>
            </div>
        </div>
    </div>

    <script src="/campusequip/public/assets/js/snackbar.js"></script>
    <script src="/campusequip/public/assets/js/auth.js"></script>
</body>

</html>
