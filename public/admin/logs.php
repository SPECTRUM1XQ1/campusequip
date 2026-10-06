<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['admin', 'staff']);

$pageTitle = 'Transaction Log';
$comingSoonMessage = 'The searchable audit trail view is next on the build list.';

// Temporary mock data for UI design — backend dev: replace with a DB query.
// 'class' maps to a badge style: returned | approved | declined-solid
$logs = [
    ['id' => '101', 'timestamp' => '2026-09-10 15:00:12', 'req_id' => '1012', 'serial' => 'SN-148', 'action' => 'Returned', 'class' => 'returned', 'borrower' => 'Justin Peralta', 'processed_by' => 'Santos', 'fine' => '5.00', 'note' => '3 Days Overdue'],
    ['id' => '102', 'timestamp' => '2026-09-10 15:12:44', 'req_id' => '1013', 'serial' => 'SN-882', 'action' => 'Approved', 'class' => 'approved', 'borrower' => 'Justin Peralta', 'processed_by' => 'Cruz', 'fine' => '—', 'note' => 'Authorized for loan'],
    ['id' => '104', 'timestamp' => '2026-09-11 09:20:00', 'req_id' => '1014', 'serial' => 'SN-102', 'action' => 'Decline', 'class' => 'declined-solid', 'borrower' => 'Basti Peralta', 'processed_by' => 'Santos', 'fine' => '—', 'note' => 'Item Used in Events'],
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
        <?php include __DIR__ . '/../../includes/sidebar-admin.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../../includes/topbar.php'; ?>

            <section class="requests-page">
                <p class="page-note">Historical ledger audit trail of all transactions</p>

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
                                <th scope="col">Processed By</th>
                                <th scope="col">Fine ID</th>
                                <th scope="col">Note / Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="cell-strong"><?= htmlspecialchars($log['id']) ?></td>
                                    <td><?= htmlspecialchars($log['timestamp']) ?></td>
                                    <td class="cell-id"><?= htmlspecialchars($log['req_id']) ?></td>
                                    <td><?= htmlspecialchars($log['serial']) ?></td>
                                    <td>
                                        <span class="status-badge <?= htmlspecialchars($log['class']) ?>">
                                            <?= htmlspecialchars($log['action']) ?>
                                        </span>
                                    </td>
                                    <td class="cell-upper"><?= htmlspecialchars($log['borrower']) ?></td>
                                    <td><?= htmlspecialchars($log['processed_by']) ?></td>
                                    <td><?= htmlspecialchars($log['fine']) ?></td>
                                    <td><?= htmlspecialchars($log['note']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>

</html>