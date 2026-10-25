<?php if (!defined('APP_INIT')) { http_response_code(403); exit; } ?>
<div class="modal-overlay" id="returnModal">
    <div class="modal">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">Process Item Return</h2>
                <p class="modal-subtitle">Inspect returned equipment condition.</p>
            </div>
            <button class="modal-close" data-close-modal="returnModal">&times;</button>
        </div>

        <form id="return-form">
            <input type="hidden" name="checkout_id" id="return_checkout_id">
            <div class="modal-body">
                <dl class="modal-summary">
                    <dt>Borrower</dt>
                    <dd id="ret-borrower"></dd>
                    <dt>Item</dt>
                    <dd id="ret-item"></dd>
                    <dt>Status</dt>
                    <dd id="ret-status-badge"></dd>
                </dl>

                <div class="modal-field">
                    <label class="modal-label">Item Condition *</label>
                    <label class="option-row">
                        <input type="radio" name="return_condition" value="good" checked> Good / Normal Condition
                    </label>
                    <label class="option-row">
                        <input type="radio" name="return_condition" value="damaged"> Damaged
                    </label>
                    <label class="option-row">
                        <input type="radio" name="return_condition" value="missing_accessories"> Missing Accessories
                    </label>
                </div>

                <!-- Shown only when "Missing Accessories" is selected -->
                <div class="modal-field" id="missing-accessories-field" style="display: none;">
                    <label class="modal-label">Which accessories are missing? *</label>
                    <div id="missing-accessories-list"></div>
                </div>

                <!-- Shown only when "Damaged" is selected -->
                <div class="modal-field" id="damage-notes-field" style="display: none;">
                    <label class="modal-label">What happened? * (describe the damage)</label>
                    <textarea class="input-field" name="damage_notes" maxlength="500" id="damage_notes" rows="3" placeholder="e.g. Screen cracked after being dropped, water damage on the case..."></textarea>
                </div>

                <div class="modal-field">
                    <label class="modal-label">Inspection Remarks (optional)</label>
                    <textarea class="input-field" name="inspection_remarks" maxlength="500" rows="2" placeholder="Any other notes about the item's condition..."></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline btn-rect" data-close-modal="returnModal"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                <button type="submit" class="btn btn-approve btn-rect" id="ret-submit-btn"><i class="ph ph-check-circle" aria-hidden="true"></i><span class="btn-text">Return</span></button>
            </div>
        </form>
    </div>
</div>
