<?php if (!defined('APP_INIT')) { http_response_code(403); exit; } ?>
<div class="modal-overlay" id="reserveModal">
    <div class="modal m-borrower-catalog-reserve-s1">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">New Reservation Request</h2>
                <p class="modal-subtitle">Pick your borrowing period</p>
            </div>
            <button class="modal-close" data-close-modal="reserveModal">&times;</button>
        </div>

        <form id="reserve-form" data-pickup-window-days="<?= (int) PICKUP_WINDOW_DAYS ?>" data-loan-due-time="<?= htmlspecialchars(LOAN_DUE_TIME) ?>">
            <input type="hidden" name="catalog_id" id="res_catalog_id">
            <div class="modal-body">
                <div class="m-borrower-catalog-reserve-s2">
                    <div class="m-borrower-catalog-reserve-s3">
                        <span class="m-borrower-catalog-reserve-s4">Equipment Model</span>
                        <span class="m-borrower-catalog-reserve-s5" id="res-model-name"></span>
                    </div>
                    <div class="m-borrower-catalog-reserve-s6">
                        <span class="m-borrower-catalog-reserve-s4">Max Loan Window</span>
                        <span class="m-borrower-catalog-reserve-s5" id="res-max-days"></span>
                    </div>
                    <div class="m-borrower-catalog-reserve-s7" id="res-fine-row">
                        <span class="m-borrower-catalog-reserve-s4">Late Fine (if returned late)</span>
                        <span class="m-borrower-catalog-reserve-s5" id="res-late-fine"></span>
                    </div>
                </div>

                <div class="m-borrower-catalog-reserve-s8">
                    <div>
                        <label class="modal-label" for="res_start_at">Start Date *</label>
                        <input type="date" class="input-field date-compact" name="start_at" id="res_start_at" required>
                    </div>
                    <div>
                        <label class="modal-label" for="res_days">Days to Borrow *</label>
                        <div class="day-stepper">
                            <button type="button" id="res_days_minus" aria-label="One day less">&minus;</button>
                            <input type="number" class="input-field" name="days" id="res_days" min="1" value="1" inputmode="numeric" required>
                            <button type="button" id="res_days_plus" aria-label="One day more">+</button>
                        </div>
                    </div>
                </div>
                <div class="day-chips" id="res_day_chips"></div>
                <p class="return-preview" id="res_pickup_preview"></p>

                <div class="m-borrower-catalog-reserve-s9">
                    <label class="modal-label">Purpose of Use *</label>
                    <textarea class="input-field" name="purpose" maxlength="500" placeholder="e.g. Capstone project documentation, laboratory experiment..." required></textarea>
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
