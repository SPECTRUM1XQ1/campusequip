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
    <title>Sign In — CampusEquip</title>
    <meta name="app-base" content="<?= htmlspecialchars(APP_BASE) ?>">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
    <script src="<?= APP_BASE ?>/public/assets/js/app-core.js?v=<?= @filemtime(__DIR__ . '/../assets/js/app-core.js') ?: 1 ?>"></script>
    <link rel="stylesheet" href="<?= APP_BASE ?>/public/assets/css/base.css?v=<?= @filemtime(__DIR__ . '/../assets/css/base.css') ?: 1 ?>">
    <link rel="stylesheet" href="<?= APP_BASE ?>/public/assets/css/pages/auth.css?v=<?= @filemtime(__DIR__ . '/../assets/css/pages/auth.css') ?: 1 ?>">
</head>

<body>
    <div class="login-wrapper">
        <!-- Left Side: Visual/Branding -->
        <div class="login-left">
            <div class="login-overlay">
                <div class="brand-showcase">
                    <div class="auth-login-s1">
                        <span class="campus-equip-logo auth-login-s2">
                            <span class="chip-icon auth-login-s3"></span>
                        </span>
                        <span class="auth-login-s4">CampusEquip</span>
                    </div>
                    <h1
                        class="auth-login-s5">
                        Equipping your IT journey.
                    </h1>
                    <p class="auth-login-s6">
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
                            <label class="form-label auth-login-s7"
                               >Email
                                / ID Number</label>
                            <input type="text" name="identifier" class="input-field"
                                placeholder="e.g. 202610201 or student@univ.edu" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label auth-login-s7"
                               >Password</label>
                            <input type="password" name="password" class="input-field" placeholder="Enter password"
                                required>
                        </div>

                        <button type="submit" class="btn-primary auth-login-s8">LOGIN</button>
                    </form>
                    <div class="toggle-view-text">
                        New to CampusEquip?
                        <a href="<?= APP_BASE ?>/public/login/signup.php" class="link-text auth-login-s9"
                           >SIGN UP</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= APP_BASE ?>/public/assets/js/snackbar.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/snackbar.js') ?: 1 ?>"></script>
    <script src="<?= APP_BASE ?>/public/assets/js/password-toggle.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/password-toggle.js') ?: 1 ?>"></script>
    <script src="<?= APP_BASE ?>/public/assets/js/auth.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/auth.js') ?: 1 ?>"></script>
</body>

</html>