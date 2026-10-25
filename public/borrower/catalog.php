<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../src/Equipment.php';
require_once __DIR__ . '/../../src/Wishlist.php';
requireRole(['borrower']);

$pageTitle = 'Equipment Catalog';
$pageStyles = ['shared/borrower', 'pages/borrower-catalog'];
$equipmentModel = new Equipment();
$catalog = $equipmentModel->listCatalog();
$savedIds = (new Wishlist())->savedCatalogIds((int) $_SESSION['user_id']);
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

            <div class="borrower-catalog-s1">
                <h3 class="borrower-catalog-s2">
                    Browse All Academic Equipment
                </h3>

                <div hidden data-list data-items="#catalog-grid .item-card" data-page-size="9" data-pager="#catalog-pager" data-pager-always data-pager-style="swipe"
                     data-empty="#catalog-empty"></div>

                <div id="catalog-grid" class="borrower-catalog-s3">
                    <?php if (empty($catalog)): ?>
                        <p class="text-muted">No equipment items are currently available in the catalog.</p>
                    <?php else: ?>
                        <?php foreach ($catalog as $item): 
                            $avail = (int)($item['available_units'] ?? 0);
                        ?>
                            <div class="item-card bg-white borrower-catalog-s4">
                                <div class="borrower-catalog-s5">
                                    <div class="borrower-catalog-s6">
                                        <h3 class="borrower-catalog-s7">
                                            <?= htmlspecialchars($item['model_name']) ?>
                                        </h3>
                                        <span class="catalog-stock-badge" style="background: <?= $avail > 0 ? '#dcfce7' : '#fee2e2' ?>; color: <?= $avail > 0 ? '#16a34a' : '#ef4444' ?>; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.6rem; border-radius: 9999px;">
                                            <?= $avail > 0 ? "$avail Available" : 'Out of Stock' ?>
                                        </span>
                                    </div>
                                    <p class="borrower-catalog-s8">
                                        Category: <?= htmlspecialchars($item['category_name']) ?> &bull; Max Duration: <?= (int)$item['max_loan_days'] ?> Days<?= (float)$item['late_fine_rate'] > 0 ? ' &bull; Late fine: &#8369;' . number_format((float)$item['late_fine_rate'], 2) . '/day' : '' ?>
                                    </p>
                                    <?php if (!empty($item['description'])): ?>
                                        <p class="borrower-catalog-s9">
                                            <?= htmlspecialchars($item['description']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="borrower-catalog-s10">
                                    <button type="button" class="btn btn-primary btn-rect borrower-catalog-s11"
                                            <?= $avail === 0 ? 'disabled' : '' ?>
                                            data-reserve="<?= (int)$item['catalog_id'] ?>" data-model-name="<?= e($item['model_name']) ?>" data-max-days="<?= (int)$item['max_loan_days'] ?>" data-late-fine="<?= (float)$item['late_fine_rate'] ?>"><i class="ph ph-calendar-plus" aria-hidden="true"></i><span class="btn-text"><?= $avail > 0 ? 'Reserve Now' : 'Currently Unavailable' ?></span></button>
                                    <button type="button" class="btn btn-outline btn-rect save-btn borrower-catalog-s12"
                                           
                                            data-catalog-id="<?= (int)$item['catalog_id'] ?>"
                                            data-saved="<?= in_array((int)$item['catalog_id'], $savedIds, true) ? '1' : '0' ?>">
                                        <i class="ph ph-bookmark-simple"></i> <span class="save-label">Save</span>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <p id="catalog-empty" class="text-muted" style="display: none; text-align: center; padding: 2rem;">
                    No equipment matches your search.
                </p>
                <div class="pager" id="catalog-pager"></div>
            </div>

            <!-- RESERVE MODAL -->
<?php include __DIR__ . '/../../includes/modal/borrower/catalog-reserve.php'; ?>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/navigation/footer.php'; ?>

    <script src="<?= APP_BASE ?>/public/assets/js/pages/borrower-catalog.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/pages/borrower-catalog.js') ?: 1 ?>"></script>
    <script src="<?= APP_BASE ?>/public/assets/js/filters.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/filters.js') ?: 1 ?>"></script>
</body>
</html>