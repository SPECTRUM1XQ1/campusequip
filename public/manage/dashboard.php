<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../src/Reservation.php';
require_once __DIR__ . '/../../src/Equipment.php';
require_once __DIR__ . '/../../src/TransactionLog.php';
require_once __DIR__ . '/../../src/Dashboard.php';
requireRole(['admin', 'staff']);

$pageTitle = 'Dashboard Overview';
$pageStyles = ['shared/dashboard', 'pages/manage-dashboard'];

// KPI real-time metrics. Unpaid fines are shown to admins and to staff whose job is processing returns.
$showFines = hasPermission('can_inspect_returns');
[
    'pendingRequests' => $pendingRequests, 'activeLoans' => $activeLoans, 'overdueItems' => $overdueItems,
    'availableEquipment' => $availableEquipment, 'unpaidFines' => $unpaidFines,
] = (new Dashboard())->adminStats($showFines);

// Each stat card links to the page where that number can be acted on, but only if this person may open it
// (staff only get the pages their one job needs; see includes/navigation/sidebar-admin.php).
$returnsPage = (($_SESSION['role'] ?? '') === 'admin')
    ? APP_BASE . '/public/manage/checkouts.php'
    : (hasPermission('can_inspect_returns') ? APP_BASE . '/public/staff/return.php' : null);
$statLinks = [
    'available' => hasPermission('can_manage_catalog') ? APP_BASE . '/public/manage/serialized.php' : null,
    'active'    => $returnsPage,
    'overdue'   => $returnsPage ? $returnsPage . '?filter=overdue' : null,
    'pending'   => hasPermission('can_approve_requests') ? APP_BASE . '/public/manage/requests.php' : null,
];
$statOpen = function (string $key) use ($statLinks): string {
    return !empty($statLinks[$key])
        ? '<a href="' . htmlspecialchars($statLinks[$key]) . '" class="stat-card stat-card-link">'
        : '<div class="stat-card">';
};
$statClose = fn(string $key): string => !empty($statLinks[$key]) ? '</a>' : '</div>';

// Fetch lists for widgets
$reservationModel = new Reservation();
$equipmentModel   = new Equipment();
$logModel         = new TransactionLog();

$pendingItems     = $reservationModel->listPendingDetailed();
$recentPending    = $pendingItems;      // all of them: the list shows 3 per page (see data-page-size below)
$breakdown        = $equipmentModel->getAvailabilityBreakdown(50);   // shown 5 per page
$recentLogs       = $logModel->listRecent(5);
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

            <!-- Real-time Stat Grid -->
            <div class="stats-grid">
                <?= $statOpen('available') ?>
                    <div class="stat-details">
                        <span class="stat-label">Total Available</span>
                        <h3 class="stat-value"><?= $availableEquipment ?></h3>
                    </div>
                    <div class="stat-icon primary">
                        <i class="ph ph-cpu manage-dashboard-s1"></i>
                    </div>
                <?= $statClose('available') ?>

                <?= $statOpen('active') ?>
                    <div class="stat-details">
                        <span class="stat-label">Active Checkouts</span>
                        <h3 class="stat-value"><?= $activeLoans ?></h3>
                    </div>
                    <div class="stat-icon success">
                        <i class="ph ph-arrows-left-right manage-dashboard-s1"></i>
                    </div>
                <?= $statClose('active') ?>

                <?= $statOpen('overdue') ?>
                    <div class="stat-details">
                        <span class="stat-label">Overdue Items</span>
                        <h3 class="stat-value"><?= $overdueItems ?></h3>
                    </div>
                    <div class="stat-icon danger">
                        <i class="ph ph-warning-circle manage-dashboard-s1"></i>
                    </div>
                <?= $statClose('overdue') ?>

                <?php if ($showFines): ?>
                <a href="<?= APP_BASE ?>/public/manage/fines.php?filter=unpaid" class="stat-card stat-card-link manage-dashboard-s2">
                    <div class="stat-details">
                        <span class="stat-label">Unpaid Fines</span>
                        <h3 class="stat-value">&#8369;<?= number_format((float) $unpaidFines['total'], 2) ?></h3>
                        <span class="text-muted manage-dashboard-s3"><?= (int) $unpaidFines['count'] ?> unpaid</span>
                    </div>
                    <div class="stat-icon danger">
                        <i class="ph ph-receipt manage-dashboard-s1"></i>
                    </div>
                </a>
                <?php endif; ?>

                <?= $statOpen('pending') ?>
                    <div class="stat-details">
                        <span class="stat-label">Pending Requests</span>
                        <h3 class="stat-value" id="dash-pending-count"><?= $pendingRequests ?></h3>
                    </div>
                    <div class="stat-icon warning">
                        <i class="ph ph-hourglass-medium manage-dashboard-s1"></i>
                    </div>
                <?= $statClose('pending') ?>
            </div>

            <div class="dashboard-grid">
                <!-- LEFT COLUMN -->
                <div>
                    <div class="section-header">
                        <h3 class="section-title">Pending Reservation Requests</h3>
                        <span class="section-subtitle"><?= count($pendingItems) ?> waiting review</span>
                    </div>

                    <?php if (!empty($recentPending)): ?>
                        <div hidden data-list data-items="#dashboard-requests-container .request-card" data-page-size="3"
                             data-pager="#dashboard-requests-pager" data-pager-style="swipe"></div>
                    <?php endif; ?>
                    <div id="dashboard-requests-container">
                        <?php if (empty($recentPending)): ?>
                            <div class="card-container text-muted manage-dashboard-s4">
                                <i class="ph ph-check-circle manage-dashboard-s5"></i>
                                No pending requests at this time.
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentPending as $req): 
                                $days = ceil((strtotime($req['end_at']) - strtotime($req['start_at'])) / 86400);
                            ?>
                                <div class="request-card" id="req-card-<?= (int)$req['reservation_id'] ?>">
                                    <div class="request-meta">
                                        <div>
                                            <p class="meta-label">Borrower</p>
                                            <h4 class="meta-value"><?= htmlspecialchars($req['borrower_name']) ?></h4>
                                            <p class="meta-sub"><?= htmlspecialchars($req['borrower_phone'] ?: $req['borrower_id_number']) ?></p>
                                        </div>
                                        <div>
                                            <p class="meta-label">Equipment Requested</p>
                                            <h4 class="meta-value"><?= htmlspecialchars($req['model_name']) ?></h4>
                                            <p class="meta-sub"><?= count($req['available_units']) ?> unit(s) ready</p>
                                        </div>
                                        <div>
                                            <p class="meta-label">Timeline</p>
                                            <h4 class="meta-value"><?= $days ?> Day(s)</h4>
                                            <p class="meta-sub"><?= date('M d, Y', strtotime($req['start_at'])) ?></p>
                                        </div>
                                    </div>
                                    <?php if (hasPermission('can_approve_requests')): ?>
                                    <div class="request-actions">
                                        <a href="<?= APP_BASE ?>/public/manage/requests.php" class="btn btn-primary btn-sm btn-rect manage-dashboard-s6"><i class="ph ph-list-checks" aria-hidden="true"></i><span class="btn-text">Review & Assign</span></a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="pager pager--dots-only" id="dashboard-requests-pager"></div>

                    <!-- Equipment Availability Breakdown -->
                    <div class="card-container">
                        <h3 class="section-title manage-dashboard-s7">Equipment Availability Breakdown</h3>
                        <?php if (empty($breakdown)): ?>
                            <p class="text-muted">No inventory tracked yet.</p>
                        <?php else: ?>
                            <p class="availability-legend" aria-hidden="true">
                                <span><i class="seg-dot seg--available"></i>Available</span>
                                <span><i class="seg-dot seg--out"></i>Borrowed</span>
                                <span><i class="seg-dot seg--maint"></i>Maintenance</span>
                            </p>
                            <div hidden data-list data-items="#availability-list .availability-row" data-page-size="5"
                                 data-pager="#availability-pager" data-pager-style="swipe"></div>
                            <div id="availability-list">
                            <?php foreach ($breakdown as $item):
                                $total  = max(1, (int)$item['total_units']);
                                $avail  = (int)$item['available_units'];
                                $out    = (int)$item['checked_out_units'];
                                $maint  = (int)$item['maintenance_units'];
                                $pct    = fn(int $n): float => round($n / $total * 100, 2);
                            ?>
                                <div class="availability-row">
                                    <div class="availability-head">
                                        <span class="availability-name"><?= htmlspecialchars($item['model_name']) ?></span>
                                        <span class="availability-meta">
                                            Available: <?= $avail ?>/<?= $total ?>
                                            <?php if ($out > 0): ?> &bull; <span class="meta-out">Borrowed: <?= $out ?></span><?php endif; ?>
                                            <?php if ($maint > 0): ?> &bull; <span class="meta-maint">Maintenance: <?= $maint ?></span><?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="progress-track progress-track--stack" role="img"
                                         aria-label="<?= $avail ?> available, <?= $out ?> borrowed, <?= $maint ?> in maintenance, out of <?= $total ?>">
                                        <span class="seg seg--available" style="width: <?= $pct($avail) ?>%;"></span>
                                        <span class="seg seg--out" style="width: <?= $pct($out) ?>%;"></span>
                                        <span class="seg seg--maint" style="width: <?= $pct($maint) ?>%;"></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                            <div class="pager pager--dots-only" id="availability-pager"></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- RIGHT COLUMN -->
                <div>
                    <div class="card-container">
                        <h3 class="section-title manage-dashboard-s8">Recent Logs</h3>
                        <?php if (empty($recentLogs)): ?>
                            <p class="text-muted">No system transactions logged yet.</p>
                        <?php else: ?>
                            <?php foreach ($recentLogs as $log): ?>
                                <div class="log-item">
                                    <div>
                                        <h4 class="log-id">LOG-#<?= (int)$log['log_id'] ?></h4>
                                        <p class="log-name"><?= htmlspecialchars($log['borrower_name'] ?: $log['actor_name']) ?></p>
                                        <p class="log-note"><?= ucfirst(str_replace('_', ' ', htmlspecialchars($log['action_type']))) ?></p>
                                    </div>
                                    <span class="log-date"><?= date('M d, H:i', strtotime($log['action_timestamp'])) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/navigation/footer.php'; ?>
    <script src="<?= APP_BASE ?>/public/assets/js/filters.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/filters.js') ?: 1 ?>"></script>
</body>
</html>