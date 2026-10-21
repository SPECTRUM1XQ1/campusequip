<?php if (!defined('APP_INIT')) { http_response_code(403); exit; } ?>
<div class="modal-overlay" id="approveModal" aria-hidden="true">
    <div class="modal">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Approve Reservation</h3>
                <p class="modal-subtitle">Assign an available serialized unit to fulfill this request.</p>
            </div>
            <button type="button" class="modal-close" data-close-modal="approveModal">&times;</button>
        </div>

        <form id="approve-form">
            <input type="hidden" name="reservation_id" id="approve_reservation_id">
            <div class="modal-body">
                <dl class="modal-summary">
                    <dt>Request ID</dt>
                    <dd id="approve-summary-id"></dd>
                    <dt>Borrower</dt>
                    <dd id="approve-summary-borrower"></dd>
                    <dt>Equipment</dt>
                    <dd id="approve-summary-model"></dd>
                </dl>

                <div class="modal-field">
                    <label class="modal-label" for="assign_item_id">Assign Physical Unit (Serial Number) *</label>
                    <select class="input-field" name="item_id" id="assign_item_id" required>
                        <!-- Dynamically filled with available serialized items -->
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline btn-rect" data-close-modal="approveModal"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                <button type="submit" class="btn btn-primary btn-rect" id="approve-submit-btn"><i class="ph ph-check" aria-hidden="true"></i><span class="btn-text">Confirm Approval</span></button>
            </div>
        </form>
    </div>
</div>
