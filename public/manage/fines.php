<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../src/Fine.php';
requireRole(['admin', 'staff']);
requirePermission('can_inspect_returns');

$pageTitle = 'Fines & Penalties';

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
        <?php include __DIR__ . '/../../includes/sidebar-admin.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../../includes/topbar.php'; ?>

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
                <h4 class="section-eyebrow">Overdue &mdash; Fines Accruing</h4>
                <div class="table-card" style="margin-bottom: 2rem;">
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
                                    <td colspan="5" style="text-align:center; padding: 2rem;" class="text-muted">
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
                                        <span style="display:block; font-size:0.75rem; color:var(--text-muted);"><?= htmlspecialchars($a['borrower_id_number'] ?: '') ?> &middot; REQ-<?= (int) $a['reservation_id'] ?></span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($a['model_name']) ?>
                                        <span style="display:block; font-size:0.75rem; color:var(--text-muted);">SN: <?= htmlspecialchars($a['serial_number']) ?></span>
                                    </td>
                                    <td><?= date('M d, Y g:i A', strtotime($a['due_at'])) ?></td>
                                    <td class="cell-strong"><?= $days ?> day<?= $days === 1 ? '' : 's' ?></td>
                                    <td class="cell-strong">
                                        <?php if ($rate > 0): ?>
                                            <?= peso($rate * $days) ?>
                                            <span style="display:block; font-size:0.75rem; font-weight:400; color:var(--text-muted);"><?= $days ?> &times; <?= peso($rate) ?>/day</span>
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

                <h4 class="section-eyebrow">Charged Fines</h4>
                <div class="requests-toolbar">
                    <p class="results-count">
                        Showing <strong id="visible-count"><?= count($fines) ?></strong> of <strong id="total-count"><?= count($fines) ?></strong> fines
                    </p>

                    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
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
                        <button type="button" class="btn btn-primary btn-sm btn-rect" onclick="openModal('chargeModal')"><i class="ph ph-plus" aria-hidden="true"></i><span class="btn-text">Charge Fine</span></button>
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
                                    <td colspan="<?= $isAdmin ? 7 : 6 ?>" style="text-align:center; padding: 2.5rem;" class="text-muted">
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
                                        <span style="display:block; font-size:0.75rem; font-weight:400; color:var(--text-muted);">REQ-<?= (int) $f['reservation_id'] ?></span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($f['borrower_name']) ?>
                                        <span style="display:block; font-size:0.75rem; color:var(--text-muted);"><?= htmlspecialchars($f['borrower_id_number'] ?: '') ?></span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($f['model_name']) ?>
                                        <span style="display:block; font-size:0.75rem; color:var(--text-muted);">SN: <?= htmlspecialchars($f['serial_number']) ?></span>
                                    </td>
                                    <td>
                                        <span class="text-muted" style="font-size: 0.75rem; text-transform: uppercase;"><?= htmlspecialchars(str_replace('_', ' ', $f['fine_type'])) ?></span><br><?= htmlspecialchars($f['reason'] ?: '—') ?>
                                        <?php if ($f['charged_at']): ?>
                                            <span style="display:block; font-size:0.75rem; color:var(--text-muted);"><?= date('M d, Y g:i A', strtotime($f['charged_at'])) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="cell-strong"><?= peso((float) $f['amount_due']) ?></td>
                                    <td>
                                        <span class="status-badge <?= $badge ?>"><?= ucfirst($status) ?></span>
                                        <?php if ($status === 'paid' && $f['paid_at']): ?>
                                            <span style="display:block; font-size:0.75rem; color:var(--text-muted);"><?= date('M d, Y', strtotime($f['paid_at'])) ?></span>
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
                                <td colspan="<?= $isAdmin ? 7 : 6 ?>" style="text-align:center; padding: 2.5rem;" class="text-muted">
                                    No fines match your search or filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="pager" id="fines-pager"></div>
            </section>

            <?php if ($isAdmin): ?>
            <!-- Mark Paid -->
            <div class="modal-overlay" id="payModal">
                <div class="modal modal--confirm">
                    <div class="modal-header">
                        <h2 class="modal-title">Record Payment</h2>
                        <button type="button" class="modal-close" aria-label="Close" onclick="closeModal('payModal')">
                            <i class="ph ph-x" style="font-size: 1.25rem;"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="confirm-note">
                            <i class="ph ph-receipt" style="color: #16a34a;"></i>
                            <span id="pay-message"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline btn-rect" onclick="closeModal('payModal')"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                        <button type="button" class="btn btn-primary btn-rect" id="pay-confirm-btn"><i class="ph ph-coins" aria-hidden="true"></i><span class="btn-text">Confirm Payment</span></button>
                    </div>
                </div>
            </div>

            <!-- Waive (admin only) -->
            <div class="modal-overlay" id="waiveModal">
                <div class="modal modal--confirm">
                    <div class="modal-header">
                        <h2 class="modal-title">Waive Fine</h2>
                        <button type="button" class="modal-close" aria-label="Close" onclick="closeModal('waiveModal')">
                            <i class="ph ph-x" style="font-size: 1.25rem;"></i>
                        </button>
                    </div>
                    <form id="waive-form">
                        <div class="modal-body">
                            <div class="confirm-note"><i class="ph ph-warning"></i><span id="waive-message"></span></div>
                            <div class="modal-field" style="margin-top: 1.25rem;">
                                <label class="modal-label" for="waive_reason">Reason for waiving *</label>
                                <textarea class="input-field" id="waive_reason" name="reason" rows="3" placeholder="e.g. Item was held by the lab for inspection" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline btn-rect" onclick="closeModal('waiveModal')"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                            <button type="submit" class="btn btn-danger btn-rect" id="waive-submit-btn"><i class="ph ph-minus-circle" aria-hidden="true"></i><span class="btn-text">Waive Fine</span></button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Charge Fine -->
            <div class="modal-overlay" id="chargeModal">
                <div class="modal" style="max-width: 32rem;">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title">Charge a Fine</h2>
                            <p class="modal-subtitle">For damage, missing parts, or other penalties.</p>
                        </div>
                        <button type="button" class="modal-close" aria-label="Close" onclick="closeModal('chargeModal')">
                            <i class="ph ph-x" style="font-size: 1.25rem;"></i>
                        </button>
                    </div>
                    <form id="charge-form">
                        <div class="modal-body">
                            <div class="modal-field">
                                <label class="modal-label" for="charge_checkout">Loan *</label>
                                <select class="input-field" id="charge_checkout" name="checkout_id" required>
                                    <option value="">Select a loan...</option>
                                    <?php foreach ($chargeable as $c): ?>
                                        <option value="<?= (int) $c['checkout_id'] ?>">
                                            REQ-<?= (int) $c['reservation_id'] ?> · <?= htmlspecialchars($c['model_name']) ?> (<?= htmlspecialchars($c['serial_number']) ?>) · <?= htmlspecialchars($c['borrower_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="modal-field">
                                <label class="modal-label" for="charge_type">Type *</label>
                                <select class="input-field" id="charge_type" name="fine_type" required>
                                    <option value="damage">Damage</option>
                                    <option value="missing_accessories">Missing accessories</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="modal-field">
                                <label class="modal-label" for="charge_amount">Amount (&#8369;) *</label>
                                <input type="number" class="input-field" id="charge_amount" name="amount_due" min="0.01" step="0.01" required>
                            </div>
                            <div class="modal-field">
                                <label class="modal-label" for="charge_reason">Reason *</label>
                                <textarea class="input-field" id="charge_reason" name="reason" rows="3" placeholder="e.g. Cracked screen on return" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline btn-rect" onclick="closeModal('chargeModal')"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                            <button type="submit" class="btn btn-primary btn-rect" id="charge-submit-btn"><i class="ph ph-receipt" aria-hidden="true"></i><span class="btn-text">Charge Fine</span></button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>
    <script src="<?= APP_BASE ?>/public/assets/js/filters.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/filters.js') ?: 1 ?>"></script>

    <?php if ($isAdmin): ?>
    <script>
        async function postForm(url, fd) {
            const res = await fetch(url, { method: 'POST', body: fd });
            return res.json();
        }

        async function finish(data, successMessage) {
            if (data.success) {
                showToast(successMessage, 'success');
                setTimeout(() => location.reload(), 700);
            } else {
                showToast(data.message || 'Something went wrong.', 'error');
            }
        }

        // ---- Mark paid ----
        let payId = null;
        document.querySelectorAll('.pay-btn').forEach(btn => btn.addEventListener('click', () => {
            payId = btn.dataset.id;
            document.getElementById('pay-message').textContent = 'Record that ' + btn.dataset.label + ' has been paid?';
            openModal('payModal');
        }));
        document.getElementById('pay-confirm-btn').addEventListener('click', async function () {
            if (!payId) return;
            this.disabled = true;
            try {
                const fd = new FormData();
                fd.append('fine_id', payId);
                await finish(await postForm(APP_BASE + '/api/fine/pay.php', fd), 'Payment recorded.');
            } catch (e) { showToast('Server communication error', 'error'); }
            this.disabled = false;
        });

        // ---- Waive (admin) ----
        let waiveId = null;
        document.querySelectorAll('.waive-btn').forEach(btn => btn.addEventListener('click', () => {
            waiveId = btn.dataset.id;
            document.getElementById('waive-message').textContent = 'Waive ' + btn.dataset.label + '? The borrower will no longer owe it.';
            document.getElementById('waive_reason').value = '';
            openModal('waiveModal');
        }));
        document.getElementById('waive-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('waive-submit-btn');
            btn.disabled = true;
            try {
                const fd = new FormData(this);
                fd.append('fine_id', waiveId);
                await finish(await postForm(APP_BASE + '/api/fine/waive.php', fd), 'Fine waived.');
            } catch (err) { showToast('Server communication error', 'error'); }
            btn.disabled = false;
        });

        // ---- Charge ----
        document.getElementById('charge-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('charge-submit-btn');
            btn.disabled = true;
            try {
                await finish(await postForm(APP_BASE + '/api/fine/charge.php', new FormData(this)), 'Fine charged.');
            } catch (err) { showToast('Server communication error', 'error'); }
            btn.disabled = false;
        });
    </script>
    <?php endif; ?>
</body>

</html>
