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
    <script>try{var d=sessionStorage.getItem('ce-auth-dir');if(d){document.documentElement.setAttribute('data-auth-in',d);sessionStorage.removeItem('ce-auth-dir');}}catch(e){}</script>
    <meta name="app-base" content="<?= htmlspecialchars(APP_BASE) ?>">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
    <script src="<?= APP_BASE ?>/public/assets/js/app-core.js?v=<?= @filemtime(__DIR__ . '/../assets/js/app-core.js') ?: 1 ?>"></script>
    <link rel="stylesheet" href="<?= APP_BASE ?>/public/assets/vendor/phosphor/style.css?v=2.1.2">
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
            <a class="auth-back-home" href="<?= APP_URL ?>/landing.php"><i class="ph ph-arrow-left" aria-hidden="true"></i>Back to home</a>

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
                                <input type="text" name="last_name" class="input-field" placeholder="e.g. Peralta"
                                    maxlength="30" minlength="2" data-letters-only autocomplete="family-name" required>
                            </div>
                            <div class="form-group auth-signup-s7">
                                <label class="input-label">First Name</label>
                                <input type="text" name="first_name" class="input-field" placeholder="e.g. Justin"
                                    maxlength="20" minlength="2" data-letters-only autocomplete="given-name" required>
                            </div>
                            <div class="form-group auth-signup-s7">
                                <label class="input-label">Middle Name</label>
                                <input type="text" name="middle_name" class="input-field" placeholder="e.g. Candelario"
                                    maxlength="20" data-letters-only autocomplete="additional-name">
                            </div>
                        </div>

                        <div class="grid-row grid-cols-2">
                            <div class="form-group auth-signup-s7">
                                <label class="input-label">Student / Employee ID</label>
                                <input type="text" name="id_number" class="input-field" placeholder="03-01-2526-049080 or 03-1920-03027"
                                    inputmode="numeric" pattern="([0-9]{2}-[0-9]{2}-[0-9]{4}-[0-9]{6})|([0-9]{2}-[0-9]{4}-[0-9]{5})" maxlength="17"
                                    data-id-format title="Format: 03-01-2526-049080 or 03-1920-03027" autocomplete="off" required>
                                <small class="field-hint">Numbers only, the dashes are added for you.</small>
                            </div>
                            <div class="form-group auth-signup-s7">
                                <label class="input-label">Phone Number</label>
                                <input type="tel" name="phone" class="input-field" placeholder="09171234567"
                                    inputmode="numeric" pattern="09[0-9]{9}" minlength="11" maxlength="11" data-ph-phone
                                    title="Philippine mobile number, 11 digits starting with 09" autocomplete="tel-national" required>
                                <small class="field-hint">Philippine mobile number (09XXXXXXXXX).</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="input-label">Email</label>
                            <input type="email" name="email" class="input-field" placeholder="juca.peralta.up@phinmaed.com"
                                maxlength="50" data-phinma-email autocapitalize="none" spellcheck="false" autocomplete="email" required>
                            <small class="field-hint" id="email-hint">Your PHINMA email, like juca.peralta.up@phinmaed.com.</small>
                        </div>

                        <div class="grid-row grid-cols-2">
                            <div class="form-group auth-signup-s7">
                                <label class="input-label">Password</label>
                                <div class="password-wrapper">
                                    <input type="password" name="password" class="input-field"
                                        placeholder="At least 8 characters" minlength="8" maxlength="64"
                                        data-no-space autocomplete="new-password" required>
                                </div>
                                <small class="field-hint">8 to 64 characters, no spaces.</small>
                            </div>
                            <div class="form-group auth-signup-s7">
                                <label class="input-label">Confirm Password</label>
                                <div class="password-wrapper">
                                    <input type="password" name="confirm_password" class="input-field"
                                        placeholder="Re-enter password" maxlength="64"
                                        data-no-space autocomplete="new-password" required>
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