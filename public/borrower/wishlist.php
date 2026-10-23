<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../src/Wishlist.php';
requireRole(['borrower']);

$pageTitle = 'Saved Items';
$pageStyles = ['shared/borrower', 'pages/borrower-wishlist'];

$saved = (new Wishlist())->listForUser((int) $_SESSION['user_id']);

// Left column: can't be reserved right now. Right column: has units available.
$unavailable = [];
$available   = [];
foreach ($saved as $item) {
    if ((int) $item['is_archived'] === 0 && (int) $item['available_units'] > 0) {
        $available[] = $item;
    } else {
        $unavailable[] = $item;
    }
}

// Why is it unavailable? (badge text)
function unavailableReason(array $item): string
{
    if ((int) $item['is_archived'] === 1) {
        return 'No longer offered';
    }
    if ((int) $item['total_units'] === 0) {
        return 'No units';
    }
    if ((int) $item['maintenance_units'] === (int) $item['total_units']) {
        return 'Maintenance';
    }
    return 'All borrowed';
}

// One saved-item card. $isAvailable decides the badge and the Reserve button.
function savedCard(array $item, bool $isAvailable): void
{
    $days = (int) $item['max_loan_days'];
    $id   = (int) $item['catalog_id'];
    ?>
    <div class="item-card saved-card" data-catalog-id="<?= $id ?>">
        <label class="select-box">
            <input type="checkbox" class="select-check" value="<?= $id ?>" aria-label="Select <?= htmlspecialchars($item['model_name'], ENT_QUOTES) ?>">
        </label>
        <div class="saved-card-info">
            <h3><?= htmlspecialchars($item['model_name']) ?></h3>
            <p>
                Max window: <?= $days ?> <?= $days === 1 ? 'Day' : 'Days' ?> &bull;<br><?= htmlspecialchars($item['category_name'] ?: 'Equipment') ?>
            </p>
        </div>
        <div class="saved-card-actions">
            <?php if ($isAvailable): ?>
                <span class="pill pill--success"><?= (int) $item['available_units'] ?> Available</span>
                <button type="button" class="btn btn-sm btn-primary btn-rect reserve-btn"
                    data-id="<?= $id ?>"
                    data-model="<?= htmlspecialchars($item['model_name'], ENT_QUOTES) ?>"
                    data-days="<?= $days ?>"><i class="ph ph-calendar-plus" aria-hidden="true"></i><span class="btn-text">Reserve</span></button>
            <?php else: ?>
                <span class="pill pill--danger"><?= htmlspecialchars(unavailableReason($item)) ?></span>
            <?php endif; ?>
            <button type="button" class="btn-trash remove-btn" aria-label="Remove <?= htmlspecialchars($item['model_name'], ENT_QUOTES) ?> from saved items"
                data-id="<?= $id ?>" data-model="<?= htmlspecialchars($item['model_name'], ENT_QUOTES) ?>">
                <i class="ph ph-trash"></i>
            </button>
        </div>
    </div>
    <?php
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

                <!-- Watchlist Header -->
                <div class="saved-header">
                    <h2>Your Personal Watchlist <small>(<?= count($saved) ?>)</small></h2>
                    <?php if (!empty($saved)): ?>
                        <div class="saved-actions">
                            <button type="button" class="btn btn-outline btn-rect" id="select-cancel-btn" hidden><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                            <button type="button" class="btn btn-outline btn-rect" id="clear-all-btn"><i class="ph ph-trash" aria-hidden="true"></i><span class="btn-text">Clear All</span></button>
                            <button type="button" class="btn btn-primary btn-rect" id="select-btn"><i class="ph ph-check-square" aria-hidden="true"></i><span class="btn-text">Select Items</span></button>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (empty($saved)): ?>
                    <div class="empty-state">
                        <i class="ph ph-bookmark-simple"></i>
                        <p>You haven't saved any equipment yet.</p>
                        <a href="<?= APP_BASE ?>/public/borrower/catalog.php" class="btn btn-primary btn-rect"><i class="ph ph-squares-four" aria-hidden="true"></i><span class="btn-text">Browse the Catalog</span></a>
                    </div>
                <?php else: ?>
                    <div class="saved-columns">

                        <div>
                            <h4 class="section-eyebrow">Currently Unavailable</h4>
                            <?php if (empty($unavailable)): ?>
                                <p class="text-muted">Everything you saved is available right now.</p>
                            <?php endif; ?>
                            <div id="saved-unavailable" data-list data-items="#saved-unavailable .saved-card" data-page-size="4" data-pager="#unavailable-pager">
                                <?php foreach ($unavailable as $item) { savedCard($item, false); } ?>
                            </div>
                            <div class="pager" id="unavailable-pager"></div>
                        </div>

                        <div>
                            <h4 class="section-eyebrow">Available Now For Dispatch</h4>
                            <?php if (empty($available)): ?>
                                <p class="text-muted">None of your saved items are available right now.</p>
                            <?php endif; ?>
                            <div id="saved-available" data-list data-items="#saved-available .saved-card" data-page-size="4" data-pager="#available-pager">
                                <?php foreach ($available as $item) { savedCard($item, true); } ?>
                            </div>
                            <div class="pager" id="available-pager"></div>
                        </div>

                    </div>
                <?php endif; ?>
            </div>

            <!-- Confirm Remove Modal -->
<?php include __DIR__ . '/../../includes/modal/borrower/wishlist-remove.php'; ?>

            <!-- Reserve Equipment Modal -->
<?php include __DIR__ . '/../../includes/modal/borrower/wishlist-reserve.php'; ?>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/navigation/footer.php'; ?>

    <script src="<?= APP_BASE ?>/public/assets/js/filters.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/filters.js') ?: 1 ?>"></script>
    <script src="<?= APP_BASE ?>/public/assets/js/pages/borrower-wishlist.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/pages/borrower-wishlist.js') ?: 1 ?>"></script>
</body>

</html>
