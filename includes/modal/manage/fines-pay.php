<?php if (!defined('APP_INIT')) { http_response_code(403); exit; } ?>
<div class="modal-overlay" id="payModal">
    <div class="modal modal--confirm">
        <div class="modal-header">
            <h2 class="modal-title">Record Payment</h2>
            <button type="button" class="modal-close" aria-label="Close" data-close-modal="payModal">
                <i class="ph ph-x m-manage-fines-pay-s1"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="confirm-note">
                <i class="ph ph-receipt m-manage-fines-pay-s2"></i>
                <span id="pay-message"></span>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline btn-rect" data-close-modal="payModal"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
            <button type="button" class="btn btn-primary btn-rect" id="pay-confirm-btn"><i class="ph ph-coins" aria-hidden="true"></i><span class="btn-text">Confirm Payment</span></button>
        </div>
    </div>
</div>
