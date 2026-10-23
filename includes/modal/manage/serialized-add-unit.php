<?php if (!defined('APP_INIT')) { http_response_code(403); exit; } ?>
<div class="modal-overlay" id="addUnitModal">
    <div class="modal m-manage-serialized-add-unit-s1">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">Register Physical Unit</h2>
                <p class="modal-subtitle">Add a barcode/serial tracked hardware item.</p>
            </div>
            <button class="modal-close" data-close-modal="addUnitModal">&times;</button>
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
                <button type="button" class="btn btn-outline btn-rect" data-close-modal="addUnitModal"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                <button type="submit" class="btn btn-primary btn-rect" id="add-unit-submit-btn"><i class="ph ph-barcode" aria-hidden="true"></i><span class="btn-text">Register</span></button>
            </div>
        </form>
    </div>
</div>
