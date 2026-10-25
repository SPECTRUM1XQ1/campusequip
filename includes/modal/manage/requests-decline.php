<?php if (!defined('APP_INIT')) { http_response_code(403); exit; } ?>
<div class="modal-overlay" id="declineModal" aria-hidden="true">
    <div class="modal">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Decline Reservation</h3>
                <p class="modal-subtitle">State the reason; this will be logged and communicated.</p>
            </div>
            <button type="button" class="modal-close" data-close-modal="declineModal">&times;</button>
        </div>

        <form id="decline-form">
            <input type="hidden" name="reservation_id" id="decline_reservation_id">
            <div class="modal-body">
                <dl class="modal-summary">
                    <dt>Request ID</dt>
                    <dd id="decline-summary-id"></dd>
                    <dt>Borrower</dt>
                    <dd id="decline-summary-borrower"></dd>
                    <dt>Equipment</dt>
                    <dd id="decline-summary-model"></dd>
                </dl>

                <!-- Radio buttons: only one reason can be chosen -->
                <div class="modal-field" role="radiogroup" aria-labelledby="decline-reason-label">
                    <label class="modal-label" id="decline-reason-label">Reason for Decline *</label>
                    <label class="option-row">
                        <input type="radio" name="decline_choice" value="All units scheduled for laboratory classes."> Laboratory conflict
                    </label>
                    <label class="option-row">
                        <input type="radio" name="decline_choice" value="Unit undergoing routine hardware maintenance."> Under maintenance
                    </label>
                    <label class="option-row">
                        <input type="radio" name="decline_choice" value="Incomplete borrower clearance or unpaid fines."> Borrower fine block
                    </label>
                    <label class="option-row">
                        <input type="radio" name="decline_choice" value="__other__"> Other (specify below)
                    </label>
                </div>

                <div class="modal-field" id="decline-other-field" style="display: none;">
                    <textarea class="input-field" id="decline_other" maxlength="200" rows="3" placeholder="Enter reason details..."></textarea>
                </div>

                <!-- The value actually sent to api/reservation/decline.php -->
                <input type="hidden" name="decline_reason" id="decline_reason">
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline btn-rect" data-close-modal="declineModal"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                <button type="submit" class="btn btn-danger btn-rect" id="decline-submit-btn"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Decline</span></button>
            </div>
        </form>
    </div>
</div>
