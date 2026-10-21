<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../src/Equipment.php';
requireRole(['admin', 'staff']);
requirePermission('can_manage_catalog');

$pageTitle = 'Equipment Catalog';
$pageStyles = ['pages/manage-catalog'];
$equipmentModel = new Equipment();
$categories = $equipmentModel->listCategories();
$catalog = $equipmentModel->listCatalog(null, null, true); // includes archived; the filter pills decide what is shown
$accessoriesByModel = $equipmentModel->listAccessoriesGrouped(); // [catalog_id => [accessories]]
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
                <div class="requests-toolbar">
                    <p class="results-count">
                        Showing <strong id="visible-count"><?= min(count(array_filter($catalog, fn($c) => (int)($c['is_archived'] ?? 0) === 0)), 6) ?></strong> of <strong id="total-count"><?= count(array_filter($catalog, fn($c) => (int)($c['is_archived'] ?? 0) === 0)) ?></strong> models
                    </p>
                    <div class="filter-group" role="group" aria-label="Filter equipment"
                         data-filter-group data-items="#catalog-card-container .catalog-item-card" data-page-size="6" data-pager="#catalog-pager" data-pager-always
                         data-empty="#catalog-filter-empty" data-visible="#visible-count" data-total="#total-count" data-total-matches>
                        <span class="filter-label">Filter:</span>
                        <button type="button" class="filter-pill active" data-filter="active">All</button>
                        <button type="button" class="filter-pill" data-filter="available">Available</button>
                        <button type="button" class="filter-pill" data-filter="archived">Archived</button>
                    </div>

                    <div class="manage-catalog-s1">
                        <button type="button" class="btn btn-outline btn-sm btn-rect" data-open-modal="categoryModal"><i class="ph ph-plus" aria-hidden="true"></i><span class="btn-text">Add Category</span></button>
                        <button type="button" class="btn btn-primary btn-sm btn-rect" data-open-modal="addModal"><i class="ph ph-plus" aria-hidden="true"></i><span class="btn-text">Add New Item</span></button>
                    </div>
                </div>

                <div id="catalog-card-container" class="catalog-list">
                    <?php if (empty($catalog)): ?>
                        <div class="card-container text-muted manage-catalog-s2">
                            No equipment catalog items found. Click "+ Add New Item" to create one.
                        </div>
                    <?php else: ?>
                        <?php foreach ($catalog as $item): 
                            $avail = (int)($item['available_units'] ?? 0);
                            $total = (int)($item['total_units'] ?? 0);
                            $isArchived = (int)($item['is_archived'] ?? 0) === 1;
                        ?>
                            <article class="request-card catalog-item-card"
                                     data-available="<?= $avail > 0 ? 'yes' : 'no' ?>"
                                     data-archived="<?= $isArchived ? 'yes' : 'no' ?>"
                                     data-tags="<?= $isArchived ? 'archived' : ('active' . ($avail > 0 ? ' available' : '')) ?>">
                                <div class="ops-card-head">
                                    <div>
                                        <h3 class="section-title"><?= htmlspecialchars($item['model_name']) ?></h3>
                                        <p class="meta-sub">Category: <?= htmlspecialchars($item['category_name']) ?></p>
                                    </div>
                                    <?php if ($isArchived): ?>
                                        <span class="status-badge declined">Archived</span>
                                    <?php else: ?>
                                        <span class="status-badge <?= $avail > 0 ? 'approved' : 'declined' ?>">
                                            <?= $avail > 0 ? "$avail Available" : 'Unavailable' ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="catalog-stats">
                                    <div>
                                        <p class="meta-label">Total Stock</p>
                                        <p class="meta-value"><?= $total ?> Units</p>
                                    </div>
                                    <div>
                                        <p class="meta-label">Available</p>
                                        <p class="meta-value value-success"><?= $avail ?> Units</p>
                                    </div>
                                    <div>
                                        <p class="meta-label">Max Loan Period</p>
                                        <p class="meta-value"><?= (int)$item['max_loan_days'] ?> Days</p>
                                    </div>
                                    <div>
                                        <p class="meta-label">Late Fine Rate</p>
                                        <p class="meta-value">&#8369;<?= number_format((float)$item['late_fine_rate'], 2) ?>/day</p>
                                    </div>
                                </div>

                                <div class="catalog-actions">
                                    <div class="card-actions">
                                        <a href="serialized.php?catalog_id=<?= (int)$item['catalog_id'] ?>" class="btn btn-primary btn-sm btn-rect btn-reveal manage-catalog-s3"><i class="ph ph-barcode" aria-hidden="true"></i><span class="btn-label">View Units</span></a>
                                        <button type="button" class="btn btn-soft-teal btn-sm btn-rect btn-reveal"
                                                data-catalog-id="<?= (int)$item['catalog_id'] ?>"
                                                data-model-name="<?= htmlspecialchars($item['model_name']) ?>"
                                                data-catalog-action="accessories"><i class="ph ph-plugs" aria-hidden="true"></i><span class="btn-label">Accessories (<span class="acc-count" data-catalog-id="<?= (int)$item['catalog_id'] ?>"><?= count($accessoriesByModel[(int)$item['catalog_id']] ?? []) ?></span>)</span></button>
                                        <?= edit_button([
                                    'catalog-id' => (int) $item['catalog_id'],
                                    'model-name' => $item['model_name'],
                                    'category-id' => (int) $item['category_id'],
                                    'max-loan-days' => (int) $item['max_loan_days'],
                                    'late-fine-rate' => number_format((float)$item['late_fine_rate'], 2, '.', ''),
                                    'description' => (string)($item['description'] ?? ''),
                                    'catalog-action' => 'edit',
                                ]) ?>
                                    </div>
                                    <?php if ($isArchived): ?>
                                        <button type="button" class="btn btn-soft-green btn-sm btn-rect btn-reveal"
                                                data-catalog-id="<?= (int)$item['catalog_id'] ?>"
                                                data-model-name="<?= htmlspecialchars($item['model_name']) ?>"
                                                data-catalog-action="restore"><i class="ph ph-arrow-counter-clockwise" aria-hidden="true"></i><span class="btn-label">Restore</span></button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-outline-danger btn-sm btn-rect btn-reveal"
                                                data-catalog-id="<?= (int)$item['catalog_id'] ?>"
                                                data-model-name="<?= htmlspecialchars($item['model_name']) ?>"
                                                data-catalog-action="archive"><i class="ph ph-archive" aria-hidden="true"></i><span class="btn-label">Archive</span></button>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div id="catalog-filter-empty" class="card-container text-muted" style="display: none; text-align: center; padding: 2rem;">
                    No equipment in this view.
                </div>
                <div class="pager" id="catalog-pager"></div>

                <!-- EDIT MODEL MODAL -->
<?php include __DIR__ . '/../../includes/modal/manage/catalog-edit.php'; ?>

                <!-- ADD MODEL MODAL -->
<?php include __DIR__ . '/../../includes/modal/manage/catalog-add.php'; ?>

<?php include __DIR__ . '/../../includes/modal/manage/catalog-accessories.php'; ?>
            </section>
            <!-- Add Category Modal -->
<?php include __DIR__ . '/../../includes/modal/manage/catalog-category.php'; ?>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/navigation/footer.php'; ?>
    <script src="<?= APP_BASE ?>/public/assets/js/filters.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/filters.js') ?: 1 ?>"></script>

    <script type="application/json" id="accessories-data"><?= json_encode($accessoriesByModel, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
    <script src="<?= APP_BASE ?>/public/assets/js/pages/manage-catalog.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/pages/manage-catalog.js') ?: 1 ?>"></script>
</body>
</html>