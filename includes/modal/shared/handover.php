<?php if (!defined('APP_INIT')) { http_response_code(403); exit; } ?>
<div class="modal-overlay" id="handoverModal">
    <div class="modal">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">Confirm Handover</h2>
                <p class="modal-subtitle">Verify student ID and item completeness.</p>
            </div>
            <button class="modal-close" data-close-modal="handoverModal">&times;</button>
        </div>

        <form id="handover-form">
            <input type="hidden" name="reservation_id" id="handover_reservation_id">
            <div class="modal-body">
                <dl class="modal-summary">
                    <dt>Borrower Name</dt>
                    <dd id="ho-borrower"></dd>
                    <dt>ID Number</dt>
                    <dd id="ho-idnumber"></dd>
                    <dt>Item</dt>
                    <dd id="ho-item"></dd>
                    <dt>Due Date</dt>
                    <dd id="ho-due"></dd>
                </dl>

                <div class="modal-field">
                    <label class="checkbox-row m-shared-handover-s1">
                        <input type="checkbox" name="id_verified" value="1" required> 
                        Physical ID card verified in person
                    </label>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline btn-rect" data-close-modal="handoverModal"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                <button type="submit" class="btn btn-primary btn-rect" id="ho-submit-btn"><i class="ph ph-package" aria-hidden="true"></i><span class="btn-text">Confirm Handover</span></button>
            </div>
        </form>
    </div>
</div>
