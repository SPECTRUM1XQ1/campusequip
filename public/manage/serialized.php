<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../src/Equipment.php';
requireRole(['admin', 'staff']);
requirePermission('can_manage_catalog');

$pageTitle = 'Serialize Item';
$pageStyles = ['pages/manage-serialized'];
$equipmentModel = new Equipment();
// ?catalog_id=N comes from the "View Serialized Units" button on the catalog page.
$filterCatalogId = isset($_GET['catalog_id']) ? (int) $_GET['catalog_id'] : 0;
$serialItems = $equipmentModel->listAllSerialized($filterCatalogId > 0 ? $filterCatalogId : null);
$catalogList = $equipmentModel->listCatalog();                  // active models: the Register modal
$allModels   = $equipmentModel->listCatalog(null, null, true);  // incl. archived: the filter dropdown

$statusClasses = [
    'available'   => 'approved',
    'checked_out' => 'pending',
    'maintenance' => 'declined',
    'retired'     => 'declined'
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
                <div class="requests-toolbar">
                    <div class="filter-group">
                        <label class="filter-label" for="model-filter">Model:</label>
                        <select class="input-field manage-serialized-s1" id="model-filter"
                                data-filter-catalog>
                            <option value="">All models</option>
                            <?php foreach ($allModels as $m): ?>
                                <option value="<?= (int)$m['catalog_id'] ?>" <?= (int)$m['catalog_id'] === $filterCatalogId ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m['model_name']) ?><?= (int)$m['is_archived'] === 1 ? ' (archived)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="results-count">
                            Showing <strong id="visible-count"><?= count($serialItems) ?></strong> of <strong id="total-count"><?= count($serialItems) ?></strong> units
                        </p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm btn-rect" data-open-modal="addUnitModal"><i class="ph ph-plus" aria-hidden="true"></i><span class="btn-text">Register New Unit</span></button>
                </div>

                <div hidden data-list data-items="#serial-tbody tr[data-row]" data-page-size="10" data-pager="#serial-pager" data-pager-always
                     data-empty="#filter-empty-row" data-visible="#visible-count" data-total="#total-count"></div>

                <div class="table-card table-card--compact">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Serial #</th>
                                <th scope="col">Equipment Model</th>
                                <th scope="col">Storage Location</th>
                                <th scope="col">Status</th>
                                <th scope="col">Condition Notes</th>
                                <th scope="col" class="col-action">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="serial-tbody">
                            <?php if (empty($serialItems)): ?>
                                <tr>
                                    <td colspan="6" class="text-muted manage-serialized-s2">
                                        <?= $filterCatalogId > 0 ? 'No units registered for this model yet.' : 'No serialized units registered yet.' ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($serialItems as $item): ?>
                                    <tr data-row data-tags="<?= htmlspecialchars($item['status']) ?>">
                                        <td class="cell-strong"><?= htmlspecialchars($item['serial_number']) ?></td>
                                        <td>
                                            <?= htmlspecialchars($item['model_name']) ?>
                                            <?php if ((int)$item['is_archived'] === 1): ?>
                                                <span class="text-muted">(archived)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($item['storage_location'] ?: 'Lab Desk') ?></td>
                                        <td>
                                            <span class="status-badge <?= $statusClasses[$item['status']] ?? 'pending' ?>">
                                                <?= ucfirst(str_replace('_', ' ', htmlspecialchars($item['status']))) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($item['condition_notes'] ?: 'Good condition') ?></td>
                                        <td class="col-action">
                                            <?= edit_button([
                                    'item-id' => (int) $item['item_id'],
                                    'model-name' => $item['model_name'],
                                    'serial' => $item['serial_number'],
                                    'storage' => (string)($item['storage_location'] ?? ''),
                                    'notes' => (string)($item['condition_notes'] ?? ''),
                                    'status' => $item['status'],
                                    'unit-action' => 'edit',
                                ]) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <tr id="filter-empty-row" style="display:none;">
                                <td colspan="6" class="text-muted manage-serialized-s2">
                                    No units match your search.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="pager" id="serial-pager"></div>

                <!-- ADD UNIT MODAL -->
<?php include __DIR__ . '/../../includes/modal/manage/serialized-add-unit.php'; ?>

                <!-- EDIT UNIT MODAL -->
<?php include __DIR__ . '/../../includes/modal/manage/serialized-edit-unit.php'; ?>
            </section>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/navigation/footer.php'; ?>

    <script src="<?= APP_BASE ?>/public/assets/js/filters.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/filters.js') ?: 1 ?>"></script>
    <script src="<?= APP_BASE ?>/public/assets/js/pages/manage-serialized.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/pages/manage-serialized.js') ?: 1 ?>"></script>
</body>
</html>