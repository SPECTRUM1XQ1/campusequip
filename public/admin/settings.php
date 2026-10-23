<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../src/Settings.php';
requireRole('admin');

$pageTitle = 'System Settings';
$pageStyles = ['pages/admin-settings'];

// Live numbers for "Needs attention" and "System status". If the query fails the page still opens.
$snap = [];
try {
    $snap = (new Settings())->snapshot();
} catch (Throwable $e) {
    error_log('System Settings snapshot failed: ' . $e->getMessage());
}
$n = fn(string $key): int => (int) ($snap[$key] ?? 0);

$values = Settings::all();
$maintenanceOn = $values['maintenance_mode'] === '1';

$attention = [
    ['key' => 'pending',  'label' => 'Pending requests',        'href' => '/public/manage/requests.php',  'tone' => 'warn'],
    ['key' => 'ready',    'label' => 'Waiting for pick-up',     'href' => '/public/staff/handover.php',   'tone' => 'warn'],
    ['key' => 'overdue',  'label' => 'Overdue loans',           'href' => '/public/manage/checkouts.php', 'tone' => 'danger'],
    ['key' => 'unpaid_fines', 'label' => 'Unpaid fines',        'href' => '/public/manage/fines.php',     'tone' => 'warn'],
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
                <p class="page-note">Check what needs attention, post notices for borrowers and staff, and see the rules the system runs on</p>

                <div class="settings-stack">

                    <!-- Needs attention -->
                    <div class="card-container settings-wide">
                        <div class="section-header">
                            <h3 class="section-title">Needs attention</h3>
                            <span class="section-subtitle">Live counts, click one to open it</span>
                        </div>
                        <div class="attn-grid">
                            <?php foreach ($attention as $a): $count = $n($a['key']); ?>
                                <a class="attn-tile<?= $count > 0 ? ' is-' . $a['tone'] : '' ?>" href="<?= APP_BASE . $a['href'] ?>">
                                    <span class="attn-num"><?= $count ?></span>
                                    <span class="attn-label"><?= htmlspecialchars($a['label']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($n('unpaid_fines') > 0): ?>
                            <p class="field-hint">Unpaid fines total ₱<?= number_format((float) ($snap['unpaid_total'] ?? 0), 2) ?>.</p>
                        <?php endif; ?>
                    </div>

                    <div class="settings-col">
                    <form class="settings-form" id="settings-form" method="post" action="">

                        <!-- Announcement + desk info -->
                        <div class="card-container">
                            <div class="section-header">
                                <h3 class="section-title">Announcement &amp; desk info</h3>
                                <span class="section-subtitle">What borrowers and staff see</span>
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="announcement">Announcement for borrowers</label>
                                <textarea class="input-field" id="announcement" name="announcement" rows="2" maxlength="300"
                                    placeholder="e.g. The equipment desk is closed on Friday."><?= htmlspecialchars($values['announcement']) ?></textarea>
                                <p class="field-hint">Shown under the top bar on every borrower page. Leave empty to hide it.</p>
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="announcement_staff">Announcement for staff</label>
                                <textarea class="input-field" id="announcement_staff" name="announcement_staff" rows="2" maxlength="300"
                                    placeholder="e.g. Inventory count on Monday, please finish all returns by 4 PM."><?= htmlspecialchars($values['announcement_staff']) ?></textarea>
                                <p class="field-hint">Shown under the top bar on every staff and admin page. Leave empty to hide it.</p>
                            </div>

                            <div class="grid-row grid-cols-2">
                                <div>
                                    <label class="input-label" for="desk_hours">Desk hours</label>
                                    <input class="input-field" type="text" id="desk_hours" name="desk_hours" maxlength="100"
                                        placeholder="e.g. Mon–Fri, 8:00 AM – 5:00 PM"
                                        value="<?= htmlspecialchars($values['desk_hours']) ?>">
                                </div>
                                <div>
                                    <label class="input-label" for="desk_contact">Desk contact</label>
                                    <input class="input-field" type="text" id="desk_contact" name="desk_contact" maxlength="100"
                                        placeholder="e.g. IT Laboratory, Room 204"
                                        value="<?= htmlspecialchars($values['desk_contact']) ?>">
                                </div>
                            </div>
                            <p class="field-hint">Shown on the sign-in page. Leave both empty to hide that section.</p>
                        </div>

                        <!-- Maintenance mode -->
                        <div class="card-container">
                            <div class="section-header">
                                <h3 class="section-title">Maintenance mode</h3>
                                <span class="section-subtitle">Pause new reservation requests</span>
                            </div>

                            <div class="list-item">
                                <div class="list-info">
                                    <h5>Pause new requests</h5>
                                    <p>Borrowers can still browse and see their records, but cannot submit a new request. Staff and admins are not affected.</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="maintenance_mode" name="maintenance_mode" aria-label="Pause new requests"
                                        <?= $maintenanceOn ? 'checked' : '' ?>>
                                    <span class="switch-slider"></span>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="maintenance_message">Message shown to borrowers</label>
                                <textarea class="input-field" id="maintenance_message" name="maintenance_message" rows="2" maxlength="200"><?= htmlspecialchars($values['maintenance_message']) ?></textarea>
                            </div>
                        </div>

                        <div class="card-actions card-actions-right">
                            <button type="reset" class="btn btn-outline"><i class="ph ph-arrow-counter-clockwise" aria-hidden="true"></i><span class="btn-text">Discard changes</span></button>
                            <button type="submit" class="btn btn-primary" id="settings-save-btn"><i class="ph ph-floppy-disk" aria-hidden="true"></i><span class="btn-text">Save Changes</span></button>
                        </div>
                    </form>
                    </div>

                    <div class="settings-col">
                    <!-- Borrowing rules (read-only) -->
                    <div class="card-container">
                        <div class="section-header">
                            <h3 class="section-title">Borrowing rules</h3>
                            <span class="section-subtitle">System defaults</span>
                        </div>
                        <div class="attn-grid attn-grid-2">
                            <div class="attn-tile">
                                <span class="attn-num"><?= htmlspecialchars(date('g:i A', strtotime(LOAN_DUE_TIME))) ?></span>
                                <span class="attn-label">Return time on the due date</span>
                            </div>
                            <div class="attn-tile">
                                <span class="attn-num"><?= (int) PICKUP_WINDOW_DAYS ?> days</span>
                                <span class="attn-label">Pick-up window</span>
                            </div>
                            <div class="attn-tile">
                                <span class="attn-num"><?= (int) DEFAULT_MAX_LOAN_DAYS ?> days</span>
                                <span class="attn-label">Default maximum loan</span>
                            </div>
                            <div class="attn-tile">
                                <span class="attn-num">₱<?= number_format((float) DEFAULT_LATE_FINE_RATE, 2) ?></span>
                                <span class="attn-label">Default late fine per day</span>
                            </div>
                        </div>
                        <p class="field-hint">Each equipment model can set its own maximum loan and late fine in the Equipment Catalog; these defaults apply to new models.</p>
                        <div class="card-actions">
                            <a href="<?= APP_BASE ?>/public/manage/catalog.php" class="btn btn-outline btn-sm btn-rect"><i class="ph ph-book-open" aria-hidden="true"></i><span class="btn-text">Open Equipment Catalog</span></a>
                        </div>
                    </div>

                    <!-- System status -->
                    <div class="card-container">
                        <div class="section-header">
                            <h3 class="section-title">System status</h3>
                            <span class="section-subtitle">Read-only</span>
                        </div>
                        <dl class="sys-list">
                            <div><dt>Application</dt><dd><?= htmlspecialchars(APP_NAME) ?></dd></div>
                            <div><dt>PHP version</dt><dd><?= htmlspecialchars(PHP_VERSION) ?></dd></div>
                            <div><dt>Database</dt><dd><?= $snap ? 'Connected' : 'Check the error log' ?></dd></div>
                            <div><dt>Server time</dt><dd><?= htmlspecialchars(date('M j, g:i A')) ?></dd></div>
                            <div><dt>Users</dt><dd><?= $n('users') ?></dd></div>
                            <div><dt>Equipment models</dt><dd><?= $n('models') ?></dd></div>
                            <div><dt>Serialized units</dt><dd><?= $n('units') ?></dd></div>
                            <div><dt>Items on loan</dt><dd><?= $n('active_loans') ?></dd></div>
                        </dl>
                    </div>

                    <!-- Data tools -->
                    <div class="card-container">
                        <div class="section-header">
                            <h3 class="section-title">Data tools</h3>
                            <span class="section-subtitle">Records and reports</span>
                        </div>
                        <div class="list-item">
                            <div class="list-info">
                                <h5>Export records</h5>
                                <p>View reservations, checkouts, fines or transaction logs, then print them or download a CSV for Excel / Google Sheets.</p>
                            </div>
                            <a href="<?= APP_BASE ?>/public/admin/export.php" class="btn btn-outline btn-sm btn-rect"><i class="ph ph-export" aria-hidden="true"></i><span class="btn-text">Open Export</span></a>
                        </div>
                    </div>
                    </div>

                </div>
            </section>
            <!-- Snackbar Notification Container -->
<div id="snackbar-container"></div>

<script src="<?= APP_BASE ?>/public/assets/js/pages/admin-settings.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/pages/admin-settings.js') ?: 1 ?>"></script>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/navigation/footer.php'; ?>
</body>

</html>
