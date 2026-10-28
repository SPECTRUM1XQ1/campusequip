<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../src/Equipment.php';
require_once __DIR__ . '/../../src/Reservation.php';
require_once __DIR__ . '/../../src/Fine.php';
require_once __DIR__ . '/../../src/Dashboard.php';
require_once __DIR__ . '/../../includes/helpers/pickup-helpers.php';
requireRole('borrower');

$pageTitle = 'Home Overview';
$pageStyles = ['shared/borrower', 'shared/dashboard', 'pages/borrower-dashboard'];
$userId = (int) $_SESSION['user_id'];

// Cancel approved reservations whose pick-up window has ended before we list anything.
(new Reservation())->expireLapsedPickups();

// Every list and number on this page comes from Dashboard (no SQL in the page).
[
    'myPending'     => $myPending,     'myApproved' => $myApproved,   'myCheckouts' => $myCheckouts,
    'accruing'      => $accruing,      'accruingTotal' => $accruingTotal,
    'unpaidTotal'   => $unpaidTotal,   'unpaidCount' => $unpaidCount,
    'paidTotal'     => $paidTotal,     'paidCount' => $paidCount,     'owedTotal' => $owedTotal,
] = (new Dashboard())->borrowerSummary($userId);
$peso = fn(float $n) => '&#8369;' . number_format($n, 2);

// 5. Quick Available catalog items
$catalogList = (new Equipment())->listCatalog();
$popularCatalog = array_slice($catalogList, 0, 5);
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

        <main class="main-content">
            <?php include __DIR__ . '/../../includes/navigation/topbar.php'; ?>

            <div class="dashboard-grid">
                <div class="left-column">
                    <!-- Request Pending -->
                    <div class="section-header">
                        <h3 class="section-title">Pending Requests</h3>
                        <span class="section-subtitle">Awaiting staff sign-off</span>
                    </div>

                    <?php if (!empty($myPending)): ?>
                        <div hidden data-list data-items="#pending-grid > .item-card" data-page-size="4"
                             data-pager="#pending-pager" data-pager-always data-pager-style="swipe"></div>
                    <?php endif; ?>
                    <div class="pending-grid borrower-dashboard-s1" id="pending-grid">
                        <?php if (empty($myPending)): ?>
                            <div class="item-card bg-white text-muted borrower-dashboard-s2">
                                No pending equipment requests.
                            </div>
                        <?php else: ?>
                            <?php foreach ($myPending as $pend): ?>
                                <div class="item-card bg-white" id="borrower-req-<?= (int)$pend['reservation_id'] ?>">
                                    <h5><?= htmlspecialchars($pend['model_name']) ?></h5>
                                    <p>Pick-up window (once approved):<br><?= pickupWindowFor($pend)['text'] ?></p>
                                    <div class="card-actions">
                                        <span class="badge-warning">Pending Review</span>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-cancel-reservation="<?= (int)$pend['reservation_id'] ?>"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="pager" id="pending-pager"></div>

                    <!-- Fines & penalties summary -->
                    <div class="section-header">
                        <h3 class="section-title">Penalties &amp; Fines</h3>
                        <a href="<?= APP_BASE ?>/public/borrower/fines.php" class="section-link">View details</a>
                    </div>
                    <div class="fines-board-host">
                    <div class="fines-board <?= $owedTotal > 0 ? 'fines-board--owing' : 'fines-board--clear' ?>">
                        <div class="fines-board-main">
                            <span class="fines-board-icon"><i class="ph <?= $owedTotal > 0 ? 'ph-warning-circle' : 'ph-check-circle' ?>"></i></span>
                            <div class="fines-board-owed">
                                <span class="fines-board-label">You owe right now</span>
                                <strong class="fines-board-amount"><?= $peso($owedTotal) ?></strong>
                                <span class="fines-board-sub">Growing fines + charged &amp; unpaid</span>
                            </div>
                            <?php if ($accruingTotal > 0): ?>
                                <span class="pill pill--danger">Overdue</span>
                            <?php elseif ($unpaidTotal > 0): ?>
                                <span class="pill pill--warning">Unpaid</span>
                            <?php else: ?>
                                <span class="pill pill--success">All clear</span>
                            <?php endif; ?>
                        </div>

                        <div class="fines-board-stats">
                            <a class="fines-board-stat fines-board-stat-link" href="<?= APP_BASE ?>/public/borrower/fines.php#accruing-list">
                                <span class="fines-board-stat-label">Growing daily</span>
                                <strong class="<?= $accruingTotal > 0 ? 'is-danger' : '' ?>"><?= $peso($accruingTotal) ?></strong>
                                <span class="fines-board-stat-note">Running late fines</span>
                            </a>
                            <a class="fines-board-stat fines-board-stat-link" href="<?= APP_BASE ?>/public/borrower/fines.php#accruing-list">
                                <span class="fines-board-stat-label">Overdue items</span>
                                <strong class="<?= count($accruing) > 0 ? 'is-danger' : '' ?>"><?= count($accruing) ?></strong>
                                <span class="fines-board-stat-note">Not yet returned</span>
                            </a>
                            <a class="fines-board-stat fines-board-stat-link" href="<?= APP_BASE ?>/public/borrower/fines.php#charged-list">
                                <span class="fines-board-stat-label">Charged &amp; unpaid</span>
                                <strong class="<?= $unpaidTotal > 0 ? 'is-danger' : '' ?>"><?= $peso($unpaidTotal) ?></strong>
                                <span class="fines-board-stat-note"><?= $unpaidCount ?> fine<?= $unpaidCount === 1 ? '' : 's' ?></span>
                            </a>
                            <a class="fines-board-stat fines-board-stat-link" href="<?= APP_BASE ?>/public/borrower/fines.php#charged-list">
                                <span class="fines-board-stat-label">Paid so far</span>
                                <strong class="is-success"><?= $peso($paidTotal) ?></strong>
                                <span class="fines-board-stat-note"><?= $paidCount ?> fine<?= $paidCount === 1 ? '' : 's' ?></span>
                            </a>
                        </div>

                        <?php if ($accruingTotal > 0): ?>
                            <div class="notice notice--danger fines-board-notice">
                                <i class="ph ph-info"></i>
                                <span>Late fines grow every day until you return the item. The final amount is charged when you return it at the Lab Desk.</span>
                            </div>
                        <?php elseif ($unpaidTotal > 0): ?>
                            <div class="notice notice--info fines-board-notice">
                                <i class="ph ph-info"></i>
                                <span>Please settle your unpaid fines at the Lab Desk.</span>
                            </div>
                        <?php else: ?>
                            <p class="fines-board-notice fines-clear-note">No fines. Return items on time to keep it that way.</p>
                        <?php endif; ?>
                    </div>
                    </div>

                    <!-- Approved Pickups -->
                    <div class="section-header">
                        <h3 class="section-title">Approved Requests (Ready at Lab Desk)</h3>
                    </div>

                    <?php if (empty($myApproved)): ?>
                        <div class="item-card bg-white text-muted borrower-dashboard-s3">
                            No items ready for pickup.
                        </div>
                    <?php else: ?>
                        <div class="loan-grid">
                        <?php foreach ($myApproved as $appr): ?>
                            <div class="item-card bg-white borrower-dashboard-s4">
                                <div>
                                    <div class="request-sn-status">
                                        <h5><?= htmlspecialchars($appr['model_name']) ?> <span class="sn">(SN: <?= htmlspecialchars($appr['serial_number']) ?>)</span></h5>
                                        <span class="status-badge approved">Ready for Pickup</span>
                                    </div>
                                    <p class="borrower-dashboard-s5">Approved: <?= date('M d, Y', strtotime($appr['reviewed_at'])) ?> &bull; Present your Student ID at the desk</p>
                                    <?php $apprWin = pickupWindowFor($appr); ?>
                                    <p class="borrower-dashboard-s5">Pick-up window: <strong><?= $apprWin['text'] ?></strong></p>
                                    <?= pickupCountdownTag($apprWin, '', 'Pick-up window ended — this reservation is cancelled') ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="right-column">
                    <div class="section-header">
                        <h3 class="section-title">Currently Possessed</h3>
                    </div>

                    <?php if (empty($myCheckouts)): ?>
                        <div class="item-card bg-white text-muted borrower-dashboard-s3">
                            You currently have no checked-out equipment.
                        </div>
                    <?php else: ?>
                        <?php foreach ($myCheckouts as $loan): ?>
                            <div class="item-card bg-white loan-mini">
                                <h5><?= htmlspecialchars($loan['model_name']) ?> (SN: <?= htmlspecialchars($loan['serial_number']) ?>)</h5>
                                <p class="borrower-dashboard-s5">Checked Out: <?= date('M d, Y', strtotime($loan['checked_out_at'])) ?></p>
                                <p class="borrower-dashboard-s6">Due: <?= date('M d, Y h:i A', strtotime($loan['due_at'])) ?></p>
                                <?php $rate = (float) $loan['late_fine_rate']; $late = (int) $loan['days_late']; ?>
                                <?php if ((int) $loan['is_overdue'] === 1): ?>
                                    <p class="loan-fine loan-fine--danger">
                                        OVERDUE &mdash; <?= $late ?> day<?= $late === 1 ? '' : 's' ?> late<?= $rate > 0 ? ' &bull; Fine so far: ' . $peso($rate * $late) . ' (' . $late . ' &times; ' . $peso($rate) . '/day), still growing' : '' ?>
                                    </p>
                                <?php elseif ($rate > 0): ?>
                                    <p class="loan-fine">Late fine if returned after the due date: <?= $peso($rate) ?>/day</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="section-header">
                        <h3 class="section-title">Available Equipment</h3>
                        <a href="<?= APP_BASE ?>/public/borrower/catalog.php" class="section-link">Browse Catalog</a>
                    </div>

                    <div class="card-container">
                        <?php foreach ($popularCatalog as $cat): ?>
                            <div class="list-item">
                                <div class="list-info">
                                    <h5><?= htmlspecialchars($cat['model_name']) ?></h5>
                                    <p class="text-success"><?= (int)($cat['available_units'] ?? 0) ?> Available</p>
                                </div>
                                <a href="<?= APP_BASE ?>/public/borrower/catalog.php" class="btn btn-sm btn-outline borrower-dashboard-s7"><i class="ph ph-eye" aria-hidden="true"></i><span class="btn-text">View</span></a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/navigation/footer.php'; ?>
    <script src="<?= APP_BASE ?>/public/assets/js/countdown.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/countdown.js') ?: 1 ?>"></script>

    <script src="<?= APP_BASE ?>/public/assets/js/pages/borrower-dashboard.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/pages/borrower-dashboard.js') ?: 1 ?>"></script>
    <script src="<?= APP_BASE ?>/public/assets/js/filters.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/filters.js') ?: 1 ?>"></script>
</body>
</html>