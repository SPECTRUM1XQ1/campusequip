<?php if (!defined('APP_INIT')) { http_response_code(403); exit; } ?>
<div class="modal-overlay" id="categoryModal">
    <div class="modal m-manage-catalog-category-s1">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">Add Category</h2>
                <p class="modal-subtitle">New categories appear when adding or editing equipment.</p>
            </div>
            <button type="button" class="modal-close" aria-label="Close" data-close-modal="categoryModal">
                <i class="ph ph-x m-manage-catalog-category-s2"></i>
            </button>
        </div>
        <form id="category-form">
            <div class="modal-body">
                <div class="modal-field">
                    <label class="modal-label" for="category_name">Category name *</label>
                    <input type="text" class="input-field" id="category_name" name="category_name" maxlength="50" placeholder="e.g. Lab Instruments" required>
                </div>
                <p class="modal-label m-manage-catalog-category-s3">Existing categories</p>
                <div class="m-manage-catalog-category-s4">
                    <?php foreach ($categories as $cat): ?>
                        <span class="pill pill--neutral"><?= htmlspecialchars($cat['category_name']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline btn-rect" data-close-modal="categoryModal"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                <button type="submit" class="btn btn-primary btn-rect" id="category-submit-btn"><i class="ph ph-plus" aria-hidden="true"></i><span class="btn-text">Add Category</span></button>
            </div>
        </form>
    </div>
</div>
