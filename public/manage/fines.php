<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../src/Fine.php';
requireRole(['admin', 'staff']);
requirePermission('can_inspect_returns');

$pageTitle = 'Fines & Penalties';
$pageStyles = ['shared/collapse', 'pages/manage-fines'];

$fineModel   = new Fine();
$fines       = $fineModel->listAllDetailed();
$chargeable  = $fineModel->listChargeableCheckouts();
$accruing    = $fineModel->listAccruing();
$isAdmin     = ($_SESSION['role'] === 'admin');

// Totals for the summary strip (the arithmetic lives in Fine::totals()).
['unpaidTotal' => $unpaidTotal, 'unpaidCount' => $unpaidCount, 'paidTotal' => $paidTotal,
 'paidCount' => $paidCount, 'waivedCount' => $waivedCount, 'accruingTotal' => $accruingTotal] = Fine::totals($fines, $accruing);

function peso(float $n): string
{
    return '&#8369;' . number_format($n, 2);
}
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
                <p class="page-note">A late fine grows each day an item is overdue and is charged automatically when the item is returned. Administrators can also charge other fines (e.g. damage) on returned loans. <?= $isAdmin ? '' : 'You can see every fine here; if one is unpaid, please remind the borrower. Only an administrator can charge, collect or waive fines.' ?></p>

                <!-- Summary -->
                <div class="fine-summary">
                    <div class="fine-stat">
                        <span class="meta-label">Accruing (overdue, not yet returned)</span>
                        <strong class="fine-stat-value fine-stat-danger"><?= peso($accruingTotal) ?></strong>
                        <span class="text-muted"><?= count($accruing) ?> loan<?= count($accruing) === 1 ? '' : 's' ?></span>
                    </div>
                    <div class="fine-stat">
                        <span class="meta-label">Unpaid</span>
                        <strong class="fine-stat-value fine-stat-danger"><?= peso($unpaidTotal) ?></strong>
                        <span class="text-muted"><?= $unpaidCount ?> fine<?= $unpaidCount === 1 ? '' : 's' ?></span>
                    </div>
                    <div class="fine-stat">
                        <span class="meta-label">Collected</span>
                        <strong class="fine-stat-value fine-stat-success"><?= peso($paidTotal) ?></strong>
                        <span class="text-muted"><?= $paidCount ?> fine<?= $paidCount === 1 ? '' : 's' ?></span>
                    </div>
                    <div class="fine-stat">
                        <span class="meta-label">Waived</span>
                        <strong class="fine-stat-value"><?= $waivedCount ?></strong>
                        <span class="text-muted">fine<?= $waivedCount === 1 ? '' : 's' ?></span>
                    </div>
                </div>

                <!-- Overdue loans that are still out: fine grows daily, charged on return -->
                <section class="collapse-section" data-collapse="fines-accruing">
                <h4 class="section-eyebrow collapse-heading">
                    <button type="button" class="collapse-toggle" aria-expanded="true" aria-controls="collapse-fines-accruing" data-collapse-toggle>
                        <i class="ph ph-warning-circle collapse-icon" aria-hidden="true"></i>
                        <span class="collapse-title">Overdue &mdash; Fines Accruing</span>
                        <span class="collapse-count<?= empty($accruing) ? '' : ' collapse-count--danger' ?>"><?= count($accruing) ?></span>
                        <i class="ph ph-caret-down collapse-chevron" aria-hidden="true"></i>
                    </button>
                </h4>
                <div class="collapse-body" id="collapse-fines-accruing">
                <div class="collapse-inner"><div class="collapse-content">
                <div class="table-card">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Borrower</th>
                                <th scope="col">Equipment</th>
                                <th scope="col">Was Due</th>
                                <th scope="col">Days Late</th>
                                <th scope="col">Fine So Far</th>
                            </tr>
                        </thead>
                        <tbody id="accruing-tbody">
                            <?php if (empty($accruing)): ?>
                                <tr>
                                    <td colspan="5" class="text-muted manage-fines-s2">
                                        No overdue loans right now.
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($accruing as $a):
                                $rate = (float) $a['late_fine_rate'];
                                $days = (int) $a['days_late'];
                            ?>
                                <tr data-row>
                                    <td>
                                        <?= htmlspecialchars($a['borrower_name']) ?>
                                        <span class="manage-fines-s3"><?= htmlspecialchars($a['borrower_id_number'] ?: '') ?> &middot; REQ-<?= (int) $a['reservation_id'] ?></span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($a['model_name']) ?>
                                        <span class="manage-fines-s3">SN: <?= htmlspecialchars($a['serial_number']) ?></span>
                                    </td>
                                    <td><?= date('M d, Y g:i A', strtotime($a['due_at'])) ?></td>
                                    <td class="cell-strong"><?= $days ?> day<?= $days === 1 ? '' : 's' ?></td>
                                    <td class="cell-strong">
                                        <?php if ($rate > 0): ?>
                                            <?= peso($rate * $days) ?>
                                            <span class="manage-fines-s4"><?= $days ?> &times; <?= peso($rate) ?>/day</span>
                                        <?php else: ?>
                                            <span class="text-muted">No late fine for this item</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div hidden data-list data-items="#accruing-tbody tr[data-row]" data-page-size="10" data-pager="#accruing-pager" data-pager-always></div>
                <div class="pager" id="accruing-pager"></div>
                </div></div>
                </div>
                </section>

                <section class="collapse-section" data-collapse="fines-charged">
                <h4 class="section-eyebrow collapse-heading">
                    <button type="button" class="collapse-toggle" aria-expanded="true" aria-controls="collapse-fines-charged" data-collapse-toggle>
                        <i class="ph ph-receipt collapse-icon" aria-hidden="true"></i>
                        <span class="collapse-title">Charged Fines</span>
                        <span class="collapse-count"><?= count($fines) ?></span>
                        <i class="ph ph-caret-down collapse-chevron" aria-hidden="true"></i>
                    </button>
                </h4>
                <div class="collapse-body" id="collapse-fines-charged">
                <div class="collapse-inner"><div class="collapse-content">
                <div class="requests-toolbar">
                    <p class="results-count">
                        Showing <strong id="visible-count"><?= count($fines) ?></strong> of <strong id="total-count"><?= count($fines) ?></strong> fines
                    </p>

                    <div class="manage-fines-s5">
                        <div class="filter-group" role="group" aria-label="Filter fines by status"
                             data-filter-group data-items="#fines-tbody tr[data-tags]" data-page-size="10" data-pager="#fines-pager" data-pager-always
                             data-empty="#filter-empty-row" data-visible="#visible-count" data-total="#total-count">
                            <span class="filter-label">Status:</span>
                            <button type="button" class="filter-pill active" data-filter="all">All</button>
                            <button type="button" class="filter-pill" data-filter="unpaid">Unpaid</button>
                            <button type="button" class="filter-pill" data-filter="paid">Paid</button>
                            <button type="button" class="filter-pill" data-filter="waived">Waived</button>
                        </div>
                        <?php if ($isAdmin): ?>
                        <button type="button" class="btn btn-primary btn-sm btn-rect" data-open-modal="chargeModal"><i class="ph ph-plus" aria-hidden="true"></i><span class="btn-text">Charge Fine</span></button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="table-card">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Fine ID</th>
                                <th scope="col">Borrower</th>
                                <th scope="col">Equipment</th>
                                <th scope="col">Reason</th>
                                <th scope="col">Amount</th>
                                <th scope="col">Status</th>
                                <?php if ($isAdmin): ?>
                                <th scope="col" class="col-action col-action--wide">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="fines-tbody">
                            <?php if (empty($fines)): ?>
                                <tr>
                                    <td colspan="<?= $isAdmin ? 7 : 6 ?>" class="text-muted manage-fines-s6">
                                        No fines have been charged yet.
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($fines as $f):
                                $status = $f['payment_status'];
                                $badge  = ($status === 'paid') ? 'approved' : (($status === 'waived') ? 'waived' : 'pending');
                            ?>
                                <tr data-tags="<?= htmlspecialchars($status) ?>">
                                    <td class="cell-strong">FINE-<?= (int) $f['fine_id'] ?>
                                        <span class="manage-fines-s4">REQ-<?= (int) $f['reservation_id'] ?></span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($f['borrower_name']) ?>
                                        <span class="manage-fines-s3"><?= htmlspecialchars($f['borrower_id_number'] ?: '') ?></span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($f['model_name']) ?>
                                        <span class="manage-fines-s3">SN: <?= htmlspecialchars($f['serial_number']) ?></span>
                                    </td>
                                    <td>
                                        <span class="text-muted manage-fines-s7"><?= htmlspecialchars(str_replace('_', ' ', $f['fine_type'])) ?></span><br><?= htmlspecialchars($f['reason'] ?: '—') ?>
                                        <?php if ($f['charged_at']): ?>
                                            <span class="manage-fines-s3"><?= date('M d, Y g:i A', strtotime($f['charged_at'])) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="cell-strong"><?= peso((float) $f['amount_due']) ?></td>
                                    <td>
                                        <span class="status-badge <?= $badge ?>"><?= ucfirst($status) ?></span>
                                        <?php if ($status === 'paid' && $f['paid_at']): ?>
                                            <span class="manage-fines-s3"><?= date('M d, Y', strtotime($f['paid_at'])) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($isAdmin): ?>
                                    <td class="col-action">
                                        <?php if ($status === 'unpaid'): ?>
                                            <div class="row-actions">
                                                <button type="button" class="btn-approve pay-btn btn-reveal"
                                                    data-id="<?= (int) $f['fine_id'] ?>"
                                                    data-label="FINE-<?= (int) $f['fine_id'] ?> (<?= htmlspecialchars(number_format((float) $f['amount_due'], 2), ENT_QUOTES) ?>) from <?= htmlspecialchars($f['borrower_name'], ENT_QUOTES) ?>"><i class="ph ph-check-circle" aria-hidden="true"></i><span class="btn-label">Mark Paid</span></button>
                                                <?php if ($isAdmin): ?>
                                                    <button type="button" class="btn-decline waive-btn btn-reveal"
                                                        data-id="<?= (int) $f['fine_id'] ?>"
                                                        data-label="FINE-<?= (int) $f['fine_id'] ?> (<?= htmlspecialchars(number_format((float) $f['amount_due'], 2), ENT_QUOTES) ?>) from <?= htmlspecialchars($f['borrower_name'], ENT_QUOTES) ?>"><i class="ph ph-minus-circle" aria-hidden="true"></i><span class="btn-label">Waive</span></button>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                            <tr id="filter-empty-row" style="display:none;">
                                <td colspan="<?= $isAdmin ? 7 : 6 ?>" class="text-muted manage-fines-s6">
                                    No fines match your search or filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="pager" id="fines-pager"></div>
                </div></div>
                </div>
                </section>
            </section>

            <?php if ($isAdmin): ?>
            <!-- Mark Paid -->
<?php include __DIR__ . '/../../includes/modal/manage/fines-pay.php'; ?>

            <!-- Waive (admin only) -->
<?php include __DIR__ . '/../../includes/modal/manage/fines-waive.php'; ?>

            <!-- Charge Fine -->
<?php include __DIR__ . '/../../includes/modal/manage/fines-charge.php'; ?>
            <?php endif; ?>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/navigation/footer.php'; ?>
    <script src="<?= APP_BASE ?>/public/assets/js/filters.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/filters.js') ?: 1 ?>"></script>
    <script src="<?= APP_BASE ?>/public/assets/js/collapse.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/collapse.js') ?: 1 ?>"></script>

    <?php if ($isAdmin): ?>
    <script src="<?= APP_BASE ?>/public/assets/js/pages/manage-fines.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/pages/manage-fines.js') ?: 1 ?>"></script>
    <?php endif; ?>
</body>

</html>
