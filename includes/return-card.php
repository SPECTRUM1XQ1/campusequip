<?php
// One "Active Returns" card. Expects $row (a row from Checkout::listActiveDetailed()).
// Shared by manage/checkouts.php and staff/return.php so both always look the same.
//
// It mirrors includes/handover-card.php block for block (header, borrower, item, ONE date with a
// countdown pill, ONE button), so a Handover card and a Return card are the same size.
//
// The pill counts down to the due date and then switches to "Overdue by ..."
// (assets/js/countdown.js). The late fine is not shown here: it is calculated and charged
// automatically when the item is returned.
require_once __DIR__ . '/helpers/pickup-helpers.php';

// Tags used by the filter pills: overdue / due today / on time.
if ((int) $row['is_overdue'] === 1) {
    $returnTag = 'overdue';
} elseif (date('Y-m-d', strtotime($row['due_at'])) === date('Y-m-d')) {
    $returnTag = 'today ontime';
} else {
    $returnTag = 'ontime';
}
?>
<article class="request-card" id="return-card-<?= (int) $row['checkout_id'] ?>" data-tags="<?= $returnTag ?>">
    <div class="ops-card-head">
        <span class="cell-id">REQ ID: <?= (int) $row['reservation_id'] ?></span>
        <span class="meta-label">Issued: <?= date('M j', strtotime($row['checked_out_at'])) ?></span>
    </div>

    <div class="ops-field">
        <p class="meta-label">Borrower</p>
        <h4 class="meta-value"><?= htmlspecialchars($row['borrower_name']) ?></h4>
        <span class="ui-return-card-s1"><?= htmlspecialchars($row['borrower_id_number']) ?></span>
    </div>
    <div class="ops-field">
        <p class="meta-label">Item & Unit Serial</p>
        <p class="meta-text"><strong><?= htmlspecialchars($row['model_name']) ?></strong> (SN: <?= htmlspecialchars($row['serial_number']) ?>)</p>
    </div>
    <div class="ops-field">
        <p class="meta-label">Due Date</p>
        <p class="meta-text"><?= date('M j, Y g:i A', strtotime($row['due_at'])) ?></p>
        <?= dueCountdownTag($row['due_at']) ?>
    </div>

    <button type="button" class="btn btn-outline-success btn-block"
            data-return="<?= (int) $row['checkout_id'] ?>" data-borrower="<?= e($row['borrower_name']) ?>" data-item="<?= e($row['model_name'] . ' (SN: ' . $row['serial_number'] . ')') ?>" data-overdue="<?= (int) $row['is_overdue'] ?>" data-catalog="<?= (int) $row['catalog_id'] ?>"><i class="ph ph-arrow-u-down-left" aria-hidden="true"></i><span class="btn-text">Process Return</span></button>
</article>
