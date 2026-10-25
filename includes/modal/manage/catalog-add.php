<?php if (!defined('APP_INIT')) { http_response_code(403); exit; } ?>
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">Add Equipment Model</h2>
                <p class="modal-subtitle">Define catalog model information and loan rules.</p>
            </div>
            <button class="modal-close" data-close-modal="addModal">&times;</button>
        </div>
        <form id="add-model-form">
            <div class="modal-body">
                <div class="modal-field">
                    <label class="modal-label">Model Name *</label>
                    <input type="text" class="input-field" name="model_name" maxlength="150" placeholder="e.g. Sony Alpha A7 IV" required>
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
                    <textarea class="input-field" name="description" maxlength="500" placeholder="Technical specifications, included accessories..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline btn-rect" data-close-modal="addModal"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                <button type="submit" class="btn btn-primary btn-rect" id="add-model-submit-btn"><i class="ph ph-floppy-disk" aria-hidden="true"></i><span class="btn-text">Save</span></button>
            </div>
        </form>
    </div>
</div>
