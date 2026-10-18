<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../src/Reservation.php';
requireRole(['admin', 'staff']);
requirePermission('can_approve_requests');

$pageTitle = 'Request & Pending';
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
        <?php include __DIR__ . '/../../includes/sidebar-admin.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../../includes/topbar.php'; ?>

            <section class="requests-page">
                <div class="requests-toolbar">
                    <p class="results-count">
                        Showing <strong id="visible-count"><?= $totalPending ?></strong> of <strong id="total-count"><?= $totalPending ?></strong> pending requests
                    </p>

                    <div class="filter-group" role="group" aria-label="Filter requests by start date"
                         data-filter-group data-items="#requests-tbody tr[data-tags]" data-page-size="10" data-pager="#requests-pager" data-empty="#filter-empty-row"
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
                                <th scope="col" class="col-action">Action</th>
                            </tr>
                        </thead>
                        <tbody id="requests-tbody">
                            <?php if (empty($requests)): ?>
                                <tr>
                                    <td colspan="7" style="text-align:center; padding: 2.5rem;" class="text-muted">
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
                                            <span style="display:block; font-size:0.75rem; color:var(--text-muted); font-weight:400;">
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
                                                        onclick="openDeclineModal(<?= (int)$req['reservation_id'] ?>, <?= js_arg($req['borrower_name']) ?>, <?= js_arg($req['model_name']) ?>)"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-label">Decline</span></button>
                                                <button type="button" class="btn-approve btn-reveal" 
                                                        data-units="<?= $unitJson ?>"
                                                        onclick="openApproveModal(<?= (int)$req['reservation_id'] ?>, <?= js_arg($req['borrower_name']) ?>, <?= js_arg($req['model_name']) ?>, this)"><i class="ph ph-check" aria-hidden="true"></i><span class="btn-label">Approve</span></button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <tr id="filter-empty-row" style="display:none;">
                                <td colspan="7" style="text-align:center; padding: 2.5rem;" class="text-muted">
                                    No requests match this filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="pager" id="requests-pager"></div>
            </section>

            <!-- APPROVE MODAL -->
            <div class="modal-overlay" id="approveModal" aria-hidden="true">
                <div class="modal">
                    <div class="modal-header">
                        <div>
                            <h3 class="modal-title">Approve Reservation</h3>
                            <p class="modal-subtitle">Assign an available serialized unit to fulfill this request.</p>
                        </div>
                        <button type="button" class="modal-close" onclick="closeModal('approveModal')">&times;</button>
                    </div>

                    <form id="approve-form">
                        <input type="hidden" name="reservation_id" id="approve_reservation_id">
                        <div class="modal-body">
                            <dl class="modal-summary">
                                <dt>Request ID</dt>
                                <dd id="approve-summary-id"></dd>
                                <dt>Borrower</dt>
                                <dd id="approve-summary-borrower"></dd>
                                <dt>Equipment</dt>
                                <dd id="approve-summary-model"></dd>
                            </dl>

                            <div class="modal-field">
                                <label class="modal-label" for="assign_item_id">Assign Physical Unit (Serial Number) *</label>
                                <select class="input-field" name="item_id" id="assign_item_id" required>
                                    <!-- Dynamically filled with available serialized items -->
                                </select>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline btn-rect" onclick="closeModal('approveModal')"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                            <button type="submit" class="btn btn-primary btn-rect" id="approve-submit-btn"><i class="ph ph-check" aria-hidden="true"></i><span class="btn-text">Confirm Approval</span></button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- DECLINE MODAL -->
            <div class="modal-overlay" id="declineModal" aria-hidden="true">
                <div class="modal">
                    <div class="modal-header">
                        <div>
                            <h3 class="modal-title">Decline Reservation</h3>
                            <p class="modal-subtitle">State the reason; this will be logged and communicated.</p>
                        </div>
                        <button type="button" class="modal-close" onclick="closeModal('declineModal')">&times;</button>
                    </div>

                    <form id="decline-form">
                        <input type="hidden" name="reservation_id" id="decline_reservation_id">
                        <div class="modal-body">
                            <dl class="modal-summary">
                                <dt>Request ID</dt>
                                <dd id="decline-summary-id"></dd>
                                <dt>Borrower</dt>
                                <dd id="decline-summary-borrower"></dd>
                                <dt>Equipment</dt>
                                <dd id="decline-summary-model"></dd>
                            </dl>

                            <!-- Radio buttons: only one reason can be chosen -->
                            <div class="modal-field" role="radiogroup" aria-labelledby="decline-reason-label">
                                <label class="modal-label" id="decline-reason-label">Reason for Decline *</label>
                                <label class="option-row">
                                    <input type="radio" name="decline_choice" value="All units scheduled for laboratory classes."> Laboratory conflict
                                </label>
                                <label class="option-row">
                                    <input type="radio" name="decline_choice" value="Unit undergoing routine hardware maintenance."> Under maintenance
                                </label>
                                <label class="option-row">
                                    <input type="radio" name="decline_choice" value="Incomplete borrower clearance or unpaid fines."> Borrower fine block
                                </label>
                                <label class="option-row">
                                    <input type="radio" name="decline_choice" value="__other__"> Other (specify below)
                                </label>
                            </div>

                            <div class="modal-field" id="decline-other-field" style="display: none;">
                                <textarea class="input-field" id="decline_other" rows="3" placeholder="Enter reason details..."></textarea>
                            </div>

                            <!-- The value actually sent to api/reservation/decline.php -->
                            <input type="hidden" name="decline_reason" id="decline_reason">
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline btn-rect" onclick="closeModal('declineModal')"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                            <button type="submit" class="btn btn-danger btn-rect" id="decline-submit-btn"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Confirm Decline</span></button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>
    <script src="<?= APP_BASE ?>/public/assets/js/filters.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/filters.js') ?: 1 ?>"></script>

    <script>
        function openApproveModal(id, borrower, model, btn) {
            document.getElementById('approve_reservation_id').value = id;
            document.getElementById('approve-summary-id').textContent = 'REQ-' + id;
            document.getElementById('approve-summary-borrower').textContent = borrower;
            document.getElementById('approve-summary-model').textContent = model;

            const unitSelect = document.getElementById('assign_item_id');
            unitSelect.innerHTML = '';
            
            const units = JSON.parse(btn.getAttribute('data-units') || '[]');
            if (units.length === 0) {
                unitSelect.innerHTML = '<option value="" disabled selected>No available units in stock!</option>';
                document.getElementById('approve-submit-btn').disabled = true;
            } else {
                document.getElementById('approve-submit-btn').disabled = false;
                units.forEach(u => {
                    const opt = document.createElement('option');
                    opt.value = u.item_id;
                    opt.textContent = `SN: ${u.serial_number} (${u.storage_location || 'Main Desk'})`;
                    unitSelect.appendChild(opt);
                });
            }
            openModal('approveModal');
        }

        function openDeclineModal(id, borrower, model) {
            document.getElementById('decline_reservation_id').value = id;
            document.getElementById('decline-summary-id').textContent = 'REQ-' + id;
            document.getElementById('decline-summary-borrower').textContent = borrower;
            document.getElementById('decline-summary-model').textContent = model;
            document.getElementById('decline-form').reset(); // clears radios + custom text
            document.getElementById('decline_reservation_id').value = id; // reset() also clears hidden inputs
            syncDeclineReason();
            openModal('declineModal');
        }

        // The textarea only appears for "Other".
        function syncDeclineReason() {
            const choice = document.querySelector('input[name="decline_choice"]:checked');
            const isOther = choice && choice.value === '__other__';
            const field = document.getElementById('decline-other-field');
            field.style.display = isOther ? 'block' : 'none';
            if (isOther) document.getElementById('decline_other').focus();
        }
        document.querySelectorAll('input[name="decline_choice"]').forEach(r => r.addEventListener('change', syncDeclineReason));

        // AJAX Approve Request
        document.getElementById('approve-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('approve-submit-btn');
            btn.disabled = true;
            setBtnText(btn, 'Processing...');

            const formData = new FormData(this);
            try {
                const res = await fetch(APP_BASE + '/api/reservation/approve.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Reservation approved successfully!', 'success');
                    const rowId = 'req-row-' + formData.get('reservation_id');
                    const row = document.getElementById(rowId);
                    if (row) row.remove();
                    window.refreshFilters();
                    closeModal('approveModal');
                } else {
                    showToast(data.message || 'Approval failed', 'error');
                }
            } catch (err) {
                showToast('Server communication error', 'error');
            } finally {
                btn.disabled = false;
                setBtnText(btn, 'Confirm Approval');
            }
        });

        // AJAX Decline Request
        document.getElementById('decline-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            // Work out the single reason string to send.
            const choice = this.querySelector('input[name="decline_choice"]:checked');
            if (!choice) {
                showToast('Please select a reason for declining.', 'error');
                return;
            }
            const reason = (choice.value === '__other__')
                ? document.getElementById('decline_other').value.trim()
                : choice.value;
            if (reason === '') {
                showToast('Please type the reason for declining.', 'error');
                return;
            }
            document.getElementById('decline_reason').value = reason;

            const btn = document.getElementById('decline-submit-btn');
            btn.disabled = true;
            setBtnText(btn, 'Processing...');

            const formData = new FormData(this);
            try {
                const res = await fetch(APP_BASE + '/api/reservation/decline.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Reservation declined and borrower notified.', 'info');
                    const rowId = 'req-row-' + formData.get('reservation_id');
                    const row = document.getElementById(rowId);
                    if (row) row.remove();
                    window.refreshFilters();
                    closeModal('declineModal');
                } else {
                    showToast(data.message || 'Decline failed', 'error');
                }
            } catch (err) {
                showToast('Server communication error', 'error');
            } finally {
                btn.disabled = false;
                setBtnText(btn, 'Confirm Decline');
            }
        });
    </script>
</body>
</html>