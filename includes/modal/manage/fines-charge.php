<?php if (!defined('APP_INIT')) { http_response_code(403); exit; } ?>
<div class="modal-overlay" id="chargeModal">
    <div class="modal m-manage-fines-charge-s1">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">Charge a Fine</h2>
                <p class="modal-subtitle">For damage, missing parts, or other penalties.</p>
            </div>
            <button type="button" class="modal-close" aria-label="Close" data-close-modal="chargeModal">
                <i class="ph ph-x m-manage-fines-charge-s2"></i>
            </button>
        </div>
        <form id="charge-form">
            <div class="modal-body">
                <div class="modal-field">
                    <label class="modal-label" for="charge_checkout">Loan *</label>
                    <select class="input-field" id="charge_checkout" name="checkout_id" required>
                        <option value="">Select a loan...</option>
                        <?php foreach ($chargeable as $c): ?>
                            <option value="<?= (int) $c['checkout_id'] ?>">
                                REQ-<?= (int) $c['reservation_id'] ?> · <?= htmlspecialchars($c['model_name']) ?> (<?= htmlspecialchars($c['serial_number']) ?>) · <?= htmlspecialchars($c['borrower_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-field">
                    <label class="modal-label" for="charge_type">Type *</label>
                    <select class="input-field" id="charge_type" name="fine_type" required>
                        <option value="damage">Damage</option>
                        <option value="missing_accessories">Missing accessories</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="modal-field">
                    <label class="modal-label" for="charge_amount">Amount (&#8369;) *</label>
                    <input type="number" class="input-field" id="charge_amount" name="amount_due" min="0.01" max="99999.99" step="0.01" inputmode="decimal" required>
                    <small class="text-muted">Up to 5 digits (maximum &#8369;99,999.99).</small>
                </div>
                <div class="modal-field">
                    <label class="modal-label" for="charge_reason">Reason *</label>
                    <textarea class="input-field" id="charge_reason" name="reason" rows="3" placeholder="e.g. Cracked screen on return" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline btn-rect" data-close-modal="chargeModal"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                <button type="submit" class="btn btn-primary btn-rect" id="charge-submit-btn"><i class="ph ph-receipt" aria-hidden="true"></i><span class="btn-text">Charge</span></button>
            </div>
        </form>
    </div>
</div>
