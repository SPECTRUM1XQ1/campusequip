<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['admin', 'staff']);

$pageTitle = 'Checkout & Return';
$comingSoonMessage = 'Handover and return inspection screens are next on the build list.';

// Temporary mock data for UI design — backend dev: replace with DB queries.
$readyForCheckout = [
    ['req_id' => 'REQ-120', 'assigned_by' => 'Admin Cruz', 'borrower' => 'Justin Peralta', 'item' => 'Sony Camera (SN 101)', 'expected_return' => 'Sep 12 2026 4:30PM'],
    ['req_id' => 'REQ-121', 'assigned_by' => 'Staff Santos', 'borrower' => 'Aris Santos', 'item' => 'Dell XPS Laptop (SN 103)', 'expected_return' => 'Sep 14 2026 12:00PM'],
];

$activeReturns = [
    ['req_id' => 'REQ-120', 'issued_by' => 'Staff Santos', 'borrower' => 'Justin Peralta', 'item' => 'Sony Camera (SN 101)', 'due_date' => 'Sep 12 2026 4:30PM', 'overdue' => true],
    ['req_id' => 'REQ-122', 'issued_by' => 'Admin Cruz', 'borrower' => 'Chloe Mendez', 'item' => 'AV Projector (SN 501)', 'due_date' => 'Sep 11 2026 5:00PM', 'overdue' => true],
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
                <div class="pending-grid">

                    <!-- LEFT: Ready for Checkout -->
                    <div>
                        <div class="section-header">
                            <h3 class="section-title">Ready for Checkout</h3>
                        </div>

                        <?php foreach ($readyForCheckout as $row): ?>
                            <article class="request-card">
                                <div class="ops-card-head">
                                    <span class="cell-id">REQ ID:
                                        <?= htmlspecialchars(str_replace('REQ-', '', $row['req_id'])) ?></span>
                                    <span class="meta-label">Assign By: <?= htmlspecialchars($row['assigned_by']) ?></span>
                                </div>

                                <div class="ops-field">
                                    <p class="meta-label">Borrower</p>
                                    <h4 class="meta-value"><?= htmlspecialchars($row['borrower']) ?></h4>
                                </div>
                                <div class="ops-field">
                                    <p class="meta-label">Item</p>
                                    <p class="meta-text"><?= htmlspecialchars($row['item']) ?></p>
                                </div>
                                <div class="ops-field">
                                    <p class="meta-label">Expected Return</p>
                                    <p class="meta-text"><?= htmlspecialchars($row['expected_return']) ?></p>
                                </div>

                                <button type="button" class="btn btn-primary btn-block"
                                    data-id="<?= htmlspecialchars($row['req_id']) ?>">Hand Over</button>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- RIGHT: Active Returns -->
                    <div>
                        <div class="section-header">
                            <h3 class="section-title">Active Returns</h3>
                        </div>

                        <?php foreach ($activeReturns as $row): ?>
                            <article class="request-card">
                                <div class="ops-card-head">
                                    <span class="cell-id">REQ ID:
                                        <?= htmlspecialchars(str_replace('REQ-', '', $row['req_id'])) ?></span>
                                    <span class="meta-label">Issued By: <?= htmlspecialchars($row['issued_by']) ?></span>
                                </div>

                                <div class="ops-field">
                                    <p class="meta-label">Borrower</p>
                                    <h4 class="meta-value"><?= htmlspecialchars($row['borrower']) ?></h4>
                                </div>
                                <div class="ops-field">
                                    <p class="meta-label">Item</p>
                                    <p class="meta-text"><?= htmlspecialchars($row['item']) ?></p>
                                </div>
                                <div class="ops-field">
                                    <p class="meta-label">Due Date</p>
                                    <p class="<?= $row['overdue'] ? 'meta-due' : 'meta-text' ?>">
                                        <?= htmlspecialchars($row['due_date']) ?>
                                    </p>
                                </div>

                                <button type="button" class="btn btn-outline-success btn-block"
                                    data-id="<?= htmlspecialchars($row['req_id']) ?>">Returned</button>
                            </article>
                        <?php endforeach; ?>
                    </div>

                </div>
            </section>
            <!-- Confirm Handover Modal -->
            <div class="modal-overlay" id="handoverModal">
                <div class="modal">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title">Confirm Equipment Handover</h2>
                            <p class="modal-subtitle">Verify student ID and item state.</p>
                        </div>
                        <button class="modal-close" onclick="closeModal('handoverModal')">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="modal-body">
                        <dl class="modal-summary">
                            <dt>Borrower Name</dt>
                            <dd>Justin Peralta</dd>
                            <dt>Borrower ID</dt>
                            <dd>CE-2026-18472</dd>
                            <dt></dt>
                            <dd class="id-verification">
                                <input type="checkbox" checked> Physical Student ID verified at desk
                            </dd>
                            <dt>Request ID</dt>
                            <dd>REQ-210</dd>
                            <dt>Handover Time</dt>
                            <dd>Dec 11, 2026 - 08:30 AM</dd>
                            <dt>Item</dt>
                            <dd>Sony Alpha Camera - SN-10201</dd>
                            <dt>Due Date</dt>
                            <dd>Dec 18, 2026</dd>
                        </dl>
                        <div class="modal-field">
                            <label class="modal-label">Accessories issued</label>
                            <p class="meta-sub" style="margin-bottom:0.75rem;">Confirm each item is present before
                                handover.</p>
                            <label class="checkbox-row"><input type="checkbox" checked> Camera Body</label>
                            <label class="checkbox-row"><input type="checkbox" checked> Lens Cap</label>
                            <label class="checkbox-row"><input type="checkbox" checked> Battery</label>
                            <label class="checkbox-row"><input type="checkbox" checked> Carrying Bag</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline btn-rect" onclick="closeModal('handoverModal')">Cancel</button>
                        <button class="btn btn-primary btn-rect" onclick="closeModal('handoverModal')">Confirm
                            Handover</button>
                    </div>
                </div>
            </div>

            <!-- Process Return Modal -->
            <div class="modal-overlay" id="returnModal">
                <div class="modal">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title">Process Item Return</h2>
                            <p class="modal-subtitle">Inspect returned equipment before closing.</p>
                        </div>
                        <button class="modal-close" onclick="closeModal('returnModal')">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="modal-body">
                        <dl class="modal-summary">
                            <dt>Borrower</dt>
                            <dd>Justin Peralta</dd>
                            <dt>Item & Serial</dt>
                            <dd>Sony Alpha Camera - SN-10201</dd>
                            <dt>Dates</dt>
                            <dd>Handover · Dec 11, 2026<br>Due / returned · Dec 18, 2026</dd>
                            <dt>Status</dt>
                            <dd><span class="status-badge approved">On-Time Return</span></dd>
                        </dl>
                        <div class="modal-field">
                            <label class="modal-label">Item condition</label>
                            <label class="option-row">
                                <input type="radio" name="condition" value="good" checked>
                                Good / Normal Wear
                            </label>
                            <label class="option-row">
                                <input type="radio" name="condition" value="damaged">
                                Damaged
                            </label>
                            <label class="option-row">
                                <input type="radio" name="condition" value="missing">
                                Missing Accessories
                            </label>
                        </div>
                        <div class="modal-field">
                            <label class="modal-label">Inspection remarks (optional)</label>
                            <textarea class="input-field"
                                placeholder="Record cosmetic wear, damage, or missing items..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline btn-rect" onclick="closeModal('returnModal')">Cancel</button>
                        <button class="btn btn-approve btn-rect" onclick="closeModal('returnModal')">Complete
                            Return</button>
                    </div>
                </div>
            </div>

            <!-- Modal Toggle Logic -->
            <script>
                function openModal(modalId) {
                    document.getElementById(modalId).classList.add('open');
                }

                function closeModal(modalId) {
                    document.getElementById(modalId).classList.remove('open');
                }

                document.addEventListener('DOMContentLoaded', () => {
                    // Open Handover modal when clicking 'Hand Over' button in table
                    const handoverBtns = document.querySelectorAll('.btn-primary.btn-block');
                    handoverBtns.forEach(btn => {
                        btn.addEventListener('click', () => openModal('handoverModal'));
                    });

                    // Open Return modal when clicking 'Returned' button in table
                    const returnBtns = document.querySelectorAll('.btn-outline-success.btn-block');
                    returnBtns.forEach(btn => {
                        btn.addEventListener('click', () => openModal('returnModal'));
                    });

                    // Allow clicking outside the modal card to close it
                    const overlays = document.querySelectorAll('.modal-overlay');
                    overlays.forEach(overlay => {
                        overlay.addEventListener('click', (e) => {
                            if (e.target === overlay) closeModal(overlay.id);
                        });
                    });
                });
            </script>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>

</html>