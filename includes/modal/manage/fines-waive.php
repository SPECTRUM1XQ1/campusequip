<?php if (!defined('APP_INIT')) { http_response_code(403); exit; } ?>
<div class="modal-overlay" id="waiveModal">
    <div class="modal modal--confirm">
        <div class="modal-header">
            <h2 class="modal-title">Waive Fine</h2>
            <button type="button" class="modal-close" aria-label="Close" data-close-modal="waiveModal">
                <i class="ph ph-x m-manage-fines-waive-s1"></i>
            </button>
        </div>
        <form id="waive-form">
            <div class="modal-body">
                <div class="confirm-note"><i class="ph ph-warning"></i><span id="waive-message"></span></div>
                <div class="modal-field m-manage-fines-waive-s2">
                    <label class="modal-label" for="waive_reason">Reason for waiving *</label>
                    <textarea class="input-field" id="waive_reason" name="reason" rows="3" placeholder="e.g. Item was held by the lab for inspection" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline btn-rect" data-close-modal="waiveModal"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                <button type="submit" class="btn btn-danger btn-rect" id="waive-submit-btn"><i class="ph ph-minus-circle" aria-hidden="true"></i><span class="btn-text">Waive</span></button>
            </div>
        </form>
    </div>
</div>
