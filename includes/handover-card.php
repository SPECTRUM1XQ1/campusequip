<?php
// One "Ready for Handover" card. Expects $row (a row from Reservation::listReadyForCheckout()).
// Shared by manage/checkouts.php and staff/handover.php so both always look the same.
//
// Pick-up window: opens at the reservation start and stays open for PICKUP_WINDOW_DAYS,
// but never later than the loan's end date. (This is different from the due date, which
// is when the item has to come back.) When it ends the reservation is cancelled
// (Reservation::expireLapsedPickups) and this card disappears.
require_once __DIR__ . '/helpers/pickup-helpers.php';

$win = pickupWindowFor($row, 'M j, g:i A');

// Values for the Hand Over button, escaped once so the onclick stays on one line.
// (A line break inside a quoted JS string would break the button.)
$jsItem = $row['model_name'] . ' (SN: ' . $row['serial_number'] . ')';
?>
<article class="request-card" id="handover-card-<?= (int)$row['reservation_id'] ?>">
    <div class="ops-card-head">
        <span class="cell-id">REQ ID: <?= (int)$row['reservation_id'] ?></span>
        <span class="meta-label">Assigned by: <?= htmlspecialchars($row['assigned_by_name'] ?: 'Staff') ?></span>
    </div>

    <div class="ops-field">
        <p class="meta-label">Borrower</p>
        <h4 class="meta-value"><?= htmlspecialchars($row['borrower_name']) ?></h4>
        <span class="ui-handover-card-s1"><?= htmlspecialchars($row['borrower_id_number']) ?></span>
    </div>
    <div class="ops-field">
        <p class="meta-label">Item & Unit Serial</p>
        <p class="meta-text"><strong><?= htmlspecialchars($row['model_name']) ?></strong> (SN: <?= htmlspecialchars($row['serial_number']) ?>)</p>
    </div>
    <div class="ops-field">
        <p class="meta-label">Expected Pick Up</p>
        <p class="meta-text pickup-range"><?= $win['text'] ?></p>
        <?= pickupCountdownTag($win, 'remove') ?>
    </div>

    <button type="button" class="btn btn-primary btn-block"
            data-handover="<?= (int)$row['reservation_id'] ?>" data-borrower="<?= e($row['borrower_name']) ?>" data-id-number="<?= e($row['borrower_id_number']) ?>" data-item="<?= e($jsItem) ?>" data-due="<?= e(date('M d, Y', strtotime($row['end_at']))) ?>"><i class="ph ph-package" aria-hidden="true"></i><span class="btn-text">Hand Over</span></button>
</article>
