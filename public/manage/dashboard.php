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

// KPI real-time metrics. Unpaid fines are shown to admins and to staff whose job is processing returns.
$showFines = hasPermission('can_inspect_returns');
[
    'pendingRequests' => $pendingRequests, 'activeLoans' => $activeLoans, 'overdueItems' => $overdueItems,
    'availableEquipment' => $availableEquipment, 'unpaidFines' => $unpaidFines,
] = (new Dashboard())->adminStats($showFines);

// Fetch lists for widgets
$reservationModel = new Reservation();
$equipmentModel   = new Equipment();
$logModel         = new TransactionLog();

$pendingItems     = $reservationModel->listPendingDetailed();
$recentPending    = array_slice($pendingItems, 0, 3);
$breakdown        = $equipmentModel->getAvailabilityBreakdown();
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
        <?php include __DIR__ . '/../../includes/sidebar-admin.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../../includes/topbar.php'; ?>

            <!-- Real-time Stat Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-details">
                        <span class="stat-label">Total Available</span>
                        <h3 class="stat-value"><?= $availableEquipment ?></h3>
                    </div>
                    <div class="stat-icon primary">
                        <i class="ph ph-cpu" style="font-size: 1.5rem;"></i>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-details">
                        <span class="stat-label">Active Checkouts</span>
                        <h3 class="stat-value"><?= $activeLoans ?></h3>
                    </div>
                    <div class="stat-icon success">
                        <i class="ph ph-arrows-left-right" style="font-size: 1.5rem;"></i>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-details">
                        <span class="stat-label">Overdue Items</span>
                        <h3 class="stat-value"><?= $overdueItems ?></h3>
                    </div>
                    <div class="stat-icon danger">
                        <i class="ph ph-warning-circle" style="font-size: 1.5rem;"></i>
                    </div>
                </div>

                <?php if ($showFines): ?>
                <a href="<?= APP_BASE ?>/public/manage/fines.php" class="stat-card" style="text-decoration: none; color: inherit;">
                    <div class="stat-details">
                        <span class="stat-label">Unpaid Fines</span>
                        <h3 class="stat-value">&#8369;<?= number_format((float) $unpaidFines['total'], 2) ?></h3>
                        <span class="text-muted" style="font-size: 0.75rem;"><?= (int) $unpaidFines['count'] ?> unpaid</span>
                    </div>
                    <div class="stat-icon danger">
                        <i class="ph ph-receipt" style="font-size: 1.5rem;"></i>
                    </div>
                </a>
                <?php endif; ?>

                <div class="stat-card">
                    <div class="stat-details">
                        <span class="stat-label">Pending Requests</span>
                        <h3 class="stat-value" id="dash-pending-count"><?= $pendingRequests ?></h3>
                    </div>
                    <div class="stat-icon warning">
                        <i class="ph ph-hourglass-medium" style="font-size: 1.5rem;"></i>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- LEFT COLUMN -->
                <div>
                    <div class="section-header">
                        <h3 class="section-title">Pending Reservation Requests</h3>
                        <span class="section-subtitle"><?= count($pendingItems) ?> waiting review</span>
                    </div>

                    <div id="dashboard-requests-container">
                        <?php if (empty($recentPending)): ?>
                            <div class="card-container text-muted" style="text-align: center; padding: 2rem;">
                                <i class="ph ph-check-circle" style="font-size: 2rem; color: var(--success-text); margin-bottom: 0.5rem; display: block;"></i>
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
                                        <a href="<?= APP_BASE ?>/public/manage/requests.php" class="btn btn-primary btn-sm btn-rect" style="text-decoration:none;"><i class="ph ph-list-checks" aria-hidden="true"></i><span class="btn-text">Review & Assign</span></a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Equipment Availability Breakdown -->
                    <div class="card-container">
                        <h3 class="section-title" style="margin-bottom: 1.25rem;">Equipment Availability Breakdown</h3>
                        <?php if (empty($breakdown)): ?>
                            <p class="text-muted">No inventory tracked yet.</p>
                        <?php else: ?>
                            <?php foreach ($breakdown as $item): 
                                $total = max(1, (int)$item['total_units']);
                                $availPct = round(((int)$item['available_units'] / $total) * 100);
                            ?>
                                <div class="availability-row">
                                    <div class="availability-head">
                                        <span class="availability-name"><?= htmlspecialchars($item['model_name']) ?></span>
                                        <span class="availability-meta">Available: <?= (int)$item['available_units'] ?>/<?= $total ?> • Maint: <?= (int)$item['maintenance_units'] ?></span>
                                    </div>
                                    <div class="progress-track">
                                        <div class="progress-fill" style="width: <?= $availPct ?>%;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- RIGHT COLUMN -->
                <div>
                    <div class="card-container">
                        <h3 class="section-title" style="margin-bottom: 1rem;">Recent Logs</h3>
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

    <?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>