<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';

app_session_start();

if (isset($_SESSION['user_id'])) {
    $dest = in_array($_SESSION['role'], ['staff', 'admin'], true)
        ? APP_BASE . '/public/manage/dashboard.php'
        : APP_BASE . '/public/borrower/dashboard.php';
    header('Location: ' . $dest);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up — LendLab</title>
    <link rel="icon" type="image/svg+xml" href="<?= APP_BASE ?>/public/assets/images/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= APP_BASE ?>/public/assets/images/favicon-32x32.png">
    <meta name="app-base" content="<?= htmlspecialchars(APP_BASE) ?>">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
    <script src="<?= APP_BASE ?>/public/assets/js/app-core.js?v=<?= @filemtime(__DIR__ . '/../assets/js/app-core.js') ?: 1 ?>"></script>
    <link rel="stylesheet" href="<?= APP_BASE ?>/public/assets/css/base.css?v=<?= @filemtime(__DIR__ . '/../assets/css/base.css') ?: 1 ?>">
    <link rel="stylesheet" href="<?= APP_BASE ?>/public/assets/css/shared/snackbar.css?v=<?= @filemtime(__DIR__ . '/../assets/css/shared/snackbar.css') ?: 1 ?>">
    <link rel="stylesheet" href="<?= APP_BASE ?>/public/assets/css/pages/auth.css?v=<?= @filemtime(__DIR__ . '/../assets/css/pages/auth.css') ?: 1 ?>">
</head>

<body>
    <div class="login-wrapper">
        <!-- Left Side: Visual/Branding (Matches Login Exactly) -->
        <div class="login-left">
            <div class="login-overlay">
                <div class="brand-showcase">
                    <div class="auth-signup-s1">
                        <span class="app-logo auth-signup-s2">
                            <span class="app-logo-mark auth-signup-s3"></span>
                        </span>
                        <span class="auth-signup-s4">LendLab</span>
                    </div>
                    <h1
                        class="auth-signup-s5">
                        Equipping your IT journey.
                    </h1>
                    <p class="auth-signup-s6">
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
                        <span class="app-logo">
                            <span class="app-logo-mark"></span>
                        </span>
                        <span class="brand-name">LendLab</span>
                    </div>
                    <h2 class="view-title">Create Account</h2>
                    <p class="view-subtitle">Register below to reserve and borrow campus devices</p>

                    <form id="signup-form" novalidate>
                        <div class="grid-row grid-cols-3">
                            <div class="form-group auth-signup-s7">
                                <label class="input-label">Last Name</label>
                                <input type="text" name="last_name" class="input-field" placeholder="e.g. Dela Cruz"
                                    required>
                            </div>
                            <div class="form-group auth-signup-s7">
                                <label class="input-label">First Name</label>
                                <input type="text" name="first_name" class="input-field" placeholder="e.g. Juan"
                                    required>
                            </div>
                            <div class="form-group auth-signup-s7">
                                <label class="input-label">Middle Name</label>
                                <input type="text" name="middle_name" class="input-field" placeholder="e.g. C.">
                            </div>
                        </div>

                        <div class="grid-row grid-cols-2">
                            <div class="form-group auth-signup-s7">
                                <label class="input-label">Student / Employee ID</label>
                                <input type="text" name="id_number" class="input-field" placeholder="e.g. 202610201"
                                    inputmode="numeric" pattern="[0-9]*" maxlength="30" data-digits-only
                                    title="Numbers only" autocomplete="off" required>
                            </div>
                            <div class="form-group auth-signup-s7">
                                <label class="input-label">Phone Number</label>
                                <input type="tel" name="phone" class="input-field" placeholder="09123456789"
                                    inputmode="numeric" pattern="[0-9]{7,15}" minlength="7" maxlength="11" data-digits-only
                                    title="Numbers only (7 to 15 digits)" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="input-label">Email</label>
                            <input type="email" name="email" class="input-field" placeholder="student@univ.edu"
                                required>
                        </div>

                        <div class="grid-row grid-cols-2">
                            <div class="form-group auth-signup-s7">
                                <label class="input-label">Password</label>
                                <div class="password-wrapper">
                                    <input type="password" name="password" class="input-field"
                                        placeholder="At least 8 characters" required>
                                </div>
                            </div>
                            <div class="form-group auth-signup-s7">
                                <label class="input-label">Confirm Password</label>
                                <div class="password-wrapper">
                                    <input type="password" name="confirm_password" class="input-field"
                                        placeholder="Re-enter password" required>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary auth-signup-s8">Sign Up</button>
                    </form>
                    <div class="toggle-view-text">
                        Already have an account?
                        <a href="<?= APP_BASE ?>/public/login/login.php" class="link-text auth-signup-s9">Sign In</a>
                    </div>
                </div>
            </div>
            <!-- END SIGNUP CARD -->

        </div>
    </div>

    <script src="<?= APP_BASE ?>/public/assets/js/snackbar.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/snackbar.js') ?: 1 ?>"></script>
    <script src="<?= APP_BASE ?>/public/assets/js/password-toggle.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/password-toggle.js') ?: 1 ?>"></script>
    <script src="<?= APP_BASE ?>/public/assets/js/auth.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/auth.js') ?: 1 ?>"></script>
</body>

</html>