<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../src/Fine.php';
requireRole(['borrower']);

$pageTitle = 'Penalties & Fines';
$pageStyles = ['shared/borrower', 'pages/borrower-fines'];

$userId   = (int) $_SESSION['user_id'];
$fineModel = new Fine();
$accruing = $fineModel->listAccruingForBorrower($userId); // overdue, still out: fine is growing
$fines    = $fineModel->listForBorrower($userId);         // fines already charged

// Totals for the summary strip.
$accruingTotal = 0.0;
foreach ($accruing as $a) {
    $accruingTotal += (float) $a['late_fine_rate'] * (int) $a['days_late'];
}
$unpaidTotal = 0.0; $unpaidCount = 0;
$paidTotal   = 0.0; $paidCount   = 0;
foreach ($fines as $f) {
    if ($f['payment_status'] === 'unpaid') {
        $unpaidTotal += (float) $f['amount_due'];
        $unpaidCount++;
    } elseif ($f['payment_status'] === 'paid') {
        $paidTotal += (float) $f['amount_due'];
        $paidCount++;
    }
}
$owedTotal = $accruingTotal + $unpaidTotal;

function peso(float $n): string
{
    return '&#8369;' . number_format($n, 2);
}

function fmtDateTime(?string $value): string
{
    return $value ? date('M d, Y g:i A', strtotime($value)) : '—';
}

function pill(string $text, string $variant): string
{
    return '<span class="pill pill--' . $variant . '">' . htmlspecialchars($text) . '</span>';
}

/** $valueHtml is printed AS HTML, so callers must escape any user text first (see htmlspecialchars() at each call). */
function detailLine(string $label, string $valueHtml): string
{
    return '<div><span class="label">' . htmlspecialchars($label) . ':</span> ' . $valueHtml . '</div>';
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

            <section class="requests-page">
                <p class="page-note">A late fine is the item's daily rate for every day (or part of a day) it is past its due date. It keeps growing until you return the item, and the final amount is charged when you return it at the Lab Desk. Staff may also charge a fine for damage or missing accessories.</p>

                <!-- Summary -->
                <div class="fine-summary fine-summary--accent">
                    <div class="fine-stat <?= $owedTotal > 0 ? 'fine-stat--danger' : 'fine-stat--success' ?>">
                        <span class="meta-label">You owe right now</span>
                        <strong class="fine-stat-value <?= $owedTotal > 0 ? 'fine-stat-danger' : 'fine-stat-success' ?>"><?= peso($owedTotal) ?></strong>
                        <span class="text-muted">growing + unpaid</span>
                    </div>
                    <div class="fine-stat <?= $accruingTotal > 0 ? 'fine-stat--danger' : '' ?>">
                        <span class="meta-label">Growing (overdue items)</span>
                        <strong class="fine-stat-value <?= $accruingTotal > 0 ? 'fine-stat-danger' : '' ?>"><?= peso($accruingTotal) ?></strong>
                        <span class="text-muted"><?= count($accruing) ?> item<?= count($accruing) === 1 ? '' : 's' ?> not yet returned</span>
                    </div>
                    <div class="fine-stat <?= $unpaidTotal > 0 ? 'fine-stat--danger' : '' ?>">
                        <span class="meta-label">Charged &amp; unpaid</span>
                        <strong class="fine-stat-value <?= $unpaidTotal > 0 ? 'fine-stat-danger' : '' ?>"><?= peso($unpaidTotal) ?></strong>
                        <span class="text-muted"><?= $unpaidCount ?> fine<?= $unpaidCount === 1 ? '' : 's' ?></span>
                    </div>
                    <div class="fine-stat fine-stat--success">
                        <span class="meta-label">Paid so far</span>
                        <strong class="fine-stat-value fine-stat-success"><?= peso($paidTotal) ?></strong>
                        <span class="text-muted"><?= $paidCount ?> fine<?= $paidCount === 1 ? '' : 's' ?></span>
                    </div>
                </div>

                <!-- Overdue loans still out: the fine is growing -->
                <h4 class="section-eyebrow">Overdue &mdash; Fines Still Growing</h4>
                <?php if (empty($accruing)): ?>
                    <?= emptyState('ph-check-circle', 'No overdue items. Nothing is growing right now.') ?>
                <?php endif; ?>

                <div hidden data-list data-items="#accruing-list .loan-card" data-page-size="6" data-pager="#accruing-pager"></div>
                <div id="accruing-list" class="loan-grid">
                <?php foreach ($accruing as $a):
                    $rate = (float) $a['late_fine_rate'];
                    $days = (int) $a['days_late'];
                ?>
                    <div class="item-card loan-card">
                        <div class="loan-card-head">
                            <h3 class="loan-card-title"><?= htmlspecialchars($a['model_name']) ?></h3>
                            <?= pill('Overdue', 'danger') ?>
                        </div>
                        <div class="loan-card-body">
                            <?= detailLine('Serial Number', htmlspecialchars($a['serial_number'] ?: '—')) ?>
                            <?= detailLine('Request Code', 'REQ-' . (int) $a['reservation_id']) ?>
                            <?= detailLine('Was Due', fmtDateTime($a['due_at'])) ?>
                            <?= detailLine('Days Late', $days . ' day' . ($days === 1 ? '' : 's')) ?>
                            <?php if ($rate > 0): ?>
                                <?= detailLine('Fine So Far', '<strong>' . peso($rate * $days) . '</strong> (' . $days . ' &times; ' . peso($rate) . '/day)') ?>
                            <?php else: ?>
                                <?= detailLine('Fine So Far', 'No late fine applies to this item') ?>
                            <?php endif; ?>
                        </div>
                        <div class="notice notice--danger">
                            <i class="ph ph-info"></i>
                            <span>
                                <?= $rate > 0
                                    ? 'This goes up by ' . peso($rate) . ' every day. Return it to the Lab Desk to stop it growing; the final amount is charged when you return it.'
                                    : 'Please return it to the Lab Desk as soon as possible.' ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
                <div class="pager" id="accruing-pager"></div>

                <!-- Fines already charged -->
                <h4 class="section-eyebrow borrower-fines-s1">Charged Fines</h4>
                <?php if (empty($fines)): ?>
                    <?= emptyState('ph-receipt', 'You have no charged fines.') ?>
                <?php endif; ?>

                <div hidden data-list data-items="#charged-list .loan-card" data-page-size="6" data-pager="#charged-pager"></div>
                <div id="charged-list" class="loan-grid">
                <?php foreach ($fines as $f):
                    $statusPill = $f['payment_status'] === 'unpaid' ? pill('Unpaid', 'danger')
                        : ($f['payment_status'] === 'paid' ? pill('Paid', 'success') : pill('Waived', 'neutral'));
                ?>
                    <div class="item-card loan-card">
                        <div class="loan-card-head">
                            <h3 class="loan-card-title"><?= htmlspecialchars($f['model_name']) ?></h3>
                            <?= $statusPill ?>
                        </div>
                        <div class="loan-card-body">
                            <?= detailLine('Amount', '<strong>' . peso((float) $f['amount_due']) . '</strong>') ?>
                            <?= detailLine('Reason', htmlspecialchars($f['reason'] ?: '—')) ?>
                            <?= detailLine('Serial Number', htmlspecialchars($f['serial_number'] ?: '—')) ?>
                            <?= detailLine('Request Code', 'REQ-' . (int) $f['reservation_id']) ?>
                            <?= detailLine('Was Due', fmtDateTime($f['due_at'])) ?>
                            <?= detailLine('Returned', fmtDateTime($f['returned_at'])) ?>
                            <?= detailLine('Charged On', fmtDateTime($f['charged_at'])) ?>
                            <?php if ($f['payment_status'] === 'paid'): ?>
                                <?= detailLine('Paid On', fmtDateTime($f['paid_at'])) ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($f['payment_status'] === 'unpaid'): ?>
                            <div class="notice notice--info">
                                <i class="ph ph-info"></i>
                                <span>Please settle this fine at the Lab Desk.</span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div>
                <div class="pager" id="charged-pager"></div>
            </section>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/navigation/footer.php'; ?>
    <script src="<?= APP_BASE ?>/public/assets/js/filters.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/filters.js') ?: 1 ?>"></script>
</body>

</html>
