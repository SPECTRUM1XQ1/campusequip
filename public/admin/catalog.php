<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['admin', 'staff']);

$pageTitle = 'Equipment Catalog';
$comingSoonMessage = 'Managing the equipment catalog is next on the build list.';

// Temporary mock data for UI design — backend dev: replace with a DB query.
$equipment = [
    ['id' => 1, 'name' => 'Sony Alpha Camera 35MM', 'category' => 'Electronic', 'total' => 10, 'available' => 4, 'in_use' => 5, 'maintenance' => 1, 'max_loan' => '3 Days', 'fine_rule' => 'Standard'],
    ['id' => 2, 'name' => 'Dell XPS Laptop 15"', 'category' => 'Computer', 'total' => 30, 'available' => 10, 'in_use' => 18, 'maintenance' => 2, 'max_loan' => '7 Days', 'fine_rule' => 'Standard'],
    ['id' => 3, 'name' => 'Epson Multimedia Projector', 'category' => 'AV Equipment', 'total' => 12, 'available' => 8, 'in_use' => 4, 'maintenance' => 0, 'max_loan' => '1 Day', 'fine_rule' => 'Premium'],
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

                <!-- Toolbar: filters + add button -->
                <div class="requests-toolbar">
                    <div class="filter-group" role="group" aria-label="Filter equipment">
                        <span class="filter-label">Filter:</span>
                        <button type="button" class="filter-pill active">All</button>
                        <button type="button" class="filter-pill">Available</button>
                        <button type="button" class="filter-pill">Maintenance</button>
                    </div>

                    <button type="button" class="btn btn-primary btn-sm btn-rect">+ Add New Item</button>
                </div>

                <!-- One card per equipment type -->
                <?php foreach ($equipment as $item): ?>
                    <article class="request-card">
                        <div class="ops-card-head">
                            <div>
                                <h3 class="section-title"><?= htmlspecialchars($item['name']) ?></h3>
                                <p class="meta-sub">Category: <?= htmlspecialchars($item['category']) ?></p>
                            </div>
                            <span class="status-badge <?= $item['available'] > 0 ? 'approved' : 'declined' ?>">
                                <?= $item['available'] > 0 ? 'Available' : 'Unavailable' ?>
                            </span>
                        </div>

                        <div class="catalog-stats">
                            <div>
                                <p class="meta-label">Total Stock</p>
                                <p class="meta-value"><?= (int) $item['total'] ?> Units</p>
                            </div>
                            <div>
                                <p class="meta-label">Available</p>
                                <p class="meta-value value-success"><?= (int) $item['available'] ?> Units</p>
                            </div>
                            <div>
                                <p class="meta-label">In Use</p>
                                <p class="meta-value value-warning"><?= (int) $item['in_use'] ?> Units</p>
                            </div>
                            <div>
                                <p class="meta-label">Maintenance</p>
                                <p class="meta-value value-danger"><?= (int) $item['maintenance'] ?> Units</p>
                            </div>
                            <div>
                                <p class="meta-label">Max Loan Period</p>
                                <p class="meta-value"><?= htmlspecialchars($item['max_loan']) ?></p>
                            </div>
                            <div>
                                <p class="meta-label">Late Fine Rule</p>
                                <p class="meta-value"><?= htmlspecialchars($item['fine_rule']) ?></p>
                            </div>
                        </div>

                        <div class="catalog-actions">
                            <div class="card-actions">
                                <a href="serialized.php" class="btn btn-primary btn-sm btn-rect"
                                    style="text-decoration: none; display: inline-block; text-align: center;">View
                                    Serialized Units</a>
                                <button type="button" class="btn btn-soft btn-sm btn-rect"
                                    data-id="<?= (int) $item['id'] ?>">Edit Details</button>
                            </div>
                            <button type="button" class="btn-decline" data-id="<?= (int) $item['id'] ?>">Archive</button>
                        </div>
                    </article>
                <?php endforeach; ?>
                <!-- Archive Catalog Item Modal -->
                <div class="modal-overlay" id="archiveModal">
                    <div class="modal">
                        <div class="modal-header">
                            <div>
                                <h2 class="modal-title">Archive Catalog Item</h2>
                                <p class="modal-subtitle">Archiving hides item from borrowers while preserving
                                    transaction logs.</p>
                            </div>
                            <button class="modal-close" onclick="closeModal('archiveModal')">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="archive-warning-box">
                                <dl>
                                    <dt>Model Name</dt>
                                    <dd>Sony Alpha Camera 35MM</dd>
                                </dl>
                                <div class="alert-box">
                                    <div class="alert-icon">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path
                                                d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z">
                                            </path>
                                            <line x1="12" y1="9" x2="12" y2="13"></line>
                                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                        </svg>
                                    </div>
                                    <div class="alert-content">
                                        <h4>Active units exist</h4>
                                        <p>Archiving will hide this catalog item from borrowers while preserving all
                                            transaction logs and inventory records.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-outline btn-rect"
                                onclick="closeModal('archiveModal')">Cancel</button>
                            <button class="btn btn-danger btn-rect" onclick="closeModal('archiveModal')">Confirm
                                Archive</button>
                        </div>
                    </div>
                </div>

                <!-- Edit Catalog Item Details Modal -->
                <div class="modal-overlay" id="editModal">
                    <div class="modal">
                        <div class="modal-header">
                            <div>
                                <h2 class="modal-title">Edit Catalog Item Details</h2>
                                <p class="modal-subtitle">Modify catalog model info and fine rules.</p>
                            </div>
                            <button class="modal-close" onclick="closeModal('editModal')">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>
                        <div class="modal-body">
                            <dl class="modal-summary">
                                <dt>Model Name</dt>
                                <dd>Sony Alpha Camera 35MM</dd>
                                <dt>Category</dt>
                                <dd>Electronic</dd>
                                <dt>Total Units</dt>
                                <dd>10 Units</dd>
                            </dl>
                            <div class="modal-field">
                                <label class="modal-label">Equipment Name</label>
                                <input type="text" class="input-field" value="Sony Alpha Camera 35MM">
                            </div>
                            <div class="grid-row grid-cols-2">
                                <div class="modal-field">
                                    <label class="modal-label">Category</label>
                                    <select class="input-field">
                                        <option>Electronic</option>
                                        <option>Computer</option>
                                        <option>AV Equipment</option>
                                    </select>
                                </div>
                                <div class="modal-field">
                                    <label class="modal-label">Max Loan Period</label>
                                    <input type="text" class="input-field" value="3 Days">
                                </div>
                            </div>
                            <div class="modal-field">
                                <label class="modal-label">Late Fine Rule</label>
                                <select class="input-field">
                                    <option>Standard daily late fine</option>
                                    <option>Premium</option>
                                </select>
                            </div>
                            <div class="modal-field">
                                <label class="modal-label">Description</label>
                                <textarea class="input-field"
                                    placeholder="Add a clear catalog description for borrowers..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-outline btn-rect" onclick="closeModal('editModal')">Cancel</button>
                            <button class="btn btn-primary btn-rect" onclick="closeModal('editModal')">Save
                                Changes</button>
                        </div>
                    </div>
                </div>

                <!-- Add New Equipment Catalog Model Modal -->
                <div class="modal-overlay" id="addModal">
                    <div class="modal">
                        <div class="modal-header">
                            <div>
                                <h2 class="modal-title">Add New Equipment Catalog Model</h2>
                                <p class="modal-subtitle">Create a new equipment entry, set loan limits, and define fine
                                    policies.</p>
                            </div>
                            <button class="modal-close" onclick="closeModal('addModal')">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="modal-field">
                                <label class="modal-label">Equipment Name</label>
                                <input type="text" class="input-field" value="Sony Alpha Camera 35MM">
                            </div>
                            <div class="grid-row grid-cols-2">
                                <div class="modal-field">
                                    <label class="modal-label">Category</label>
                                    <select class="input-field">
                                        <option>Electronic</option>
                                        <option>Computer</option>
                                        <option>AV Equipment</option>
                                    </select>
                                </div>
                                <div class="modal-field">
                                    <label class="modal-label">Max Loan Period</label>
                                    <input type="text" class="input-field" value="3 Days">
                                </div>
                            </div>
                            <div class="modal-field">
                                <label class="modal-label">Late Fine Rule</label>
                                <select class="input-field">
                                    <option>Standard daily late fine</option>
                                    <option>Premium</option>
                                </select>
                            </div>
                            <div class="modal-field">
                                <label class="modal-label">Description</label>
                                <textarea class="input-field"
                                    placeholder="Add a clear catalog description for borrowers..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-outline btn-rect" onclick="closeModal('addModal')">Cancel</button>
                            <button class="btn btn-primary btn-rect" onclick="closeModal('addModal')">Add Item</button>
                        </div>
                    </div>
                </div>

                <!-- Modal Toggle Logic -->
                <script>
                    function openModal(modalId) {
                        document.getElementById(modalId).classList.add('open');
                    }

                    function closeModal(modalId) {
                        document.getElementById(modalId).classList.remove('open');
                    }

                    document.addEventListener('DOMContentLoaded', () => {
                        // Open Add Modal when clicking '+ Add New Item' in the toolbar
                        const addBtn = document.querySelector('.requests-toolbar .btn-primary');
                        if (addBtn) {
                            addBtn.addEventListener('click', () => openModal('addModal'));
                        }

                        // Open Edit Modal when clicking 'Edit Details' in the catalog cards
                        const editBtns = document.querySelectorAll('.catalog-actions .btn-soft');
                        editBtns.forEach(btn => {
                            btn.addEventListener('click', () => openModal('editModal'));
                        });

                        // Open Archive Modal when clicking 'Archive' in the catalog cards
                        const archiveBtns = document.querySelectorAll('.catalog-actions .btn-decline');
                        archiveBtns.forEach(btn => {
                            btn.addEventListener('click', () => openModal('archiveModal'));
                        });

                        // Allow clicking outside the modal card to close it
                        const overlays = document.querySelectorAll('.modal-overlay');
                        overlays.forEach(overlay => {
                            overlay.addEventListener('click', (e) => {
                                if (e.target === overlay) closeModal(overlay.id);
                            });
                        });
                    });
                </script>
            </section>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>

</html>