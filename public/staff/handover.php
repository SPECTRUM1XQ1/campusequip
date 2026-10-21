<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../src/Reservation.php';
requireRole(['admin', 'staff']);
requirePermission('can_confirm_pickup');

$pageTitle = 'Handover';
$pageStyles = ['shared/checkout'];

$reservationModel = new Reservation();
$reservationModel->expireLapsedPickups(); // drop reservations whose pick-up window has ended
$readyForCheckout = $reservationModel->listReadyForCheckout();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../../includes/html-head.php'; ?>
</head>
<body>
    <div class="dashboard-layout">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php include __DIR__ . '/../../includes/navigation/sidebar-admin.php'; ?>

        <main class="main-content main-content--fixed">
            <?php include __DIR__ . '/../../includes/navigation/topbar.php'; ?>

            <section class="requests-page requests-page--split">
                <div class="pending-grid staff-handover-s1">
                    <!-- Ready for Checkout Handover -->
                    <div class="ops-column">
                        <div class="section-header">
                            <h3 class="section-title">Ready for Handover</h3>
                            <span class="section-subtitle" data-ready-count><?= count($readyForCheckout) ?> ready</span>
                        </div>

                        <div hidden data-list data-items="#handover-container > .request-card" data-page-size="6" data-pager="#handover-pager" data-pager-always></div>
                        <div id="handover-container" class="ops-scroll ops-cards">
                            <?php if (empty($readyForCheckout)): ?>
                                <div class="card-container text-muted staff-handover-s2">
                                    No items pending handover.
                                </div>
                            <?php else: ?>
                                <?php foreach ($readyForCheckout as $row): ?>
                                    <?php include __DIR__ . '/../../includes/handover-card.php'; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="pager" id="handover-pager"></div>
                    </div>
                </div>
            </section>

            <!-- CONFIRM HANDOVER MODAL -->
<?php include __DIR__ . '/../../includes/modal/shared/handover.php'; ?>

        </main>
    </div>

    <?php include __DIR__ . '/../../includes/navigation/footer.php'; ?>
    <script src="<?= APP_BASE ?>/public/assets/js/filters.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/filters.js') ?: 1 ?>"></script>
    <script src="<?= APP_BASE ?>/public/assets/js/countdown.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/countdown.js') ?: 1 ?>"></script>

    <script src="<?= APP_BASE ?>/public/assets/js/shared/handover.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/shared/handover.js') ?: 1 ?>"></script>
</body>

</html>
