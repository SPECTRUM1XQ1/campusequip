<?php
// Notices under the top bar, on every page that has the top bar (it is included from navigation/topbar.php).
//   borrowers      : the borrower announcement, plus the maintenance message while maintenance mode is on
//   staff and admin: the staff announcement, plus a reminder while maintenance mode is on
// Prints nothing when there is nothing to say.
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../src/Settings.php';

$noticeIsStaff       = in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true);
$noticeMaintenance   = Settings::isMaintenance();
$noticeAnnouncement  = trim(Settings::get($noticeIsStaff ? 'announcement_staff' : 'announcement'));
?>
<?php if ($noticeMaintenance || $noticeAnnouncement !== ''): ?>
    <div class="notice-stack">
        <?php if ($noticeMaintenance): ?>
            <div class="notice-banner notice-warning" role="status">
                <i class="ph ph-wrench" aria-hidden="true"></i>
                <?php if ($noticeIsStaff): ?>
                    <span><strong>Maintenance mode is ON.</strong> Borrowers cannot submit new reservation requests. Turn it off in System Settings.</span>
                <?php else: ?>
                    <span><strong>Maintenance:</strong> <?= htmlspecialchars(Settings::maintenanceMessage()) ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if ($noticeAnnouncement !== ''): ?>
            <div class="notice-banner notice-info" role="status">
                <i class="ph ph-megaphone" aria-hidden="true"></i>
                <span><?= htmlspecialchars($noticeAnnouncement) ?></span>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
