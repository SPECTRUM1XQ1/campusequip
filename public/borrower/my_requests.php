<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../src/Reservation.php';
require_once __DIR__ . '/../../includes/helpers/pickup-helpers.php';
requireRole(['borrower']);

$pageTitle = 'Borrowing & Requests';
$pageStyles = ['shared/borrower', 'pages/borrower-my-requests'];

$reservationModel = new Reservation();
$reservationModel->expireLapsedPickups(); // cancel approved reservations whose pick-up window has ended
$all = $reservationModel->listForBorrowerDetailed((int) $_SESSION['user_id']);

// Split into the three tabs.
$borrowed = [];
$pending  = [];
$history  = [];
foreach ($all as $row) {
    switch ($row['status']) {
        case 'checked_out':
            $borrowed[] = $row;
            break;
        case 'pending':
        case 'approved':
            $pending[] = $row;
            break;
        default: // declined, cancelled, returned
            $history[] = $row;
    }
}

function fmtDateTime(?string $value): string
{
    return $value ? date('M d, Y g:i A', strtotime($value)) : '—';
}

// $variant: success | danger | warning | amber | info | neutral (see .pill in assets/css/shared/borrower.css)
function pill(string $text, string $variant): string
{
    return '<span class="pill pill--' . $variant . '">' . htmlspecialchars($text) . '</span>';
}

// One "Label: value" line inside a card.
function detailLine(string $label, string $value): string
{
    return '<div class="loan-row"><span class="label">' . htmlspecialchars($label) . '</span><span class="value">' . htmlspecialchars($value) . '</span></div>';
}

function emptyState(string $icon, string $message): string
{
    return '<div class="empty-state"><i class="ph ' . $icon . '"></i><p>' . htmlspecialchars($message) . '</p></div>';
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
        <?php include __DIR__ . '/../../includes/navigation/sidebar-admin.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../../includes/navigation/topbar.php'; ?>
            <?php include __DIR__ . '/../../includes/announcement-banner.php'; ?>

            <div class="page-body">

                <!-- Tabs (counts come from the database) -->
                <div class="tabs" role="tablist">
                    <button type="button" class="tab-btn active" id="tab-borrowed" role="tab" data-tab="borrowed">
                        <span class="tab-count"><?= count($borrowed) ?></span>
                        <span class="tab-label">Currently Borrowed</span>
                    </button>
                    <button type="button" class="tab-btn" id="tab-pending" role="tab" data-tab="pending">
                        <span class="tab-count"><?= count($pending) ?></span>
                        <span class="tab-label">Pending Request</span>
                    </button>
                    <button type="button" class="tab-btn" id="tab-history" role="tab" data-tab="history">
                        <span class="tab-count"><?= count($history) ?></span>
                        <span class="tab-label">History</span>
                    </button>
                </div>

                <!-- CURRENTLY BORROWED -->
                <div id="content-borrowed" data-list data-items="#content-borrowed .item-card" data-page-size="6" data-pager="#borrowed-pager">
                    <h4 class="section-eyebrow">Currently in Your Possession</h4>

                    <?php if (empty($borrowed)): ?>
                        <?= emptyState('ph-package', 'You are not currently borrowing any equipment.') ?>
                    <?php endif; ?>

                    <div class="loan-grid">
                    <?php foreach ($borrowed as $r):
                        $overdue = ((int) $r['is_overdue'] === 1);
                    ?>
                        <div class="item-card loan-card">
                            <div class="loan-card-head">
                                <h3 class="loan-card-title"><?= htmlspecialchars($r['model_name']) ?></h3>
                                <?= $overdue ? pill('Overdue', 'danger') : pill('Currently Borrowed', 'success') ?>
                            </div>
                            <div class="loan-card-body">
                                <?= detailLine('Serial Number', $r['serial_number'] ?: '—') ?>
                                <?= detailLine('Request Code', 'REQ-' . (int) $r['reservation_id']) ?>
                                <?= detailLine('Borrowed Date', fmtDateTime($r['checked_out_at'])) ?>
                                <?= detailLine('Due Date', fmtDateTime($r['due_at'])) ?>
                            </div>
                            <div class="notice <?= $overdue ? 'notice--danger' : 'notice--info' ?>">
                                <i class="ph ph-info"></i>
                                <span>
                                    <?= $overdue
                                        ? 'This item is past its due date. Please return it to the Lab Desk as soon as possible.'
                                            . ((float) $r['late_fine_rate'] > 0
                                                ? ' Late fine so far: ₱' . number_format((float) $r['late_fine_rate'] * (int) $r['days_late'], 2)
                                                    . ' (' . (int) $r['days_late'] . ' day' . ((int) $r['days_late'] === 1 ? '' : 's') . ' × ₱' . number_format((float) $r['late_fine_rate'], 2) . '/day).'
                                                : ' Late fines may apply.')
                                        : 'Present your physical Student ID at the Lab Desk upon return.'
                                            . ((float) $r['late_fine_rate'] > 0 ? ' Returning after the due date costs ₱' . number_format((float) $r['late_fine_rate'], 2) . ' per day.' : '') ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                    <div class="pager" id="borrowed-pager"></div>
                </div>

                <!-- PENDING REQUESTS (pending + approved-awaiting-pickup) -->
                <div id="content-pending" hidden data-list data-items="#content-pending .item-card" data-page-size="6" data-pager="#pending-pager">
                    <h4 class="section-eyebrow">Awaiting Staff Sign-Off &amp; Pickup</h4>

                    <?php if (empty($pending)): ?>
                        <?= emptyState('ph-hourglass', 'You have no pending requests.') ?>
                    <?php endif; ?>

                    <div class="loan-grid">
                    <?php foreach ($pending as $r):
                        $isApproved = ($r['status'] === 'approved');
                        $win = pickupWindowFor($r);
                    ?>
                        <div class="item-card loan-card">
                            <div class="loan-card-head">
                                <h3 class="loan-card-title"><?= htmlspecialchars($r['model_name']) ?></h3>
                                <?= $isApproved ? pill('Approved — Ready for Pickup', 'info') : pill('Pending Request', 'warning') ?>
                            </div>
                            <div class="loan-card-body">
                                <?php if ($isApproved): ?>
                                    <?= detailLine('Serial Number', $r['serial_number'] ?: '—') ?>
                                <?php endif; ?>
                                <?= detailLine('Request Code', 'REQ-' . (int) $r['reservation_id']) ?>
                                <div class="loan-row">
                                    <span class="label">Pick-Up Window<?php if (!$isApproved): ?><small>once approved</small><?php endif; ?></span>
                                    <span class="value">
                                        <?= $win['text'] ?>
                                        <?php if ($isApproved): ?>
                                            <span class="loan-row-extra"><?= pickupCountdownTag($win, '', 'Pick-up window ended — this reservation is cancelled') ?></span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?= detailLine('Purpose', $r['purpose'] ?: '—') ?>
                                <?= detailLine('Status', $isApproved ? 'Approved — bring your ID to the Lab Desk to pick it up' : 'Awaiting Staff Approval') ?>
                            </div>
                            <div class="loan-card-foot">
                                <span class="loan-card-hint">ID validation required at pickup. Not collected in time = reservation cancelled.</span>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-rect cancel-btn"
                                    data-id="<?= (int) $r['reservation_id'] ?>"
                                    data-model="<?= htmlspecialchars($r['model_name'], ENT_QUOTES) ?>"><i class="ph ph-x-circle" aria-hidden="true"></i><span class="btn-text">Cancel Request</span></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                    <div class="pager" id="pending-pager"></div>
                </div>

                <!-- HISTORY -->
                <div id="content-history" hidden data-list data-items="#content-history .item-card" data-page-size="6" data-pager="#history-pager">
                    <h4 class="section-eyebrow">Past Borrowing History</h4>

                    <?php if (empty($history)): ?>
                        <?= emptyState('ph-clock-counter-clockwise', 'Your past reservations will appear here.') ?>
                    <?php endif; ?>

                    <div class="loan-grid">
                    <?php foreach ($history as $r):
                        $status = $r['status'];
                        $pickupExpired = ($status === 'cancelled' && $r['decline_reason'] === Reservation::EXPIRED_REASON);
                        $lateReturn = ($status === 'returned' && $r['returned_at'] && $r['due_at'] && strtotime($r['returned_at']) > strtotime($r['due_at']));

                        if ($status === 'returned') {
                            $statusPill = $lateReturn ? pill('Returned Late', 'amber') : pill('Returned', 'success');
                        } elseif ($status === 'declined') {
                            $statusPill = pill('Declined', 'danger');
                        } elseif ($pickupExpired) {
                            $statusPill = pill('Pick-Up Expired', 'danger');
                        } else {
                            $statusPill = pill('Cancelled', 'neutral');
                        }

                        $conditionText = '';
                        if ($status === 'returned') {
                            if ($r['return_condition'] === 'damaged') {
                                $conditionText = 'Damaged' . ($r['damage_notes'] ? ' — ' . $r['damage_notes'] : '');
                            } elseif ($r['return_condition'] === 'missing_accessories') {
                                $conditionText = 'Missing accessories' . (!empty($r['missing_accessories']) ? ': ' . implode(', ', $r['missing_accessories']) : '');
                            } elseif ($r['return_condition'] === 'good') {
                                $conditionText = 'Good condition';
                            }
                        }
                    ?>
                        <div class="item-card loan-card">
                            <div class="loan-card-head">
                                <h3 class="loan-card-title"><?= htmlspecialchars($r['model_name']) ?></h3>
                                <?= $statusPill ?>
                            </div>
                            <div class="loan-card-body">
                                <?php if ($r['serial_number']): ?>
                                    <?= detailLine('Serial Number', $r['serial_number']) ?>
                                <?php endif; ?>
                                <?= detailLine('Request Code', 'REQ-' . (int) $r['reservation_id']) ?>
                                <?php if ($status === 'returned'): ?>
                                    <?= detailLine('Borrowed Date', fmtDateTime($r['checked_out_at'])) ?>
                                    <?= detailLine('Returned Date', fmtDateTime($r['returned_at'])) ?>
                                    <?php if ($conditionText !== ''): ?>
                                        <?= detailLine('Condition on Return', $conditionText) ?>
                                    <?php endif; ?>
                                    <?php if ((float) $r['fine_total'] > 0): ?>
                                        <?= detailLine('Fine', '₱' . number_format((float) $r['fine_total'], 2) . ((float) $r['fine_unpaid'] > 0 ? ' (unpaid: ₱' . number_format((float) $r['fine_unpaid'], 2) . ')' : ' (paid)')) ?>
                                    <?php endif; ?>
                                <?php elseif ($pickupExpired): ?>
                                    <?= detailLine('Pick-Up Window', strip_tags(html_entity_decode(pickupWindowFor($r)['text']))) ?>
                                    <?= detailLine('Reason', 'Not picked up before the window ended') ?>
                                    <?= detailLine('Requested On', fmtDateTime($r['requested_at'])) ?>
                                <?php else: ?>
                                    <?= detailLine('Request Window', fmtDateTime($r['start_at']) . ' → ' . fmtDateTime($r['end_at'])) ?>
                                    <?= detailLine('Requested On', fmtDateTime($r['requested_at'])) ?>
                                <?php endif; ?>
                                <?php if ($status === 'declined'): ?>
                                    <?= detailLine('Reason', $r['decline_reason'] ?: 'No reason given') ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                    <div class="pager" id="history-pager"></div>
                </div>

            </div>

            <!-- Cancel Request Modal -->
<?php include __DIR__ . '/../../includes/modal/borrower/requests-cancel.php'; ?>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/navigation/footer.php'; ?>
    <script src="<?= APP_BASE ?>/public/assets/js/filters.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/filters.js') ?: 1 ?>"></script>
    <script src="<?= APP_BASE ?>/public/assets/js/countdown.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/countdown.js') ?: 1 ?>"></script>

    <script src="<?= APP_BASE ?>/public/assets/js/pages/borrower-my-requests.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/pages/borrower-my-requests.js') ?: 1 ?>"></script>
</body>

</html>
