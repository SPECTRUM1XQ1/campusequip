<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../src/TransactionLog.php';
requireRole(['admin', 'staff']);

$pageTitle = 'Transaction Log';
$pageStyles = ['pages/manage-logs'];
$logModel = new TransactionLog();
$logs = $logModel->listAll();

$badgeStyles = [
    'requested'     => 'pending',
    'approved'      => 'approved',
    'declined'      => 'declined-solid',
    'cancelled'     => 'declined',
    'handover'      => 'approved',
    'returned'      => 'approved',
    'returned_late' => 'returned',
    'fine_charged'  => 'declined',
    'fine_paid'     => 'approved',
    'fine_waived'   => 'pending',
];

// Which filter pill each action belongs to.
$logTags = [
    'requested'     => 'requests',
    'cancelled'     => 'cancel',
    'approved'      => 'approved',
    'declined'      => 'declined',
    'handover'      => 'handover',
    'returned'      => 'returns',
    'returned_late' => 'returns',
    'fine_charged'  => 'fines',
    'fine_paid'     => 'fines',
    'fine_waived'   => 'fines',
];
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

            <section class="requests-page">
                <p class="page-note">Historical immutable audit trail of all transactions and equipment handovers</p>

                <div class="requests-toolbar">
                    <p class="results-count">
                        Showing <strong id="visible-count"><?= min(count($logs), 15) ?></strong> of <strong id="total-count"><?= count($logs) ?></strong> log entries
                    </p>
                    <div class="filter-group" role="group" aria-label="Filter logs by action"
                         data-filter-group data-items="#logs-tbody tr[data-tags]" data-page-size="15" data-pager="#logs-pager" data-pager-always
                         data-empty="#filter-empty-row" data-visible="#visible-count" data-total="#total-count">
                        <span class="filter-label">Action:</span>
                        <button type="button" class="filter-pill active" data-filter="all">All</button>
                        <button type="button" class="filter-pill" data-filter="requests">Requests</button>
                        <button type="button" class="filter-pill" data-filter="approved">Approved</button>
                        <button type="button" class="filter-pill" data-filter="declined">Declined</button>
                        <button type="button" class="filter-pill" data-filter="cancel">Cancel</button>
                        <button type="button" class="filter-pill" data-filter="handover">Handovers</button>
                        <button type="button" class="filter-pill" data-filter="returns">Returns</button>
                        <button type="button" class="filter-pill" data-filter="fines">Fines</button>
                    </div>
                </div>

                <div class="table-card">
                    <table class="data-table logs-table">
                        <thead>
                            <tr>
                                <th scope="col">Log ID</th>
                                <th scope="col">Timestamp</th>
                                <th scope="col">Req ID</th>
                                <th scope="col">Serial #</th>
                                <th scope="col">Action Type</th>
                                <th scope="col">Borrower</th>
                                <th scope="col">Staff Actor</th>
                                <th scope="col">Fine</th>
                                <th scope="col">Remarks / Notes</th>
                            </tr>
                        </thead>
                        <tbody id="logs-tbody">
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="9" class="text-muted manage-logs-s1">
                                        No transaction logs recorded.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr data-tags="<?= $logTags[$log['action_type']] ?? '' ?>">
                                        <td class="cell-strong">LOG - <?= (int)$log['log_id'] ?></td>
                                        <td><?= date('Y-m-d H:i:s', strtotime($log['action_timestamp'])) ?></td>
                                        <td class="cell-id"><?= $log['reservation_id'] ? 'REQ - ' . (int)$log['reservation_id'] : '—' ?></td>
                                        <td><?= htmlspecialchars($log['serial_snapshot'] ?: '—') ?></td>
                                        <td>
                                            <span class="status-badge <?= $badgeStyles[$log['action_type']] ?? 'pending' ?>">
                                                <?= ucfirst(str_replace('_', ' ', htmlspecialchars($log['action_type']))) ?>
                                            </span>
                                        </td>
                                        <td class="cell-upper"><?= htmlspecialchars($log['borrower_name'] ?: '—') ?></td>
                                        <td><?= htmlspecialchars($log['actor_name']) ?></td>
                                          <td class="cell-strong"><?= $log['fine_amount'] !== null ? '&#8369;' . number_format((float)$log['fine_amount'], 2) : '—' ?></td>
                                        <td><?= htmlspecialchars($log['remarks'] ?: '—') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <tr id="filter-empty-row" style="display:none;">
                                <td colspan="9" class="text-muted manage-logs-s1">
                                    No log entries match your search or filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="pager" id="logs-pager"></div>
            </section>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/navigation/footer.php'; ?>
    <script src="<?= APP_BASE ?>/public/assets/js/filters.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/filters.js') ?: 1 ?>"></script>
</body>
</html>