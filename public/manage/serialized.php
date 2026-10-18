<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../src/Equipment.php';
requireRole(['admin', 'staff']);
requirePermission('can_manage_catalog');

$pageTitle = 'Serialize Item';
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
        <?php include __DIR__ . '/../../includes/sidebar-admin.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../../includes/topbar.php'; ?>

            <section class="requests-page">
                <div class="requests-toolbar">
                    <div class="filter-group">
                        <label class="filter-label" for="model-filter">Model:</label>
                        <select class="input-field" id="model-filter" style="width: auto;"
                                onchange="location.href = 'serialized.php' + (this.value ? '?catalog_id=' + encodeURIComponent(this.value) : '')">
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
                    <button type="button" class="btn btn-primary btn-sm btn-rect" onclick="openModal('addUnitModal')"><i class="ph ph-plus" aria-hidden="true"></i><span class="btn-text">Register New Unit</span></button>
                </div>

                <div hidden data-list data-items="#serial-tbody tr[data-row]" data-page-size="10" data-pager="#serial-pager"
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
                                    <td colspan="6" style="text-align: center; padding: 2rem;" class="text-muted">
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
                                            <button type="button" class="btn btn-outline btn-sm btn-rect btn-reveal"
                                                    data-item-id="<?= (int)$item['item_id'] ?>"
                                                    data-model-name="<?= htmlspecialchars($item['model_name']) ?>"
                                                    data-serial="<?= htmlspecialchars($item['serial_number']) ?>"
                                                    data-storage="<?= htmlspecialchars((string)($item['storage_location'] ?? '')) ?>"
                                                    data-notes="<?= htmlspecialchars((string)($item['condition_notes'] ?? '')) ?>"
                                                    data-status="<?= htmlspecialchars($item['status']) ?>"
                                                    onclick="openEditUnitModal(this)"><i class="ph ph-pencil-simple" aria-hidden="true"></i><span class="btn-label">Edit</span></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <tr id="filter-empty-row" style="display:none;">
                                <td colspan="6" style="text-align: center; padding: 2rem;" class="text-muted">
                                    No units match your search.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="pager" id="serial-pager"></div>

                <!-- ADD UNIT MODAL -->
                <div class="modal-overlay" id="addUnitModal">
                    <div class="modal" style="max-width: 28rem;">
                        <div class="modal-header">
                            <div>
                                <h2 class="modal-title">Register Physical Unit</h2>
                                <p class="modal-subtitle">Add a barcode/serial tracked hardware item.</p>
                            </div>
                            <button class="modal-close" onclick="closeModal('addUnitModal')">&times;</button>
                        </div>
                        <form id="add-unit-form">
                            <div class="modal-body">
                                <div class="modal-field">
                                    <label class="modal-label">Equipment Model *</label>
                                    <select class="input-field" name="catalog_id" required>
                                        <option value="" disabled <?= $filterCatalogId > 0 ? '' : 'selected' ?>>Select equipment model...</option>
                                        <?php foreach ($catalogList as $cat): ?>
                                            <option value="<?= (int)$cat['catalog_id'] ?>" <?= (int)$cat['catalog_id'] === $filterCatalogId ? 'selected' : '' ?>><?= htmlspecialchars($cat['model_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="modal-field">
                                    <label class="modal-label">Serial Number (Unique Barcode) *</label>
                                    <input type="text" class="input-field" name="serial_number" placeholder="e.g. SN-CAM-10204" required>
                                </div>
                                <div class="modal-field">
                                    <label class="modal-label">Storage Location</label>
                                    <input type="text" class="input-field" name="storage_location" placeholder="e.g. Lab Cabinet 3, Shelf B">
                                </div>
                                <div class="modal-field">
                                    <label class="modal-label">Initial Condition Notes</label>
                                    <textarea class="input-field" name="condition_notes" placeholder="Brand new, includes lens cap & battery..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline btn-rect" onclick="closeModal('addUnitModal')"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                                <button type="submit" class="btn btn-primary btn-rect" id="add-unit-submit-btn"><i class="ph ph-barcode" aria-hidden="true"></i><span class="btn-text">Register Unit</span></button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- EDIT UNIT MODAL -->
                <div class="modal-overlay" id="editUnitModal">
                    <div class="modal" style="max-width: 28rem;">
                        <div class="modal-header">
                            <div>
                                <h2 class="modal-title">Edit Unit</h2>
                                <p class="modal-subtitle" id="eu-model-name"></p>
                            </div>
                            <button type="button" class="modal-close" onclick="closeModal('editUnitModal')">&times;</button>
                        </div>
                        <form id="edit-unit-form">
                            <input type="hidden" name="item_id" id="eu_item_id">
                            <div class="modal-body">
                                <p class="text-muted" id="eu-lock-note" style="display: none; margin-bottom: 0.75rem;"></p>
                                <div class="modal-field">
                                    <label class="modal-label" for="eu_serial">Serial Number (Unique Barcode) *</label>
                                    <input type="text" class="input-field" name="serial_number" id="eu_serial" maxlength="100" required>
                                </div>
                                <div class="modal-field">
                                    <label class="modal-label" for="eu_storage">Storage Location</label>
                                    <input type="text" class="input-field" name="storage_location" id="eu_storage" maxlength="100" placeholder="e.g. Lab Cabinet 3, Shelf B">
                                </div>
                                <div class="modal-field">
                                    <label class="modal-label" for="eu_status">Status</label>
                                    <select class="input-field" name="status" id="eu_status"></select>
                                </div>
                                <div class="modal-field">
                                    <label class="modal-label" for="eu_notes">Condition Notes</label>
                                    <textarea class="input-field" name="condition_notes" id="eu_notes"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline btn-rect" onclick="closeModal('editUnitModal')"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                                <button type="submit" class="btn btn-primary btn-rect" id="edit-unit-submit-btn"><i class="ph ph-floppy-disk" aria-hidden="true"></i><span class="btn-text">Save Changes</span></button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>

    <script src="<?= APP_BASE ?>/public/assets/js/filters.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/filters.js') ?: 1 ?>"></script>
    <script>
        // ---- Edit unit ----
        const EDITABLE_STATUSES = ['available', 'maintenance', 'retired'];
        const statusLabel = s => s.charAt(0).toUpperCase() + s.slice(1).replace('_', ' ');

        function openEditUnitModal(btn) {
            const d = btn.dataset;
            document.getElementById('eu_item_id').value = d.itemId;
            document.getElementById('eu-model-name').textContent = d.modelName;
            document.getElementById('eu_serial').value  = d.serial;
            document.getElementById('eu_storage').value = d.storage;
            document.getElementById('eu_notes').value   = d.notes;

            // Out on loan: serial + status are locked (storage/notes can still be corrected).
            const onLoan = (d.status === 'checked_out');
            const statusLocked = !EDITABLE_STATUSES.includes(d.status);
            document.getElementById('eu_serial').readOnly = onLoan;

            const sel = document.getElementById('eu_status');
            sel.replaceChildren();
            (statusLocked ? [d.status] : EDITABLE_STATUSES).forEach(s => {
                const opt = document.createElement('option');
                opt.value = s;
                opt.textContent = statusLabel(s);
                opt.selected = (s === d.status);
                sel.appendChild(opt);
            });
            sel.disabled = statusLocked; // a disabled field is not submitted, so the server keeps the status

            const note = document.getElementById('eu-lock-note');
            note.style.display = onLoan ? 'block' : 'none';
            note.textContent = onLoan ? 'This unit is checked out. Serial number and status are locked until it is returned.' : '';

            openModal('editUnitModal');
        }

        document.getElementById('edit-unit-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('edit-unit-submit-btn');
            btn.disabled = true;
            setBtnText(btn, 'Saving...');
            try {
                const res = await fetch(APP_BASE + '/api/catalog/edit_unit.php', { method: 'POST', body: new FormData(this) });
                const data = await res.json();
                if (data.success) {
                    showToast('Unit updated!', 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast(data.message || 'Failed to update unit', 'error');
                }
            } catch (err) {
                showToast('Server communication error', 'error');
            } finally {
                btn.disabled = false;
                setBtnText(btn, 'Save Changes');
            }
        });

        document.getElementById('add-unit-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('add-unit-submit-btn');
            btn.disabled = true;
            setBtnText(btn, 'Saving...');

            const formData = new FormData(this);
            try {
                const res = await fetch(APP_BASE + '/api/catalog/add_unit.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Unit successfully registered!', 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast(data.message || 'Failed to register unit', 'error');
                }
            } catch (err) {
                showToast('Server communication error', 'error');
            } finally {
                btn.disabled = false;
                setBtnText(btn, 'Register Unit');
            }
        });
    </script>
</body>
</html>