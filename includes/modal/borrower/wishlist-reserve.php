<?php if (!defined('APP_INIT')) { http_response_code(403); exit; } ?>
<div class="modal-overlay" id="reserveModal">
    <div class="modal m-borrower-wishlist-reserve-s1">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">New Reservation Request</h2>
                <p class="modal-subtitle">Pick your borrowing period</p>
            </div>
            <button type="button" class="modal-close" data-close-modal="reserveModal">&times;</button>
        </div>

        <form id="reserve-form">
            <input type="hidden" name="catalog_id" id="res_catalog_id">
            <div class="modal-body">
                <div class="confirm-note m-borrower-wishlist-reserve-s2">
                    <div class="m-borrower-wishlist-reserve-s3">
                        <span class="text-muted">Equipment Model</span>
                        <strong id="res-model-name"></strong>
                    </div>
                    <div class="m-borrower-wishlist-reserve-s4">
                        <span class="text-muted">Max Loan Window</span>
                        <strong id="res-max-days"></strong>
                    </div>
                </div>

                <div class="grid-row grid-cols-2 m-borrower-wishlist-reserve-s5">
                    <div>
                        <label class="modal-label" for="res_start_at">Start Date *</label>
                        <input type="date" class="input-field" name="start_at" id="res_start_at" required>
                    </div>
                    <div>
                        <label class="modal-label" for="res_end_at">End Date *</label>
                        <input type="date" class="input-field" name="end_at" id="res_end_at" required>
                    </div>
                </div>

                <div class="modal-field">
                    <label class="modal-label" for="res_purpose">Purpose of Use *</label>
                    <textarea class="input-field" id="res_purpose" name="purpose" placeholder="e.g. Capstone project documentation, laboratory experiment..." required></textarea>
                </div>

                <label class="checkbox-row">
                    <input type="checkbox" name="terms_accepted" value="1" required checked>
                    I accept academic borrowing responsibility and will return the item on time.
                </label>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline btn-rect" data-close-modal="reserveModal"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                <button type="submit" class="btn btn-primary btn-rect" id="reserve-submit-btn"><i class="ph ph-paper-plane-tilt" aria-hidden="true"></i><span class="btn-text">Submit</span></button>
            </div>
        </form>
    </div>
</div>
