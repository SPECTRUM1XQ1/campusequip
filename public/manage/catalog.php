<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../src/Equipment.php';
requireRole(['admin', 'staff']);
requirePermission('can_manage_catalog');

$pageTitle = 'Equipment Catalog';
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
        <?php include __DIR__ . '/../../includes/sidebar-admin.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../../includes/topbar.php'; ?>

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

                    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                        <button type="button" class="btn btn-outline btn-sm btn-rect" onclick="openModal('categoryModal')"><i class="ph ph-plus" aria-hidden="true"></i><span class="btn-text">Add Category</span></button>
                        <button type="button" class="btn btn-primary btn-sm btn-rect" onclick="openModal('addModal')"><i class="ph ph-plus" aria-hidden="true"></i><span class="btn-text">Add New Item</span></button>
                    </div>
                </div>

                <div id="catalog-card-container" class="catalog-list">
                    <?php if (empty($catalog)): ?>
                        <div class="card-container text-muted" style="text-align: center; padding: 3rem;">
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
                                        <a href="serialized.php?catalog_id=<?= (int)$item['catalog_id'] ?>" class="btn btn-primary btn-sm btn-rect btn-reveal" style="text-decoration: none;"><i class="ph ph-barcode" aria-hidden="true"></i><span class="btn-label">View Units</span></a>
                                        <button type="button" class="btn btn-soft-teal btn-sm btn-rect btn-reveal"
                                                data-catalog-id="<?= (int)$item['catalog_id'] ?>"
                                                data-model-name="<?= htmlspecialchars($item['model_name']) ?>"
                                                onclick="openAccessoriesModal(this)"><i class="ph ph-plugs" aria-hidden="true"></i><span class="btn-label">Accessories (<span class="acc-count" data-catalog-id="<?= (int)$item['catalog_id'] ?>"><?= count($accessoriesByModel[(int)$item['catalog_id']] ?? []) ?></span>)</span></button>
                                        <button type="button" class="btn btn-outline btn-sm btn-rect btn-reveal"
                                                data-catalog-id="<?= (int)$item['catalog_id'] ?>"
                                                data-model-name="<?= htmlspecialchars($item['model_name']) ?>"
                                                data-category-id="<?= (int)$item['category_id'] ?>"
                                                data-max-loan-days="<?= (int)$item['max_loan_days'] ?>"
                                                data-late-fine-rate="<?= htmlspecialchars(number_format((float)$item['late_fine_rate'], 2, '.', '')) ?>"
                                                data-description="<?= htmlspecialchars((string)($item['description'] ?? '')) ?>"
                                                onclick="openEditModal(this)"><i class="ph ph-pencil-simple" aria-hidden="true"></i><span class="btn-label">Edit</span></button>
                                    </div>
                                    <?php if ($isArchived): ?>
                                        <button type="button" class="btn btn-soft-green btn-sm btn-rect btn-reveal"
                                                data-catalog-id="<?= (int)$item['catalog_id'] ?>"
                                                data-model-name="<?= htmlspecialchars($item['model_name']) ?>"
                                                onclick="setArchived(this, false)"><i class="ph ph-arrow-counter-clockwise" aria-hidden="true"></i><span class="btn-label">Restore</span></button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-outline-danger btn-sm btn-rect btn-reveal"
                                                data-catalog-id="<?= (int)$item['catalog_id'] ?>"
                                                data-model-name="<?= htmlspecialchars($item['model_name']) ?>"
                                                onclick="setArchived(this, true)"><i class="ph ph-archive" aria-hidden="true"></i><span class="btn-label">Archive</span></button>
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
                <div class="modal-overlay" id="editModal">
                    <div class="modal">
                        <div class="modal-header">
                            <div>
                                <h2 class="modal-title">Edit Equipment Model</h2>
                                <p class="modal-subtitle">Changes to loan rules apply to future reservations only.</p>
                            </div>
                            <button type="button" class="modal-close" onclick="closeModal('editModal')">&times;</button>
                        </div>
                        <form id="edit-model-form">
                            <input type="hidden" name="catalog_id" id="edit_catalog_id">
                            <div class="modal-body">
                                <div class="modal-field">
                                    <label class="modal-label" for="edit_model_name">Model Name *</label>
                                    <input type="text" class="input-field" name="model_name" id="edit_model_name" maxlength="150" required>
                                </div>
                                <div class="grid-row grid-cols-2">
                                    <div class="modal-field">
                                        <label class="modal-label" for="edit_category_id">Category *</label>
                                        <select class="input-field" name="category_id" id="edit_category_id" required>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= (int)$cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="modal-field">
                                        <label class="modal-label" for="edit_max_loan_days">Max Loan Days *</label>
                                        <input type="number" class="input-field" name="max_loan_days" id="edit_max_loan_days" min="1" required>
                                    </div>
                                </div>
                                <div class="modal-field">
                                    <label class="modal-label" for="edit_late_fine_rate">Daily Late Fine Rate (&#8369;) *</label>
                                    <input type="number" step="0.50" class="input-field" name="late_fine_rate" id="edit_late_fine_rate" min="0" required>
                                </div>
                                <div class="modal-field">
                                    <label class="modal-label" for="edit_description">Description</label>
                                    <textarea class="input-field" name="description" id="edit_description"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline btn-rect" onclick="closeModal('editModal')"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                                <button type="submit" class="btn btn-primary btn-rect" id="edit-model-submit-btn"><i class="ph ph-floppy-disk" aria-hidden="true"></i><span class="btn-text">Save Changes</span></button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ADD MODEL MODAL -->
                <div class="modal-overlay" id="addModal">
                    <div class="modal">
                        <div class="modal-header">
                            <div>
                                <h2 class="modal-title">Add Equipment Model</h2>
                                <p class="modal-subtitle">Define catalog model information and loan rules.</p>
                            </div>
                            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
                        </div>
                        <form id="add-model-form">
                            <div class="modal-body">
                                <div class="modal-field">
                                    <label class="modal-label">Model Name *</label>
                                    <input type="text" class="input-field" name="model_name" placeholder="e.g. Sony Alpha A7 IV" required>
                                </div>
                                <div class="grid-row grid-cols-2">
                                    <div class="modal-field">
                                        <label class="modal-label">Category *</label>
                                        <select class="input-field" name="category_id" required>
                                            <option value="" disabled selected>Select category...</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= (int)$cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="modal-field">
                                        <label class="modal-label">Max Loan Days *</label>
                                        <input type="number" class="input-field" name="max_loan_days" value="3" min="1" required>
                                    </div>
                                </div>
                                <div class="modal-field">
                                    <label class="modal-label">Daily Late Fine Rate (&#8369;) *</label>
                                    <input type="number" step="0.50" class="input-field" name="late_fine_rate" value="5.00" min="0" required>
                                </div>
                                <div class="modal-field">
                                    <label class="modal-label">Description</label>
                                    <textarea class="input-field" name="description" placeholder="Technical specifications, included accessories..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline btn-rect" onclick="closeModal('addModal')"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                                <button type="submit" class="btn btn-primary btn-rect" id="add-model-submit-btn"><i class="ph ph-floppy-disk" aria-hidden="true"></i><span class="btn-text">Save Model</span></button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="modal-overlay" id="accessoriesModal">
                    <div class="modal">
                        <div class="modal-header">
                            <div>
                                <h3 class="modal-title">Accessories</h3>
                                <p class="modal-subtitle" id="acc-model-name"></p>
                            </div>
                            <button type="button" class="modal-close" onclick="closeModal('accessoriesModal')">&times;</button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted" style="margin-bottom: 0.75rem;">Items that come with this model. Staff tick the missing ones when it is returned.</p>
                            <div id="acc-list"></div>

                            <form id="add-accessory-form" style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                                <input type="text" class="input-field" id="acc-new-name" maxlength="100" placeholder="e.g. Power adapter" required>
                                <button type="submit" class="btn btn-primary btn-sm btn-rect" id="acc-add-btn"><i class="ph ph-plus" aria-hidden="true"></i><span class="btn-text">Add</span></button>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline btn-rect" onclick="closeModal('accessoriesModal')"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Close</span></button>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Add Category Modal -->
            <div class="modal-overlay" id="categoryModal">
                <div class="modal" style="max-width: 28rem;">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title">Add Category</h2>
                            <p class="modal-subtitle">New categories appear when adding or editing equipment.</p>
                        </div>
                        <button type="button" class="modal-close" aria-label="Close" onclick="closeModal('categoryModal')">
                            <i class="ph ph-x" style="font-size: 1.25rem;"></i>
                        </button>
                    </div>
                    <form id="category-form">
                        <div class="modal-body">
                            <div class="modal-field">
                                <label class="modal-label" for="category_name">Category name *</label>
                                <input type="text" class="input-field" id="category_name" name="category_name" maxlength="50" placeholder="e.g. Lab Instruments" required>
                            </div>
                            <p class="modal-label" style="margin-bottom: 0.5rem;">Existing categories</p>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                <?php foreach ($categories as $cat): ?>
                                    <span class="pill pill--neutral"><?= htmlspecialchars($cat['category_name']) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline btn-rect" onclick="closeModal('categoryModal')"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                            <button type="submit" class="btn btn-primary btn-rect" id="category-submit-btn"><i class="ph ph-plus" aria-hidden="true"></i><span class="btn-text">Add Category</span></button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>
    <script src="<?= APP_BASE ?>/public/assets/js/filters.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/filters.js') ?: 1 ?>"></script>

    <script>
        // ---- Accessories manager ----
        // { catalog_id: [{accessory_id, accessory_name}, ...] } kept in memory and updated after each API call
        const ACCESSORIES = <?= json_encode($accessoriesByModel, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        let accCatalogId = null;

        function openAccessoriesModal(btn) {
            accCatalogId = parseInt(btn.dataset.catalogId, 10);
            document.getElementById('acc-model-name').textContent = btn.dataset.modelName;
            document.getElementById('acc-new-name').value = '';
            renderAccessoryList();
            openModal('accessoriesModal');
        }

        function renderAccessoryList() {
            const list = document.getElementById('acc-list');
            const items = ACCESSORIES[accCatalogId] || [];
            list.replaceChildren();

            if (items.length === 0) {
                const p = document.createElement('p');
                p.className = 'text-muted';
                p.textContent = 'No accessories listed yet.';
                list.appendChild(p);
            }

            items.forEach(a => {
                const row = document.createElement('div');
                row.className = 'option-row';
                row.style.cursor = 'default';

                const name = document.createElement('span');
                name.textContent = a.accessory_name; // textContent: no HTML injection
                row.appendChild(name);

                const del = document.createElement('button');
                del.type = 'button';
                del.className = 'btn btn-outline btn-sm btn-rect';
                del.style.marginLeft = 'auto';
                del.textContent = 'Remove';
                del.addEventListener('click', () => removeAccessory(a.accessory_id));
                row.appendChild(del);

                list.appendChild(row);
            });

            document.querySelectorAll('.acc-count[data-catalog-id="' + accCatalogId + '"]')
                .forEach(el => el.textContent = items.length);
        }

        document.getElementById('add-accessory-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const input = document.getElementById('acc-new-name');
            const btn = document.getElementById('acc-add-btn');
            const name = input.value.trim();
            if (name === '') return;

            btn.disabled = true;
            const formData = new FormData();
            formData.append('catalog_id', accCatalogId);
            formData.append('accessory_name', name);
            try {
                const res = await fetch(APP_BASE + '/api/catalog/add_accessory.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    const list = ACCESSORIES[accCatalogId] = ACCESSORIES[accCatalogId] || [];
                    list.push({ accessory_id: data.accessory_id, accessory_name: name });
                    list.sort((a, b) => a.accessory_name.localeCompare(b.accessory_name));
                    input.value = '';
                    renderAccessoryList();
                    input.focus();
                } else {
                    showToast(data.message || 'Could not add accessory', 'error');
                }
            } catch (err) {
                showToast('Server error while adding accessory', 'error');
            } finally {
                btn.disabled = false;
            }
        });

        async function removeAccessory(accessoryId) {
            const formData = new FormData();
            formData.append('accessory_id', accessoryId);
            try {
                const res = await fetch(APP_BASE + '/api/catalog/delete_accessory.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    ACCESSORIES[accCatalogId] = (ACCESSORIES[accCatalogId] || []).filter(a => a.accessory_id !== accessoryId);
                    renderAccessoryList();
                } else {
                    showToast(data.message || 'Could not remove accessory', 'error');
                }
            } catch (err) {
                showToast('Server error while removing accessory', 'error');
            }
        }

        function openEditModal(btn) {
            const d = btn.dataset;
            document.getElementById('edit_catalog_id').value    = d.catalogId;
            document.getElementById('edit_model_name').value    = d.modelName;
            document.getElementById('edit_category_id').value   = d.categoryId;
            document.getElementById('edit_max_loan_days').value = d.maxLoanDays;
            document.getElementById('edit_late_fine_rate').value = d.lateFineRate;
            document.getElementById('edit_description').value   = d.description;
            openModal('editModal');
        }

        document.getElementById('edit-model-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('edit-model-submit-btn');
            btn.disabled = true;
            setBtnText(btn, 'Saving...');
            try {
                const res = await fetch(APP_BASE + '/api/catalog/update_model.php', { method: 'POST', body: new FormData(this) });
                const data = await res.json();
                if (data.success) {
                    showToast('Equipment model updated!', 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast(data.message || 'Failed to update model', 'error');
                }
            } catch (err) {
                showToast('Server communication error', 'error');
            } finally {
                btn.disabled = false;
                setBtnText(btn, 'Save Changes');
            }
        });

        // ---- Archive / restore ----
        async function setArchived(btn, archive) {
            const name = btn.dataset.modelName;
            const question = archive
                ? 'Archive "' + name + '"?\n\nIt will be hidden from borrowers and cannot be reserved. You can restore it later.'
                : 'Restore "' + name + '" so borrowers can reserve it again?';
            if (!confirm(question)) return;

            const formData = new FormData();
            formData.append('catalog_id', btn.dataset.catalogId);
            formData.append('archived', archive ? '1' : '0');
            btn.disabled = true;
            try {
                const res = await fetch(APP_BASE + '/api/catalog/archive_model.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    showToast(archive ? 'Model archived.' : 'Model restored.', 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast(data.message || 'Could not change the archive status', 'error');
                    btn.disabled = false;
                }
            } catch (err) {
                showToast('Server communication error', 'error');
                btn.disabled = false;
            }
        }

        // AJAX Add Model
        document.getElementById('add-model-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('add-model-submit-btn');
            btn.disabled = true;
            setBtnText(btn, 'Saving...');

            const formData = new FormData(this);
            try {
                const res = await fetch(APP_BASE + '/api/catalog/add_model.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Equipment model created successfully!', 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast(data.message || 'Failed to save model', 'error');
                }
            } catch (err) {
                showToast('Server communication error', 'error');
            } finally {
                btn.disabled = false;
                setBtnText(btn, 'Save Model');
            }
        });

        // Add a new category, then reload so the dropdowns include it.
        document.getElementById('category-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('category-submit-btn');
            btn.disabled = true;
            try {
                const res = await fetch(APP_BASE + '/api/catalog/add_category.php', { method: 'POST', body: new FormData(this) });
                const data = await res.json();
                if (data.success) {
                    showToast('Category added.', 'success');
                    closeModal('categoryModal');
                    setTimeout(() => location.reload(), 700);
                } else {
                    showToast(data.message || 'Could not add the category.', 'error');
                }
            } catch (err) {
                showToast('Server communication error', 'error');
            }
            btn.disabled = false;
        });
    </script>
</body>
</html>