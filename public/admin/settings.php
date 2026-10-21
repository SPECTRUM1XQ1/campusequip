<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole('admin');

$pageTitle = 'System Settings';
$pageStyles = ['pages/admin-settings'];
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
        <?php include __DIR__ . '/../../includes/navigation/sidebar-admin.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../../includes/navigation/topbar.php'; ?>

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
                                <button type="button" class="btn btn-outline btn-sm btn-rect" data-backup><i class="ph ph-database" aria-hidden="true"></i><span class="btn-text">Backup Now</span></button>
                            </div>

                            <div class="list-item">
                                <div class="list-info">
                                    <h5>Export records</h5>
                                    <p>View reservations, checkouts, fines or transaction logs, then print them or download a CSV for Excel / Google Sheets. Logs are permanent and cannot be deleted.</p>
                                </div>
                                <a href="<?= APP_BASE ?>/public/admin/export.php" class="btn btn-outline btn-sm btn-rect admin-settings-s1"><i class="ph ph-export" aria-hidden="true"></i><span class="btn-text">Open Export</span></a>
                            </div>
                        </div>
                    </div>

                    <!-- Form actions -->
                    <div class="card-actions card-actions-right">
                        <button type="reset" class="btn btn-outline"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                        <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk" aria-hidden="true"></i><span class="btn-text">Save Changes</span></button>
                    </div>

                </form>
            </section>
            <!-- Snackbar Notification Container -->
<div id="snackbar-container"></div>

<script src="<?= APP_BASE ?>/public/assets/js/pages/admin-settings.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/pages/admin-settings.js') ?: 1 ?>"></script>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/navigation/footer.php'; ?>
</body>

</html>