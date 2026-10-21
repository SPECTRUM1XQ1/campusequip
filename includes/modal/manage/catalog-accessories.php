<?php if (!defined('APP_INIT')) { http_response_code(403); exit; } ?>
<div class="modal-overlay" id="accessoriesModal">
    <div class="modal">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Accessories</h3>
                <p class="modal-subtitle" id="acc-model-name"></p>
            </div>
            <button type="button" class="modal-close" data-close-modal="accessoriesModal">&times;</button>
        </div>
        <div class="modal-body">
            <p class="text-muted m-manage-catalog-accessories-s1">Items that come with this model. Staff tick the missing ones when it is returned.</p>
            <div id="acc-list"></div>

            <form id="add-accessory-form" class="m-manage-catalog-accessories-s2">
                <input type="text" class="input-field" id="acc-new-name" maxlength="100" placeholder="e.g. Power adapter" required>
                <button type="submit" class="btn btn-primary btn-sm btn-rect" id="acc-add-btn"><i class="ph ph-plus" aria-hidden="true"></i><span class="btn-text">Add</span></button>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline btn-rect" data-close-modal="accessoriesModal"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Close</span></button>
        </div>
    </div>
</div>
