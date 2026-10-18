<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../src/Reservation.php';
require_once __DIR__ . '/../../src/Checkout.php';
require_once __DIR__ . '/../../src/Equipment.php';
requireRole(['admin', 'staff']);
requirePermission(['can_confirm_pickup', 'can_inspect_returns']);

// This combined screen is for admins. Staff use their own single-job page
// (Handover or Process Return), depending on the job the admin gave them.
if ($_SESSION['role'] === 'staff') {
    header('Location: ' . APP_URL . '/admin/' . (hasPermission('can_confirm_pickup') ? 'handover.php' : 'return.php'));
    exit;
}

$pageTitle = 'Checkout & Return';

// A staff member only sees the column for their own job (admins see both).
$canHandover = hasPermission('can_confirm_pickup');
$canReturn   = hasPermission('can_inspect_returns');

$reservationModel = new Reservation();
$checkoutModel    = new Checkout();

$reservationModel->expireLapsedPickups(); // drop reservations whose pick-up window has ended
$readyForCheckout = $reservationModel->listReadyForCheckout();
$activeReturns    = $checkoutModel->listActiveDetailed();
$accessoriesByModel = (new Equipment())->listAccessoriesGrouped(); // [catalog_id => [accessories]]
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

        <main class="main-content main-content--fixed">
            <?php include __DIR__ . '/../../includes/topbar.php'; ?>

            <section class="requests-page requests-page--split">
                <div class="pending-grid ops-aligned"<?= ($canHandover && $canReturn) ? '' : ' style="grid-template-columns: 1fr;"' ?>>

                    <?php if ($canHandover): ?>
                    <!-- LEFT: Ready for Checkout Handover -->
                    <div class="ops-column">
                        <div class="section-header">
                            <h3 class="section-title">Ready for Handover</h3>
                            <span class="section-subtitle" data-ready-count><?= count($readyForCheckout) ?> ready</span>
                        </div>

                        <div class="requests-toolbar">
                            <p class="results-count">Approved requests waiting at the lab desk</p>
                        </div>

                        <div hidden data-list data-items="#handover-container > .request-card"></div>
                        <div id="handover-container" class="ops-scroll">
                            <?php if (empty($readyForCheckout)): ?>
                                <div class="card-container text-muted" style="text-align: center; padding: 2rem;">
                                    No items pending handover.
                                </div>
                            <?php else: ?>
                                <?php foreach ($readyForCheckout as $row): ?>
                                    <?php include __DIR__ . '/../../includes/handover-card.php'; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php endif; ?>

                    <?php if ($canReturn): ?>
                    <!-- RIGHT: Active Returns -->
                    <div class="ops-column">
                        <div class="section-header">
                            <h3 class="section-title">Active Returns</h3>
                            <span class="section-subtitle"><?= count($activeReturns) ?> active loans</span>
                        </div>

                        <div class="requests-toolbar">
                            <p class="results-count">
                                Showing <strong id="returns-visible"><?= count($activeReturns) ?></strong> of <strong id="returns-total"><?= count($activeReturns) ?></strong> loans
                            </p>
                            <div class="filter-group" role="group" aria-label="Filter active returns"
                                 data-filter-group data-items="#returns-container > .request-card[data-tags]"
                                 data-empty="#returns-filter-empty" data-visible="#returns-visible" data-total="#returns-total">
                                <span class="filter-label">Filter:</span>
                                <button type="button" class="filter-pill active" data-filter="all">All</button>
                                <button type="button" class="filter-pill" data-filter="overdue">Overdue</button>
                                <button type="button" class="filter-pill" data-filter="today">Due today</button>
                                <button type="button" class="filter-pill" data-filter="ontime">On time</button>
                            </div>
                        </div>

                        <div id="returns-container" class="ops-scroll">
                            <?php if (empty($activeReturns)): ?>
                                <div class="card-container text-muted" style="text-align: center; padding: 2rem;">
                                    No equipment currently checked out.
                                </div>
                            <?php else: ?>
                                <?php foreach ($activeReturns as $row):
                                    // Tags used by the filter pills: overdue / due today / on time.
                                    if ((int)$row['is_overdue'] === 1) {
                                        $returnTag = 'overdue';
                                    } elseif (date('Y-m-d', strtotime($row['due_at'])) === date('Y-m-d')) {
                                        $returnTag = 'today ontime';
                                    } else {
                                        $returnTag = 'ontime';
                                    }
                                ?>
                                    <article class="request-card" id="return-card-<?= (int)$row['checkout_id'] ?>" data-tags="<?= $returnTag ?>">
                                        <div class="ops-card-head">
                                            <span class="cell-id">REQ ID: <?= (int)$row['reservation_id'] ?></span>
                                            <span class="meta-label">Issued: <?= date('M d', strtotime($row['checked_out_at'])) ?></span>
                                        </div>

                                        <div class="ops-field">
                                            <p class="meta-label">Borrower</p>
                                            <h4 class="meta-value"><?= htmlspecialchars($row['borrower_name']) ?></h4>
                                            <span style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($row['borrower_id_number']) ?></span>
                                        </div>
                                        <div class="ops-field">
                                            <p class="meta-label">Item & Unit Serial</p>
                                            <p class="meta-text"><strong><?= htmlspecialchars($row['model_name']) ?></strong> (SN: <?= htmlspecialchars($row['serial_number']) ?>)</p>
                                        </div>
                                        <div class="ops-field">
                                            <p class="meta-label">Due Date</p>
                                            <p class="<?= (int)$row['is_overdue'] === 1 ? 'meta-due' : 'meta-text' ?>">
                                                <?= date('M d, Y h:i A', strtotime($row['due_at'])) ?>
                                                <?php if ((int)$row['is_overdue'] === 1): ?>
                                                    (OVERDUE)
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <?php if ((int)$row['is_overdue'] === 1 && (float)$row['late_fine_rate'] > 0): ?>
                                            <div class="ops-field">
                                                <p class="meta-label">Late Fine So Far</p>
                                                <p class="meta-due">&#8369;<?= number_format((float)$row['late_fine_rate'] * (int)$row['days_late'], 2) ?>
                                                    <span class="text-muted" style="font-weight: 400;">(<?= (int)$row['days_late'] ?> day<?= (int)$row['days_late'] === 1 ? '' : 's' ?> &times; &#8369;<?= number_format((float)$row['late_fine_rate'], 2) ?>) &mdash; charged automatically on return</span>
                                                </p>
                                            </div>
                                        <?php endif; ?>

                                        <button type="button" class="btn btn-outline-success btn-block"
                                                onclick="openReturnModal(<?= (int)$row['checkout_id'] ?>, <?= js_arg($row['borrower_name']) ?>, <?= js_arg($row['model_name'] . ' (SN: ' . $row['serial_number'] . ')') ?>, <?= (int)$row['is_overdue'] ?>, <?= (int)$row['catalog_id'] ?>)"><i class="ph ph-arrow-u-down-left" aria-hidden="true"></i><span class="btn-text">Process Return</span></button>
                                    </article>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <div id="returns-filter-empty" class="card-container text-muted" style="display: none; text-align: center; padding: 2rem;">
                                No loans match this filter.
                            </div>
                        </div>
                    </div>

                    <?php endif; ?>

                </div>
            </section>

            <!-- CONFIRM HANDOVER MODAL -->
            <div class="modal-overlay" id="handoverModal">
                <div class="modal">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title">Confirm Handover</h2>
                            <p class="modal-subtitle">Verify student ID and item completeness.</p>
                        </div>
                        <button class="modal-close" onclick="closeModal('handoverModal')">&times;</button>
                    </div>

                    <form id="handover-form">
                        <input type="hidden" name="reservation_id" id="handover_reservation_id">
                        <div class="modal-body">
                            <dl class="modal-summary">
                                <dt>Borrower Name</dt>
                                <dd id="ho-borrower"></dd>
                                <dt>ID Number</dt>
                                <dd id="ho-idnumber"></dd>
                                <dt>Item</dt>
                                <dd id="ho-item"></dd>
                                <dt>Due Date</dt>
                                <dd id="ho-due"></dd>
                            </dl>

                            <div class="modal-field">
                                <label class="checkbox-row" style="font-weight: 700; color: var(--primary-brand);">
                                    <input type="checkbox" name="id_verified" value="1" required> 
                                    Physical ID card verified in person
                                </label>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline btn-rect" onclick="closeModal('handoverModal')"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                            <button type="submit" class="btn btn-primary btn-rect" id="ho-submit-btn"><i class="ph ph-package" aria-hidden="true"></i><span class="btn-text">Confirm Handover</span></button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- PROCESS RETURN MODAL -->
            <div class="modal-overlay" id="returnModal">
                <div class="modal">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title">Process Item Return</h2>
                            <p class="modal-subtitle">Inspect returned equipment condition.</p>
                        </div>
                        <button class="modal-close" onclick="closeModal('returnModal')">&times;</button>
                    </div>

                    <form id="return-form">
                        <input type="hidden" name="checkout_id" id="return_checkout_id">
                        <div class="modal-body">
                            <dl class="modal-summary">
                                <dt>Borrower</dt>
                                <dd id="ret-borrower"></dd>
                                <dt>Item</dt>
                                <dd id="ret-item"></dd>
                                <dt>Status</dt>
                                <dd id="ret-status-badge"></dd>
                            </dl>

                            <div class="modal-field">
                                <label class="modal-label">Item Condition *</label>
                                <label class="option-row">
                                    <input type="radio" name="return_condition" value="good" checked> Good / Normal Condition
                                </label>
                                <label class="option-row">
                                    <input type="radio" name="return_condition" value="damaged"> Damaged
                                </label>
                                <label class="option-row">
                                    <input type="radio" name="return_condition" value="missing_accessories"> Missing Accessories
                                </label>
                            </div>

                            <!-- Shown only when "Missing Accessories" is selected -->
                            <div class="modal-field" id="missing-accessories-field" style="display: none;">
                                <label class="modal-label">Which accessories are missing? *</label>
                                <div id="missing-accessories-list"></div>
                            </div>

                            <!-- Shown only when "Damaged" is selected -->
                            <div class="modal-field" id="damage-notes-field" style="display: none;">
                                <label class="modal-label">What happened? * (describe the damage)</label>
                                <textarea class="input-field" name="damage_notes" id="damage_notes" rows="3" placeholder="e.g. Screen cracked after being dropped, water damage on the case..."></textarea>
                            </div>

                            <div class="modal-field">
                                <label class="modal-label">Inspection Remarks (optional)</label>
                                <textarea class="input-field" name="inspection_remarks" rows="2" placeholder="Any other notes about the item's condition..."></textarea>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline btn-rect" onclick="closeModal('returnModal')"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                            <button type="submit" class="btn btn-approve btn-rect" id="ret-submit-btn"><i class="ph ph-check-circle" aria-hidden="true"></i><span class="btn-text">Complete Return</span></button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>
    <script src="<?= APP_BASE ?>/public/assets/js/filters.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/filters.js') ?: 1 ?>"></script>
    <script src="<?= APP_BASE ?>/public/assets/js/countdown.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/countdown.js') ?: 1 ?>"></script>

    <script>
        function openHandoverModal(id, borrower, idNumber, item, due) {
            document.getElementById('handover_reservation_id').value = id;
            document.getElementById('ho-borrower').textContent = borrower;
            document.getElementById('ho-idnumber').textContent = idNumber;
            document.getElementById('ho-item').textContent = item;
            document.getElementById('ho-due').textContent = due;
            openModal('handoverModal');
        }

        // Accessories that ship with each model: { catalog_id: [{accessory_id, accessory_name}, ...] }
        const ACCESSORIES_BY_MODEL = <?= json_encode($accessoriesByModel, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        function renderMissingAccessories(catalogId) {
            const list = document.getElementById('missing-accessories-list');
            const missingRadio = document.querySelector('input[name="return_condition"][value="missing_accessories"]');
            const accessories = ACCESSORIES_BY_MODEL[catalogId] || [];
            list.replaceChildren();

            if (accessories.length === 0) {
                // Nothing registered for this model, so there is nothing to tick.
                missingRadio.disabled = true;
                missingRadio.parentElement.title = 'No accessories are registered for this model (add them in Equipment Catalog).';
                const p = document.createElement('p');
                p.className = 'text-muted';
                p.textContent = 'No accessories are registered for this model.';
                list.appendChild(p);
                return;
            }

            missingRadio.disabled = false;
            missingRadio.parentElement.removeAttribute('title');
            accessories.forEach(a => {
                const label = document.createElement('label');
                label.className = 'option-row';
                const box = document.createElement('input');
                box.type = 'checkbox';
                box.name = 'missing_accessories[]';
                box.value = a.accessory_id;
                label.appendChild(box);
                label.appendChild(document.createTextNode(' ' + a.accessory_name)); // text node: no HTML injection
                list.appendChild(label);
            });
        }

        // Each condition shows only its own extra input.
        function syncReturnFields() {
            const cond = document.querySelector('input[name="return_condition"]:checked').value;
            document.getElementById('damage-notes-field').style.display = (cond === 'damaged') ? 'block' : 'none';
            document.getElementById('missing-accessories-field').style.display = (cond === 'missing_accessories') ? 'block' : 'none';
        }

        function openReturnModal(id, borrower, item, isOverdue, catalogId) {
            document.getElementById('return-form').reset(); // clear the previous return's choices
            renderMissingAccessories(catalogId);
            syncReturnFields();
            document.getElementById('return_checkout_id').value = id;
            document.getElementById('ret-borrower').textContent = borrower;
            document.getElementById('ret-item').textContent = item;
            document.getElementById('ret-status-badge').innerHTML = isOverdue 
                ? '<span class="status-badge declined">Overdue Return</span>' 
                : '<span class="status-badge approved">On-Time Return</span>';
            openModal('returnModal');
        }

        document.querySelectorAll('input[name="return_condition"]').forEach(radio => {
            radio.addEventListener('change', syncReturnFields);
        });

        // AJAX Handover Submission
        document.getElementById('handover-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('ho-submit-btn');
            btn.disabled = true;
            setBtnText(btn, 'Processing...');

            const formData = new FormData(this);
            try {
                const res = await fetch(APP_BASE + '/api/checkout/handover.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Equipment handed over to student!', 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast(data.message || 'Handover failed', 'error');
                }
            } catch (err) {
                showToast('Server error during handover', 'error');
            } finally {
                btn.disabled = false;
                setBtnText(btn, 'Confirm Handover');
            }
        });

        // AJAX Return Submission
        document.getElementById('return-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            const cond = this.querySelector('input[name="return_condition"]:checked').value;
            if (cond === 'damaged' && document.getElementById('damage_notes').value.trim() === '') {
                showToast('Please describe what happened to the item.', 'error');
                return;
            }
            if (cond === 'missing_accessories' && !this.querySelector('input[name="missing_accessories[]"]:checked')) {
                showToast('Select at least one missing accessory.', 'error');
                return;
            }

            const btn = document.getElementById('ret-submit-btn');
            btn.disabled = true;
            setBtnText(btn, 'Processing...');

            const formData = new FormData(this);
            try {
                const res = await fetch(APP_BASE + '/api/checkout/return.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.late_fine ? data.message : 'Equipment return successfully recorded!', data.late_fine ? 'info' : 'success');
                    setTimeout(() => location.reload(), data.late_fine ? 1800 : 800);
                } else {
                    showToast(data.message || 'Return processing failed', 'error');
                }
            } catch (err) {
                showToast('Server error during return', 'error');
            } finally {
                btn.disabled = false;
                setBtnText(btn, 'Complete Return');
            }
        });
    </script>
</body>
</html>