<?php if (!defined('APP_INIT')) { http_response_code(403); exit; } ?>
<div class="modal-overlay" id="editUnitModal">
    <div class="modal m-manage-serialized-edit-unit-s1">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">Edit Unit</h2>
                <p class="modal-subtitle" id="eu-model-name"></p>
            </div>
            <button type="button" class="modal-close" data-close-modal="editUnitModal">&times;</button>
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
                <button type="button" class="btn btn-outline btn-rect" data-close-modal="editUnitModal"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                <button type="submit" class="btn btn-primary btn-rect" id="edit-unit-submit-btn"><i class="ph ph-floppy-disk" aria-hidden="true"></i><span class="btn-text">Save Changes</span></button>
            </div>
        </form>
    </div>
</div>
