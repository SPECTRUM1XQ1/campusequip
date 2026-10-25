<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../src/Reservation.php';
requireRole(['admin', 'staff']);
requirePermission('can_approve_requests');

$pageTitle = 'Request & Pending';
$pageStyles = ['pages/manage-requests'];
$reservationModel = new Reservation();
$requests = $reservationModel->listPendingDetailed();
$totalPending = count($requests);
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
                <div class="requests-toolbar">
                    <p class="results-count">
                        Showing <strong id="visible-count"><?= $totalPending ?></strong> of <strong id="total-count"><?= $totalPending ?></strong> pending requests
                    </p>

                    <div class="filter-group" role="group" aria-label="Filter requests by start date"
                         data-filter-group data-items="#requests-tbody tr[data-tags]" data-page-size="10" data-pager="#requests-pager" data-pager-always data-empty="#filter-empty-row"
                         data-visible="#visible-count" data-total="#total-count">
                        <span class="filter-label">Filter:</span>
                        <button type="button" class="filter-pill active" data-filter="all">All</button>
                        <button type="button" class="filter-pill" data-filter="urgent">Starts today / overdue</button>
                        <button type="button" class="filter-pill" data-filter="week">Next 7 days</button>
                        <button type="button" class="filter-pill" data-filter="later">Later</button>
                    </div>
                </div>

                <div class="table-card">
                    <table class="data-table" id="requests-table">
                        <thead>
                            <tr>
                                <th scope="col">REQ ID</th>
                                <th scope="col">Borrower</th>
                                <th scope="col">Item Requested</th>
                                <th scope="col">Schedule Dates</th>
                                <th scope="col">Purpose</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="col-action col-action--wide">Action</th>
                            </tr>
                        </thead>
                        <tbody id="requests-tbody">
                            <?php if (empty($requests)): ?>
                                <tr>
                                    <td colspan="7" class="text-muted manage-requests-s1">
                                        No pending requests awaiting approval.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($requests as $req): 
                                    $unitJson = htmlspecialchars(json_encode($req['available_units']), ENT_QUOTES, 'UTF-8');

                                    // Tag each request by how soon it starts, for the filter pills.
                                    $startDate = date('Y-m-d', strtotime($req['start_at']));
                                    $today     = date('Y-m-d');
                                    if ($startDate <= $today) {
                                        $tags = 'urgent';
                                    } elseif ($startDate <= date('Y-m-d', strtotime('+7 days'))) {
                                        $tags = 'week';
                                    } else {
                                        $tags = 'later';
                                    }
                                ?>
                                    <tr id="req-row-<?= (int)$req['reservation_id'] ?>" data-status="pending" data-tags="<?= $tags ?>">
                                        <td class="cell-id">REQ-<?= (int)$req['reservation_id'] ?></td>
                                        <td class="cell-strong">
                                            <?= htmlspecialchars($req['borrower_name']) ?>
                                            <span class="manage-requests-s2">
                                                <?= htmlspecialchars($req['borrower_id_number']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($req['model_name']) ?></td>
                                        <td class="cell-dates">
                                            <?= date('M d, Y', strtotime($req['start_at'])) ?> &rarr; <?= date('M d, Y', strtotime($req['end_at'])) ?>
                                        </td>
                                        <td><?= htmlspecialchars($req['purpose'] ?: 'Academic coursework') ?></td>
                                        <td><span class="status-badge pending">Pending</span></td>
                                        <td class="col-action">
                                            <div class="row-actions">
                                                <button type="button" class="btn-decline btn-reveal" 
                                                        data-decline="<?= (int)$req['reservation_id'] ?>" data-borrower="<?= e($req['borrower_name']) ?>" data-model="<?= e($req['model_name']) ?>"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-label">Decline</span></button>
                                                <button type="button" class="btn-approve btn-reveal" 
                                                        data-units="<?= $unitJson ?>"
                                                        data-approve="<?= (int)$req['reservation_id'] ?>" data-borrower="<?= e($req['borrower_name']) ?>" data-model="<?= e($req['model_name']) ?>"><i class="ph ph-check" aria-hidden="true"></i><span class="btn-label">Approve</span></button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <tr id="filter-empty-row" style="display:none;">
                                <td colspan="7" class="text-muted manage-requests-s1">
                                    No requests match this filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="pager" id="requests-pager"></div>
            </section>

            <!-- APPROVE MODAL -->
<?php include __DIR__ . '/../../includes/modal/manage/requests-approve.php'; ?>

            <!-- DECLINE MODAL -->
<?php include __DIR__ . '/../../includes/modal/manage/requests-decline.php'; ?>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/navigation/footer.php'; ?>
    <script src="<?= APP_BASE ?>/public/assets/js/filters.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/filters.js') ?: 1 ?>"></script>

    <script src="<?= APP_BASE ?>/public/assets/js/pages/manage-requests.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/pages/manage-requests.js') ?: 1 ?>"></script>
</body>
</html>