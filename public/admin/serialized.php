<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['admin', 'staff']);

$pageTitle = 'Serialize Item';
$comingSoonMessage = 'Managing the equipment serialization is next on the build list.';

// Temporary mock data for UI design — backend dev: replace with a DB query.
// 'status' must be 'Available', 'Active' (currently on loan) or 'Maintenance'.
$statusClasses = ['Available' => 'approved', 'Active' => 'pending', 'Maintenance' => 'declined'];
$serialItems = [
    ['id' => 1, 'serial' => 'SN-882', 'model' => 'Sony Alpha Camera', 'status' => 'Available', 'notes' => 'Good Condition'],
    ['id' => 2, 'serial' => 'SN-883', 'model' => 'Sony Alpha Camera', 'status' => 'Active', 'notes' => 'Minor cosmetic scratches'],
    ['id' => 3, 'serial' => 'SN-884', 'model' => 'Sony Alpha Camera', 'status' => 'Maintenance', 'notes' => 'Needs lens mount repair'],
    ['id' => 4, 'serial' => 'SN-102', 'model' => 'Dell XPS Laptop 15"', 'status' => 'Available', 'notes' => 'Recently upgraded RAM'],
    ['id' => 5, 'serial' => 'SN-103', 'model' => 'Dell XPS Laptop 15"', 'status' => 'Active', 'notes' => 'Charger cable replaced'],
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

                <!-- Toolbar: filters + register button -->
                <div class="requests-toolbar">
                    <div class="filter-group" role="group" aria-label="Filter serialized items">
                        <span class="filter-label">Filter:</span>
                        <button type="button" class="filter-pill active">All</button>
                        <button type="button" class="filter-pill">Available</button>
                        <button type="button" class="filter-pill">Maintenance</button>
                    </div>

                    <button type="button" class="btn btn-primary btn-sm btn-rect">+ Register New Item</button>
                </div>

                <!-- Serialized items table -->
                <div class="table-card">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Serial #</th>
                                <th scope="col">Equipment Model</th>
                                <th scope="col">Status</th>
                                <th scope="col">Condition Notes</th>
                                <th scope="col" class="col-action">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($serialItems as $item): ?>
                                <tr>
                                    <td class="cell-strong">
                                        <?= htmlspecialchars($item['serial']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($item['model']) ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $statusClasses[$item['status']] ?? 'pending' ?>">
                                            <?= htmlspecialchars($item['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($item['notes']) ?>
                                    </td>
                                    <td class="col-action">
                                        <div class="row-actions">
                                            <button type="button" class="link-text" data-id="<?= (int) $item['id'] ?>"
                                                onclick="openModal('editSerialModal')">Edit</button>
                                            <button type="button" class="link-text link-danger"
                                                data-id="<?= (int) $item['id'] ?>" onclick="openModal('maintenanceModal')"
                                                style="border:none; background:none; padding:0; font-family:inherit; font-size:inherit; cursor:pointer;">Maintenance</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <!-- Edit Serialized Item Modal -->
            <div class="modal-overlay" id="editSerialModal">
                <div class="modal" style="max-width: 28rem;">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title">Edit Item Condition</h2>
                            <p class="modal-subtitle">Update status and maintenance notes.</p>
                        </div>
                        <button class="modal-close" onclick="closeModal('editSerialModal')">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="modal-body">
                        <dl class="modal-summary">
                            <dt>Serial #</dt>
                            <dd>SN-882</dd>
                            <dt>Model</dt>
                            <dd>Sony Alpha Camera</dd>
                        </dl>
                        <div class="modal-field">
                            <label class="modal-label">Status</label>
                            <select class="input-field">
                                <option>Available</option>
                                <option>Active (In Use)</option>
                                <option>Maintenance</option>
                            </select>
                        </div>
                        <div class="modal-field">
                            <label class="modal-label">Condition Notes</label>
                            <textarea class="input-field" placeholder="E.g., Minor cosmetic scratches..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline btn-rect" onclick="closeModal('editSerialModal')">Cancel</button>
                        <button class="btn btn-primary btn-rect" onclick="closeModal('editSerialModal')">Save
                            Changes</button>
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
                    // Allow clicking outside the modal card to close it
                    const overlays = document.querySelectorAll('.modal-overlay');
                    overlays.forEach(overlay => {
                        overlay.addEventListener('click', (e) => {
                            if (e.target === overlay) closeModal(overlay.id);
                        });
                    });
                });
            </script>
            <!-- Send to Maintenance Modal -->
            <div class="modal-overlay" id="maintenanceModal">
                <div class="modal" style="max-width: 28rem;">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title">Flag for Maintenance</h2>
                            <p class="modal-subtitle">Remove this specific unit from circulation.</p>
                        </div>
                        <button class="modal-close" onclick="closeModal('maintenanceModal')">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="archive-warning-box">
                            <dl style="margin-bottom: 0; padding-bottom: 0; border-bottom: none;">
                                <dt>Serial #</dt>
                                <dd>SN-882</dd>
                            </dl>
                        </div>
                        <div class="modal-field">
                            <label class="modal-label">Reason for maintenance</label>
                            <textarea class="input-field"
                                placeholder="Describe the damage, missing parts, or issue..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline btn-rect"
                            onclick="closeModal('maintenanceModal')">Cancel</button>
                        <button class="btn btn-danger btn-rect"
                            onclick="closeModal('maintenanceModal')">Confirm</button>
                    </div>
                </div>
            </div>
            <!-- Snackbar Notification Container -->
            <div id="snackbar-container"></div>

            <!-- Clear Logs Warning Modal -->
            <div class="modal-overlay" id="clearLogsModal">
                <div class="modal" style="max-width: 28rem;">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title">Clear Transaction Logs</h2>
                            <p class="modal-subtitle">This action cannot be undone.</p>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm btn-rect"
                            onclick="openModal('clearLogsModal')">Clear Logs</button>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="archive-warning-box">
                            <div class="alert-box">
                                <div class="alert-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path
                                            d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z">
                                        </path>
                                        <line x1="12" y1="9" x2="12" y2="13"></line>
                                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                    </svg>
                                </div>
                                <div class="alert-content">
                                    <h4>Permanent Deletion</h4>
                                    <p>You are about to permanently delete all equipment transaction logs from the
                                        database. Are you absolutely sure?</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline btn-rect" onclick="closeModal('clearLogsModal')">Cancel</button>
                        <button class="btn btn-danger btn-rect" onclick="closeModal('clearLogsModal')">Yes, Clear
                            Logs</button>
                    </div>
                </div>
            </div>

            <!-- Scripts for Modal & Snackbar -->
            <script>
                function openModal(modalId) {
                    document.getElementById(modalId).classList.add('open');
                }

                function closeModal(modalId) {
                    document.getElementById(modalId).classList.remove('open');
                }

                function triggerBackup() {
                    const container = document.getElementById('snackbar-container');
                    const snackbar = document.createElement('div');
                    snackbar.className = 'snackbar snackbar--success';
                    snackbar.textContent = 'Database backup package is downloading...';

                    container.appendChild(snackbar);

                    // Trigger reflow to ensure the CSS transition works
                    setTimeout(() => {
                        snackbar.classList.add('snackbar--visible');
                    }, 10);

                    // Remove the snackbar after 3 seconds
                    setTimeout(() => {
                        snackbar.classList.remove('snackbar--visible');
                        setTimeout(() => snackbar.remove(), 250);
                    }, 3000);
                }
            </script>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>

</html>