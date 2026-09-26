<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole('admin');

$pageTitle = 'System Settings';
$comingSoonMessage = 'Fine rates and loan period settings are next on the build list.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../../includes/html-head.php'; ?>
</head>
<body>

    <div class="dashboard-layout">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../../includes/topbar.php'; ?>
            <?php include __DIR__ . '/../../includes/coming-soon.php'; ?>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
