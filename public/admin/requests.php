<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['admin', 'staff']);

$pageTitle = 'Request & Pending';
$comingSoonMessage = 'The approve/decline workflow for reservation requests is next on the build list.';

// Temporary mock data for UI design — backend dev: replace with a DB query.
// Keep the same keys and the markup below will keep working.
$totalPending = 7;
$requests = [
    ['id' => 'REQ-100', 'borrower' => 'Justin Peralta', 'item' => 'Sony Alpha Camera', 'serial' => 'SN-882', 'dates' => 'Sept 09 9AM - Sept 10 10AM', 'status' => 'Pending'],
    ['id' => 'REQ-101', 'borrower' => 'Basti Candelario', 'item' => 'Dell XPS Laptop 15"', 'serial' => 'SN-102', 'dates' => 'Sept 11 1PM - Sept 14 5PM', 'status' => 'Pending'],
    ['id' => 'REQ-102', 'borrower' => 'Gustavo Peralta', 'item' => 'Multimedia Projector', 'serial' => 'SN-501', 'dates' => 'Sept 15 9AM - Sept 15 6PM', 'status' => 'Pending'],
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

                <!-- Toolbar: count + filters -->
                <div class="requests-toolbar">
                    <p class="results-count">
                        Showing <strong>1</strong> to <strong><?= count($requests) ?></strong>
                        of <strong><?= (int) $totalPending ?></strong> pending requests
                    </p>

                    <div class="filter-group" role="group" aria-label="Filter requests">
                        <span class="filter-label">Filter:</span>
                        <button type="button" class="filter-pill">All</button>
                        <button type="button" class="filter-pill">Approved</button>
                        <button type="button" class="filter-pill active">Pending</button>
                    </div>
                </div>

                <!-- Requests table -->
                <div class="table-card">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">REQ ID</th>
                                <th scope="col">Borrower</th>
                                <th scope="col">Item Name</th>
                                <th scope="col">Serial #</th>
                                <th scope="col">Dates</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="col-action">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $req): ?>
                                <tr>
                                    <td class="cell-id"><?= htmlspecialchars($req['id']) ?></td>
                                    <td class="cell-strong"><?= htmlspecialchars($req['borrower']) ?></td>
                                    <td><?= htmlspecialchars($req['item']) ?></td>
                                    <td><?= htmlspecialchars($req['serial']) ?></td>
                                    <td class="cell-dates"><?= htmlspecialchars($req['dates']) ?></td>
                                    <td>
                                        <span class="status-badge <?= strtolower(htmlspecialchars($req['status'])) ?>">
                                            <?= htmlspecialchars($req['status']) ?>
                                        </span>
                                    </td>
                                    <td class="col-action">
                                        <div class="row-actions">
                                            <button type="button" class="btn-decline"
                                                data-id="<?= htmlspecialchars($req['id']) ?>">Decline</button>
                                            <button type="button" class="btn-approve"
                                                data-id="<?= htmlspecialchars($req['id']) ?>">Approve</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </section>

            <!-- ============ MODAL: Approve reservation ============ -->
            <div class="modal-overlay" id="approveModal" aria-hidden="true">
                <div class="modal" role="dialog" aria-modal="true" aria-labelledby="approveTitle" tabindex="-1">
                    <div class="modal-header">
                        <div>
                            <h3 class="modal-title" id="approveTitle">Approve reservation</h3>
                            <p class="modal-subtitle">Review request and set pickup date.</p>
                        </div>
                        <button type="button" class="modal-close" data-close-modal aria-label="Close">
                            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor"
                                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="9" />
                                <path d="m9.5 9.5 5 5m0-5-5 5" />
                            </svg>
                        </button>
                    </div>

                    <form method="post" action="">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="request_id" value="">

                        <div class="modal-body">
                            <dl class="modal-summary">
                                <dt>Request ID</dt>
                                <dd data-fill="id"></dd>
                                <dt>Status</dt>
                                <dd><span class="status-badge pending" data-fill="status">Pending</span></dd>
                                <dt>Borrower</dt>
                                <dd data-fill="borrower"></dd>
                                <dt>Item</dt>
                                <dd data-fill="item"></dd>
                                <dt>Dates</dt>
                                <dd data-fill="dates"></dd>
                            </dl>

                            <div class="modal-field">
                                <span class="modal-label">Pickup date</span>

                                <label class="option-row">
                                    <input type="radio" name="pickup" value="tomorrow" checked>
                                    <span>Tomorrow (Next lab day)</span>
                                </label>

                                <label class="option-row">
                                    <input type="radio" name="pickup" value="custom">
                                    <span>Custom date</span>
                                    <svg class="option-icon" viewBox="0 0 24 24" aria-hidden="true">
                                        <rect x="4" y="5" width="16" height="15" rx="2" />
                                        <path d="M8 3v4M16 3v4M4 10h16" />
                                    </svg>
                                </label>

                                <input type="date" class="input-field modal-date" name="pickup_date" id="pickupDate"
                                    aria-label="Custom pickup date" hidden>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline btn-rect" data-close-modal>Cancel</button>
                            <button type="submit" class="btn btn-primary btn-rect">Confirm approval</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ============ MODAL: Decline reservation ============ -->
            <div class="modal-overlay" id="declineModal" aria-hidden="true">
                <div class="modal" role="dialog" aria-modal="true" aria-labelledby="declineTitle" tabindex="-1">
                    <div class="modal-header">
                        <div>
                            <h3 class="modal-title" id="declineTitle">Decline reservation</h3>
                            <p class="modal-subtitle">Reason is saved in logs.</p>
                        </div>
                        <button type="button" class="modal-close" data-close-modal aria-label="Close">
                            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor"
                                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="9" />
                                <path d="m9.5 9.5 5 5m0-5-5 5" />
                            </svg>
                        </button>
                    </div>

                    <form method="post" action="">
                        <input type="hidden" name="action" value="decline">
                        <input type="hidden" name="request_id" value="">

                        <div class="modal-body">
                            <dl class="modal-summary">
                                <dt>Request ID</dt>
                                <dd data-fill="id"></dd>
                                <dt>Borrower</dt>
                                <dd data-fill="borrower"></dd>
                                <dt>Item</dt>
                                <dd data-fill="item"></dd>
                                <dt>Dates</dt>
                                <dd data-fill="dates"></dd>
                            </dl>

                            <div class="modal-field">
                                <label class="modal-label" for="declineReason">Reason for decline</label>
                                <select class="input-field" id="declineReason" name="reason" required>
                                    <option value="" selected disabled>Select a reason</option>
                                    <option value="unavailable">Equipment unavailable</option>
                                    <option value="maintenance">Equipment under maintenance</option>
                                    <option value="schedule">Schedule conflict</option>
                                    <option value="ineligible">Borrower not eligible</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div class="modal-field">
                                <label class="modal-label" for="declineDetails">Additional details</label>
                                <textarea class="input-field" id="declineDetails" name="details" rows="4"
                                    placeholder="Add context for the borrower and equipment desk logs..."></textarea>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline btn-rect" data-close-modal>Cancel</button>
                            <button type="submit" class="btn btn-danger btn-rect">Confirm decline</button>
                        </div>
                    </form>
                </div>
            </div>

        </main>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>

    <script>
        (function () {
            var approveModal = document.getElementById('approveModal');
            var declineModal = document.getElementById('declineModal');
            var pickupDate = document.getElementById('pickupDate');
            var lastTrigger = null;

            // Fill a modal's summary box from the table row that was clicked.
            // Cell order: 0 REQ ID, 1 Borrower, 2 Item, 3 Serial, 4 Dates, 5 Status
            function fillModal(modal, btn) {
                var cells = btn.closest('tr').querySelectorAll('td');
                var data = {
                    id: btn.dataset.id,
                    borrower: cells[1].textContent.trim(),
                    item: cells[2].textContent.trim() + ' - ' + cells[3].textContent.trim(),
                    dates: cells[4].textContent.trim(),
                    status: cells[5].textContent.trim()
                };
                modal.querySelectorAll('[data-fill]').forEach(function (el) {
                    el.textContent = data[el.dataset.fill];
                });
                modal.querySelector('input[name="request_id"]').value = data.id;
            }

            function openModal(modal, btn) {
                lastTrigger = btn;
                fillModal(modal, btn);
                modal.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');
                modal.querySelector('.modal').focus();
            }

            function closeModal(modal) {
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
                modal.querySelector('form').reset();
                pickupDate.hidden = true;
                if (lastTrigger) lastTrigger.focus();
            }

            // Open: Approve / Decline buttons in the table
            document.addEventListener('click', function (e) {
                var approveBtn = e.target.closest('.data-table .btn-approve');
                var declineBtn = e.target.closest('.data-table .btn-decline');
                if (approveBtn) openModal(approveModal, approveBtn);
                if (declineBtn) openModal(declineModal, declineBtn);
            });

            // Close: X icon, Cancel button, or click on the dark backdrop
            document.addEventListener('click', function (e) {
                var overlay = e.target.closest('.modal-overlay');
                if (!overlay) return;
                if (e.target.closest('[data-close-modal]') || e.target === overlay) {
                    closeModal(overlay);
                }
            });

            // Close: Escape key
            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') return;
                [approveModal, declineModal].forEach(function (m) {
                    if (m.classList.contains('open')) closeModal(m);
                });
            });

            // Show the date picker only when "Custom date" is selected
            pickupDate.min = new Date().toISOString().slice(0, 10);
            approveModal.querySelectorAll('input[name="pickup"]').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    pickupDate.hidden = this.value !== 'custom';
                    pickupDate.required = this.value === 'custom';
                });
            });
        })();
    </script>

    <!-- Filter Toggle & Table Filtering Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const filterPills = document.querySelectorAll('.filter-group .filter-pill');
            const tableRows = document.querySelectorAll('.data-table tbody tr');

            filterPills.forEach(pill => {
                pill.addEventListener('click', function (e) {
                    e.preventDefault();

                    // 1. Visual toggle: Move the blue highlight
                    filterPills.forEach(p => p.classList.remove('active'));
                    this.classList.add('active');

                    // 2. Functional filter: Get the text of the clicked pill (e.g., "pending")
                    const filterValue = this.textContent.trim().toLowerCase();

                    // 3. Loop through every row in the table
                    tableRows.forEach(row => {
                        const statusBadge = row.querySelector('.status-badge');

                        if (statusBadge) {
                            const statusText = statusBadge.textContent.trim().toLowerCase();

                            // Show the row if "All" is clicked OR if the status matches the pill
                            if (filterValue === 'all' || statusText.includes(filterValue)) {
                                row.style.display = ''; // Show row
                            } else {
                                row.style.display = 'none'; // Hide row
                            }
                        }
                    });
                });
            });
        });
    </script>
</body>

</html>