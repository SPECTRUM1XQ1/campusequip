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
    <title>Sign Up — CampusEquip</title>
    <link rel="stylesheet" href="/campusequip/public/assets/css/style.css">
</head>

<body>
    <div class="login-wrapper">
        <!-- Left Side: Visual/Branding (Matches Login Exactly) -->
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

        <!-- Right Side: The Signup Form -->
        <div class="login-right">

            <!-- THIS IS YOUR EXISTING SIGNUP CARD -->
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
                                <input type="text" name="last_name" class="input-field" placeholder="e.g. Dela Cruz"
                                    required>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="input-label">First Name</label>
                                <input type="text" name="first_name" class="input-field" placeholder="e.g. Juan"
                                    required>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="input-label">Middle Name</label>
                                <input type="text" name="middle_name" class="input-field" placeholder="e.g. C.">
                            </div>
                        </div>

                        <div class="grid-row grid-cols-2">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="input-label">Student / Employee ID</label>
                                <input type="text" name="school_id" class="input-field" placeholder="e.g. 2026-10201"
                                    required>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="input-label">Phone Number</label>
                                <input type="tel" name="phone" class="input-field" placeholder="0912 345 6789" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="input-label">Email</label>
                            <input type="email" name="email" class="input-field" placeholder="student@univ.edu"
                                required>
                        </div>

                        <div class="grid-row grid-cols-2">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="input-label">Password</label>
                                <div class="password-wrapper">
                                    <input type="password" name="password" class="input-field"
                                        placeholder="At least 8 characters" required>
                                </div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="input-label">Confirm Password</label>
                                <div class="password-wrapper">
                                    <input type="password" name="confirm_password" class="input-field"
                                        placeholder="Re-enter password" required>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary" style="margin-top: 0.5rem;">Sign Up</button>
                    </form>
                    <div class="toggle-view-text">
                        Already have an account?
                        <a href="/campusequip/public/login.php" class="link-text" style="margin-left: 4px;">Sign In</a>
                    </div>
                </div>
            </div>
            <!-- END SIGNUP CARD -->

        </div>
    </div>

    <script src="/campusequip/public/assets/js/snackbar.js"></script>
    <script src="/campusequip/public/assets/js/auth.js"></script>
</body>

</html>