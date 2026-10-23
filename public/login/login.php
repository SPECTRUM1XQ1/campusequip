<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Settings.php';

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
    <title>Sign In — LendLab</title>
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
        <!-- Left Side: Visual/Branding -->
        <div class="login-left">
            <div class="login-overlay">
                <div class="brand-showcase">
                    <div class="auth-login-s1">
                        <span class="app-logo auth-login-s2">
                            <span class="app-logo-mark auth-login-s3"></span>
                        </span>
                        <span class="auth-login-s4">LendLab</span>
                    </div>
                    <h1
                        class="auth-login-s5">
                        Equipping your IT journey.
                    </h1>
                    <p class="auth-login-s6">
                        The official hardware and equipment management portal for PHINMA UPang IT students. Reserve
                        laptops, cameras, and kits instantly.
                    </p>
                    <div class="auth-howto">
                        <h3 class="auth-howto-title">How it works</h3>
                        <ol class="auth-steps">
                            <li><span class="step-num">1</span><span><strong>Reserve</strong> the equipment and dates you need.</span></li>
                            <li><span class="step-num">2</span><span><strong>Wait for approval</strong> from the equipment staff.</span></li>
                            <li><span class="step-num">3</span><span><strong>Pick it up</strong> at the desk with your Student ID, within your pick-up window.</span></li>
                            <li><span class="step-num">4</span><span><strong>Return it by <?= date('g:i A', strtotime(LOAN_DUE_TIME)) ?></strong> on your due date. Late returns are charged a fine per day.</span></li>
                        </ol>
                    </div>
                    <?php $deskHours = trim(Settings::get('desk_hours')); $deskContact = trim(Settings::get('desk_contact')); ?>
                    <?php if ($deskHours !== '' || $deskContact !== ''): ?>
                        <div class="auth-howto auth-help">
                            <h3 class="auth-howto-title">Equipment desk</h3>
                            <?php if ($deskHours !== ''): ?><p><i class="ph ph-clock" aria-hidden="true"></i><?= htmlspecialchars($deskHours) ?></p><?php endif; ?>
                            <?php if ($deskContact !== ''): ?><p><i class="ph ph-chat-circle-text" aria-hidden="true"></i><?= htmlspecialchars($deskContact) ?></p><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Side: The Form -->
        <div class="login-right">
            <div id="auth-container" class="auth-container max-w-signin">
                <div id="signin-view" class="view-section active-view">
                    <div class="brand-header">
                        <span class="app-logo">
                            <span class="app-logo-mark"></span>
                        </span>
                        <span class="brand-name">LendLab</span>
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
                        New to LendLab?
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