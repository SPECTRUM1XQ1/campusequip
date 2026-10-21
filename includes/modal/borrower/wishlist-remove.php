<?php if (!defined('APP_INIT')) { http_response_code(403); exit; } ?>
<div class="modal-overlay" id="removeWishlistModal">
    <div class="modal modal--confirm">
        <div class="modal-header">
            <h2 class="modal-title">Confirm Action</h2>
            <button type="button" class="modal-close" aria-label="Close" data-close-modal="removeWishlistModal">
                <i class="ph ph-x m-borrower-wishlist-remove-s1"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="confirm-note">
                <i class="ph ph-warning"></i>
                <span id="remove-message">Are you sure you want to remove this item?</span>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline btn-rect" data-close-modal="removeWishlistModal"><i class="ph ph-arrow-u-up-left" aria-hidden="true"></i><span class="btn-text">Keep</span></button>
            <button type="button" class="btn btn-danger btn-rect" id="remove-confirm-btn"><i class="ph ph-trash" aria-hidden="true"></i><span class="btn-text">Confirm Remove</span></button>
        </div>
    </div>
</div>
