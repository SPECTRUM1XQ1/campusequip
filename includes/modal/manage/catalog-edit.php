<?php if (!defined('APP_INIT')) { http_response_code(403); exit; } ?>
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">Edit Equipment Model</h2>
                <p class="modal-subtitle">Changes to loan rules apply to future reservations only.</p>
            </div>
            <button type="button" class="modal-close" data-close-modal="editModal">&times;</button>
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
                    <textarea class="input-field" name="description" maxlength="500" id="edit_description"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline btn-rect" data-close-modal="editModal"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                <button type="submit" class="btn btn-primary btn-rect" id="edit-model-submit-btn"><i class="ph ph-floppy-disk" aria-hidden="true"></i><span class="btn-text">Save</span></button>
            </div>
        </form>
    </div>
</div>
