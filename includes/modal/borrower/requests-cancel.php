<?php if (!defined('APP_INIT')) { http_response_code(403); exit; } ?>
<div class="modal-overlay" id="cancelModal">
    <div class="modal modal--confirm">
        <div class="modal-header">
            <h2 class="modal-title">Cancel Request</h2>
            <button type="button" class="modal-close" aria-label="Close" data-close-modal="cancelModal">
                <i class="ph ph-x m-borrower-requests-cancel-s1"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="confirm-note">
                <i class="ph ph-warning"></i>
                <span id="cancel-message">Are you sure you want to cancel this request?</span>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline btn-rect" data-close-modal="cancelModal"><i class="ph ph-arrow-u-up-left" aria-hidden="true"></i><span class="btn-text">Keep Request</span></button>
            <button type="button" class="btn btn-danger btn-rect" id="cancel-confirm-btn"><i class="ph ph-x-circle" aria-hidden="true"></i><span class="btn-text">Yes, Cancel</span></button>
        </div>
    </div>
</div>
