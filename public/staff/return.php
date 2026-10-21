<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../src/Checkout.php';
require_once __DIR__ . '/../../src/Equipment.php';
requireRole(['admin', 'staff']);
requirePermission('can_inspect_returns');

$pageTitle = 'Process Return';
$pageStyles = ['shared/checkout'];

$checkoutModel      = new Checkout();
$activeReturns      = $checkoutModel->listActiveDetailed();
$accessoriesByModel = (new Equipment())->listAccessoriesGrouped(); // [catalog_id => [accessories]]
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

        <main class="main-content main-content--fixed">
            <?php include __DIR__ . '/../../includes/navigation/topbar.php'; ?>

            <section class="requests-page requests-page--split">
                <div class="pending-grid staff-return-s1">
                    <!-- Active Returns -->
                    <div class="ops-column">
                        <div class="section-header">
                            <h3 class="section-title">Active Returns</h3>
                            <span class="section-subtitle"><?= count($activeReturns) ?> active loans</span>
                        </div>

                        <div class="requests-toolbar">
                            <p class="results-count">
                                Showing <strong id="returns-visible"><?= count($activeReturns) ?></strong> of <strong id="returns-total"><?= count($activeReturns) ?></strong> loans
                            </p>
                            <div class="filter-group" role="group" aria-label="Filter active returns"
                                 data-filter-group data-items="#returns-container > .request-card[data-tags]"
                                 data-empty="#returns-filter-empty" data-visible="#returns-visible" data-total="#returns-total"
                                 data-page-size="6" data-pager="#returns-pager" data-pager-always data-total-matches>
                                <span class="filter-label">Filter:</span>
                                <button type="button" class="filter-pill active" data-filter="all">All</button>
                                <button type="button" class="filter-pill" data-filter="overdue">Overdue</button>
                                <button type="button" class="filter-pill" data-filter="today">Due today</button>
                                <button type="button" class="filter-pill" data-filter="ontime">On time</button>
                            </div>
                        </div>

                        <div id="returns-container" class="ops-scroll ops-cards">
                            <?php if (empty($activeReturns)): ?>
                                <div class="card-container text-muted staff-return-s2">
                                    No equipment currently checked out.
                                </div>
                            <?php else: ?>
                                <?php foreach ($activeReturns as $row): ?>
                                    <?php include __DIR__ . '/../../includes/return-card.php'; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <div id="returns-filter-empty" class="card-container text-muted" style="display: none; text-align: center; padding: 2rem;">
                                No loans match this filter.
                            </div>
                        </div>
                        <div class="pager" id="returns-pager"></div>
                    </div>
                </div>
            </section>

            <!-- PROCESS RETURN MODAL -->
<?php include __DIR__ . '/../../includes/modal/shared/return.php'; ?>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/navigation/footer.php'; ?>
    <script src="<?= APP_BASE ?>/public/assets/js/filters.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/filters.js') ?: 1 ?>"></script>

    <script type="application/json" id="accessories-data"><?= json_encode($accessoriesByModel, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
    <script src="<?= APP_BASE ?>/public/assets/js/countdown.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/countdown.js') ?: 1 ?>"></script>
    <script src="<?= APP_BASE ?>/public/assets/js/shared/return.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/shared/return.js') ?: 1 ?>"></script>
</body>
</html>