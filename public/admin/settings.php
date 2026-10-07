<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['admin', 'staff']);

$pageTitle = 'System Settings';
$comingSoonMessage = 'Fine rates and loan period settings are next on the build list.';

// Temporary mock data for UI design — backend dev: load these from the DB.
$settings = [
    'university_name'   => 'CampusEquip University',
    'admin_email'       => 'admin@campusequip.edu',
    'loan_period_days'  => 7,
    'daily_fine_rate'   => '5.00',
    'notify_new_request' => true,
    'notify_overdue'     => true,
    'notify_returns'     => false,
    'maintenance_mode'   => false,
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
                <p class="page-note">Configure loan rules, notifications and system behaviour</p>

                <form class="settings-form" method="post" action="">

                    <!-- Card 1: General Settings -->
                    <div class="card-container">
                        <div class="section-header">
                            <h3 class="section-title">General Settings</h3>
                            <span class="section-subtitle">Basic details and loan rules</span>
                        </div>

                        <div class="form-group">
                            <label class="input-label" for="university_name">University Name</label>
                            <input class="input-field" type="text" id="university_name" name="university_name"
                                value="<?= htmlspecialchars($settings['university_name']) ?>">
                        </div>

                        <div class="form-group">
                            <label class="input-label" for="admin_email">Admin Email</label>
                            <input class="input-field" type="email" id="admin_email" name="admin_email"
                                value="<?= htmlspecialchars($settings['admin_email']) ?>">
                        </div>

                        <div class="grid-row grid-cols-2">
                            <div>
                                <label class="input-label" for="loan_period_days">Default Loan Period (days)</label>
                                <input class="input-field" type="number" min="1" id="loan_period_days" name="loan_period_days"
                                    value="<?= (int) $settings['loan_period_days'] ?>">
                            </div>
                            <div>
                                <label class="input-label" for="daily_fine_rate">Daily Fine Rate (₱ per day)</label>
                                <input class="input-field" type="number" min="0" step="0.01" id="daily_fine_rate" name="daily_fine_rate"
                                    value="<?= htmlspecialchars($settings['daily_fine_rate']) ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Notification Preferences -->
                    <div class="card-container">
                        <div class="section-header">
                            <h3 class="section-title">Notification Preferences</h3>
                            <span class="section-subtitle">Emails sent to the admin address</span>
                        </div>

                        <div>
                            <div class="list-item">
                                <div class="list-info">
                                    <h5>New reservation requests</h5>
                                    <p>Notify me when a borrower submits a request.</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="notify_new_request" aria-label="New reservation requests"
                                        <?= $settings['notify_new_request'] ? 'checked' : '' ?>>
                                    <span class="switch-slider"></span>
                                </label>
                            </div>

                            <div class="list-item">
                                <div class="list-info">
                                    <h5>Overdue items</h5>
                                    <p>Notify me when equipment passes its due date.</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="notify_overdue" aria-label="Overdue items"
                                        <?= $settings['notify_overdue'] ? 'checked' : '' ?>>
                                    <span class="switch-slider"></span>
                                </label>
                            </div>

                            <div class="list-item">
                                <div class="list-info">
                                    <h5>Returned equipment</h5>
                                    <p>Notify me when a borrower returns an item.</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="notify_returns" aria-label="Returned equipment"
                                        <?= $settings['notify_returns'] ? 'checked' : '' ?>>
                                    <span class="switch-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: System Maintenance -->
                    <div class="card-container">
                        <div class="section-header">
                            <h3 class="section-title">System Maintenance</h3>
                            <span class="section-subtitle">Handle with care</span>
                        </div>

                        <div>
                            <div class="list-item">
                                <div class="list-info">
                                    <h5>Maintenance mode</h5>
                                    <p>Temporarily block borrowers from making new requests.</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="maintenance_mode" aria-label="Maintenance mode"
                                        <?= $settings['maintenance_mode'] ? 'checked' : '' ?>>
                                    <span class="switch-slider"></span>
                                </label>
                            </div>

                            <div class="list-item">
                                <div class="list-info">
                                    <h5>Backup database</h5>
                                    <p>Download a full copy of the current data.</p>
                                </div>
                                <button type="button" class="btn btn-outline btn-sm btn-rect" onclick="triggerBackup()">Backup Now</button>
                            </div>

                            <div class="list-item">
                                <div class="list-info">
                                    <h5>Clear transaction logs</h5>
                                    <p>Permanently delete all log entries. This cannot be undone.</p>
                                </div>
                                <button type="button" class="btn btn-outline-danger btn-sm btn-rect" onclick="openModal('clearLogsModal')">Clear Logs</button>
                            </div>
                        </div>
                    </div>

                    <!-- Form actions -->
                    <div class="card-actions card-actions-right">
                        <button type="reset" class="btn btn-outline">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>

                </form>
            </section>
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
            <button class="modal-close" onclick="closeModal('clearLogsModal')">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="archive-warning-box">
                <div class="alert-box">
                    <div class="alert-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    </div>
                    <div class="alert-content">
                        <h4>Permanent Deletion</h4>
                        <p>You are about to permanently delete all equipment transaction logs from the database. Are you absolutely sure?</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline btn-rect" onclick="closeModal('clearLogsModal')">Cancel</button>
            <button class="btn btn-danger btn-rect" onclick="closeModal('clearLogsModal')">Yes, Clear Logs</button>
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